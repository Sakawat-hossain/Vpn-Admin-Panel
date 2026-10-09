<?php

namespace App\Console\Commands;

use App\Jobs\RevokeWireGuardPeer;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:update-expired';

    protected $description = 'Update expired subscriptions to free plan';

    public function handle()
    {
        $freePlan = freePlan();
        if (!$freePlan) {
            $this->error('No free plan configured (FREE_PLAN_ID).');
            return self::FAILURE;
        }

        Subscription::with(['plan', 'user.servers'])
            ->where('expiry_at', '<', Carbon::now())
            ->chunkById(200, function ($subscriptions) use ($freePlan) {
                foreach ($subscriptions as $subscription) {
                    $wasPremium = $subscription->plan && !$subscription->plan->isFree();

                    $subscription->update([
                        'plan_id' => $freePlan->id,
                        'expiry_at' => Carbon::now()->addMonths(2),
                    ]);

                    // A WireGuard config keeps working until its peer is removed,
                    // so revoke premium-server access as soon as premium ends.
                    $user = $subscription->user;
                    if ($wasPremium && $user && $user->servers && $user->servers->isPremium()) {
                        RevokeWireGuardPeer::forUser($user);
                    }
                }
            });

        $this->info('Expired subscriptions updated successfully.');
        return self::SUCCESS;
    }
}
