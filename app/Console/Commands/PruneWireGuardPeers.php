<?php

namespace App\Console\Commands;

use App\Exceptions\WgEasyException;
use App\Models\Server;
use App\Models\User;
use App\Services\WgEasyClient;
use Illuminate\Console\Command;

/**
 * Remove WireGuard peers that should no longer work:
 *  - the account was deleted or banned,
 *  - the user has since moved to another server,
 *  - the server is premium and the user's subscription is no longer premium.
 *
 * Each wg-easy server only has 253 addresses, so this also frees capacity.
 */
class PruneWireGuardPeers extends Command
{
    protected $signature = 'wg:prune {--dry-run : Only list the peers that would be removed}';

    protected $description = 'Remove stale or unauthorised WireGuard peers from all wg-easy servers';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $removed = 0;
        $failed = 0;

        foreach (Server::where('is_ovpn', 0)->get() as $server) {
            try {
                $wg = WgEasyClient::for($server);
                $clients = $wg->listClients();
            } catch (WgEasyException $e) {
                $failed++;
                $this->warn("Server #{$server->id} ({$server->ip_address}): " . $e->getMessage());
                continue;
            }

            foreach ($clients as $client) {
                $id = (string) ($client['id'] ?? '');
                if (!preg_match('/^wg(\d+)$/', $id, $m)) {
                    continue; // not created by the panel; leave it alone
                }
                $reason = $this->staleReason(User::with('subscription.plan')->find((int) $m[1]), $server);
                if ($reason === null) {
                    continue;
                }
                $this->line(($dryRun ? '[dry-run] ' : '') . "Server #{$server->id}: removing {$id} ({$reason})");
                if ($dryRun) {
                    continue;
                }
                try {
                    $wg->deleteClient($id);
                    $removed++;
                } catch (WgEasyException $e) {
                    $failed++;
                    $this->warn("  failed: " . $e->getMessage());
                }
            }
        }

        $this->info("Removed {$removed} peer(s)" . ($failed ? ", {$failed} error(s)" : '') . '.');
        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function staleReason(?User $user, Server $server): ?string
    {
        if (!$user) {
            return 'account deleted';
        }
        if ($user->isBanned()) {
            return 'account banned';
        }
        if ((int) $user->server_id !== (int) $server->id) {
            return 'user moved to another server';
        }
        if (!$user->canUseServer($server)) {
            return 'no premium subscription';
        }
        return null;
    }
}
