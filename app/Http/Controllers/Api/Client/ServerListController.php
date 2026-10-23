<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Server;

/**
 * Server list and geo lookup for the client apps, in the shape of the IVPN
 * client API (v5/servers.json, v4/geo-lookup).
 *
 * Only panel-managed OpenVPN servers are listed: the apps carry the panel CA
 * and log in with their session's VPN credentials. Free/premium is enforced
 * when the server checks the login; "is_premium" is extra data for the UI.
 */
class ServerListController extends Controller
{
    public function servers()
    {
        $servers = Server::where('status', 1)->where('is_ovpn', 1)->where('ovpn_managed', 1)
            ->orderByDesc('recommended')->orderBy('country')->orderBy('id')->get();

        $proto = config('services.openvpn.proto') === 'tcp' ? 'TCP' : 'UDP';

        return response()->json([
            'wireguard' => [],
            'openvpn' => $servers->map(fn(Server $server) => [
                'gateway' => 'server-' . $server->id,
                'country_code' => strtoupper((string) countryIsoCode($server->country)),
                'country' => $server->country,
                'city' => $server->state,
                'latitude' => (float) $server->latitude,
                'longitude' => (float) $server->longitude,
                'isp' => '',
                'is_premium' => $server->isPremium(),
                'recommended' => (bool) $server->recommended,
                'hosts' => [[
                    'hostname' => 'server-' . $server->id,
                    'host' => $server->ip_address,
                    'dns_name' => '',
                    'multihop_port' => 0,
                    'load' => 0,
                    'v2ray' => '',
                    'isp' => '',
                    'obfs' => (object) [],
                ]],
            ])->values(),
            'config' => [
                'antitracker' => ['default' => ['ip' => ''], 'hardcore' => ['ip' => '']],
                'antitracker_plus' => ['DnsServers' => []],
                'api' => ['ips' => [], 'ipv6s' => []],
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
