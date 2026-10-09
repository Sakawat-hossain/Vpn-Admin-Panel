<?php

namespace App\Jobs;

use App\Models\Server;
use App\Models\User;
use App\Services\WgEasyClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Remove a user's WireGuard peer from a wg-easy server, so the config that
 * was handed out stops working (account deleted/banned, premium expired,
 * user moved to another server).
 */
class RevokeWireGuardPeer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 60;

    public function __construct(public int $serverId, public string $clientName)
    {
    }

    /**
     * Queue revocation of the user's peer on their current server, if any.
     */
    public static function forUser(User $user, ?int $serverId = null): void
    {
        $serverId = $serverId ?? $user->server_id;
        if ($serverId) {
            self::dispatch((int) $serverId, $user->wgClientName());
        }
    }

    public function handle()
    {
        $server = Server::find($this->serverId);
        if (!$server || $server->isOpenVpn()) {
            return;
        }
        WgEasyClient::for($server)->deleteClient($this->clientName);
    }
}
