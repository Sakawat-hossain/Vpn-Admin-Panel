<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\WgEasyException;
use App\Http\Controllers\Controller;
use App\Jobs\RevokeWireGuardPeer;
use App\Models\Server;
use App\Models\User;
use App\Services\WgEasyClient;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    /**
     * get servers
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $servers = Server::where('status', 1);

        // filter premium
        if ($request['is_premium'] == "0") {
            $servers->where('is_premium', 0);
        } elseif ($request['is_premium'] == "1") {
            $servers->where('is_premium', 1);
        } elseif ($request['recommended'] == "0") {
            $servers->where('recommended', 0);
        } elseif ($request['recommended'] == "1") {
            $servers->where('recommended', 1);
        }

        // ovpn_config / wg_password are hidden on the model; configs are only
        // handed out by connect() to users entitled to the server.
        $servers = $servers->get();
        return response200($servers, __('Successfully retrieved servers data'));
    }

    /**
     * get random server
     *
     * @return JsonResponse
     */
    public function random()
    {
        $server = Server::inRandomOrder()->where('status', 1)->where('is_premium', 0)->first();
        return response200($server, __('Successfully retrieved random server data'));
    }

    /**
     * Connect the authenticated user to a server and return its client config.
     *
     * @return JsonResponse
     */
    public function connect(Request $request, Server $server)
    {
        $user = $request->user('api');

        if (!$server->isEnabled()) {
            return responseError(404, __('This server is not available'));
        }
        if (!$user->canUseServer($server)) {
            return responseError(403, __('A premium subscription is required for this server'));
        }

        // OpenVPN server: hand back the stored .ovpn profile (no wg-easy involved).
        if ($server->isOpenVpn()) {
            if (trim((string) $server->ovpn_config) === '') {
                return responseError(503, __('This server is not configured yet'));
            }
            $this->assignServer($user, $server);
            return response200([
                'client_id' => 'ovpn' . $user->id,
                'protocol' => 'openvpn',
                'conf' => $server->ovpn_config,
            ], __('Connection Success'));
        }

        // WireGuard server: (re)create this user's peer on wg-easy and return its config.
        // Re-creating replaces the peer's keys, so only the most recently connected
        // device keeps working.
        $clientId = $user->wgClientName();
        try {
            $wg = WgEasyClient::for($server);
            $wg->createClient($clientId);
            $conf = $wg->getConfiguration($clientId, $this->dnsFor($user));
        } catch (WgEasyException $e) {
            report($e);
            return responseError(502, __('Could not reach the VPN server, please try another server'));
        }

        $this->assignServer($user, $server);

        return response200([
            'client_id' => $clientId,
            'protocol' => 'wireguard',
            'conf' => $conf,
        ], __('Connection Success'));
    }

    /**
     * Record the user's current server and free their peer on the previous one,
     * so stale peers don't keep working or fill up the server's 253 addresses.
     */
    private function assignServer(User $user, Server $server): void
    {
        $previousServerId = $user->server_id;
        $user->server_id = $server->id;
        $user->save();

        if ($previousServerId && (int) $previousServerId !== (int) $server->id) {
            RevokeWireGuardPeer::forUser($user, (int) $previousServerId);
        }
    }

    /**
     * DNS server(s) written into the WireGuard config: the user's own setting
     * when it is a valid IP list, otherwise the default.
     */
    private function dnsFor(User $user): string
    {
        $dns = trim((string) $user->dns);
        if ($dns !== '' && self::isValidDnsList($dns)) {
            return $dns;
        }
        return config('services.wg_easy.default_dns', '1.1.1.1');
    }

    public static function isValidDnsList(string $dns): bool
    {
        $parts = array_map('trim', explode(',', $dns));
        if (count($parts) > 4) {
            return false;
        }
        foreach ($parts as $part) {
            if (!filter_var($part, FILTER_VALIDATE_IP)) {
                return false;
            }
        }
        return true;
    }
}
