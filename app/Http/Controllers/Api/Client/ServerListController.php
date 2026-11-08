<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\OpenVpnPki;
use App\Services\OpenVpnProfile;
use Illuminate\Support\Facades\Cache;

/**
 * Server list and geo lookup for the client apps, in the shape of the IVPN
 * client API (v5/servers.json, v4/geo-lookup).
 *
 * Only panel-managed OpenVPN servers are listed: the apps get the panel CA
 * and tls-auth key from "config.openvpn" and log in with their session's VPN
 * credentials. Free/premium is enforced
 * when the server checks the login; "is_premium" is extra data for the UI.
 */
class ServerListController extends Controller
{
    public function servers(OpenVpnPki $pki)
    {
        // Only servers whose latest deployment succeeded: a server whose deploy failed (wrong
        // password, changed host key, port in use) has no working OpenVPN, and the apps would
        // keep trying it.
        $servers = Server::where('status', 1)->where('is_ovpn', 1)->where('ovpn_managed', 1)
            ->where(fn($q) => $q->selectRaw('status')->from('config_server_jobs')
                ->whereColumn('config_server_jobs.server_id', 'servers.id')
                ->orderByDesc('id')->limit(1), 'success')
            ->orderByDesc('recommended')->orderBy('country')->orderBy('id')->get();

        $proto = config('services.openvpn.proto') === 'tcp' ? 'TCP' : 'UDP';

        return response()->json([
            'wireguard' => [],
            // Servers in the same country and city become one location with several hosts
            // ("Dhaka District #1", "#2", ...). The location keeps the gateway of its first
            // server, so a location with a single server looks exactly as before.
            'openvpn' => $servers
                ->groupBy(fn(Server $server) => strtoupper((string) countryIsoCode($server->country)) . '|' . mb_strtolower(trim((string) $server->state)))
                ->map(function ($group) {
                    $first = $group->first();
                    $multi = $group->count() > 1;
                    $hosts = $group->values()->map(fn(Server $server, $i) => [
                        // the apps use the part before the first dot as the host id
                        'hostname' => 'server-' . $server->id . '.gw',
                        'host' => $server->ip_address,
                        'name' => $multi ? trim($server->state . ' #' . ($i + 1)) : (string) $server->state,
                        'is_premium' => (bool) $server->isPremium(),
                        'dns_name' => '',
                        'multihop_port' => 0,
                        'load' => 0,
                        'v2ray' => '',
                        'isp' => '',
                        'obfs' => (object) [],
                    ]);
                    return [
                        'gateway' => 'server-' . $first->id,
                        'country_code' => strtoupper((string) countryIsoCode($first->country)),
                        'country' => $first->country,
                        'city' => $first->state,
                        'latitude' => (float) $first->latitude,
                        'longitude' => (float) $first->longitude,
                        'isp' => '',
                        // the location is Premium only when every host is
                        'is_premium' => $hosts->every(fn($h) => $h['is_premium']),
                        'recommended' => (bool) $group->contains(fn(Server $server) => (bool) $server->recommended),
                        'hosts' => $hosts->all(),
                    ];
                })->values(),
            'config' => [
                'antitracker' => ['default' => ['ip' => ''], 'hardcore' => ['ip' => '']],
                'antitracker_plus' => ['DnsServers' => []],
                'api' => $this->apiAddresses(),
                'openvpn' => [
                    'ca' => $pki->caCertificate(),
                    'tls_auth' => $pki->tlsAuthKey(),
                    'tls_mode' => OpenVpnProfile::tlsMode(),
                ],
                'ports' => [
                    'openvpn' => [['type' => $proto, 'port' => (int) config('services.openvpn.port')]],
                    'wireguard' => [],
                    'obfs3' => ['port' => 0],
                    'obfs4' => ['port' => 0],
                    'test' => [],
                    'v2ray' => (object) [],
                ],
            ],
        ]);
    }

    /**
     * The panel's IP addresses. The apps fall back to them when DNS fails
     * and allow them through the kill switch.
     */
    private function apiAddresses(): array
    {
        $configured = array_filter(array_map('trim', explode(',', (string) config('services.openvpn.api_ips'))));
        $ips = $configured ?: Cache::remember('client-api-ips', now()->addHour(), function () {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (!$host) {
                return [];
            }
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                return [$host];
            }
            return gethostbynamel($host) ?: [];
        });

        $v4 = array_values(array_filter($ips, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)));
        $v6 = array_values(array_filter($ips, fn($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)));
        return ['ips' => $v4, 'ipv6s' => $v6];
    }

    public function geoLookup()
    {
        $info = ipInfo();
        $location = $info->location;
        return response()->json([
            'ip_address' => $info->ip,
            'isp' => '',
            'organization' => '',
            'country' => $location->country === 'Other' ? '' : $location->country,
            'country_code' => $location->country_code === 'Other' ? '' : $location->country_code,
            'city' => $location->city === 'Other' ? '' : $location->city,
            'latitude' => is_numeric($location->latitude) ? (float) $location->latitude : 0,
            'longitude' => is_numeric($location->longitude) ? (float) $location->longitude : 0,
            'isIvpnServer' => Server::where('ip_address', $info->ip)->exists(),
        ]);
    }
}
