<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\Subscriptions\SubscriptionExpiredNotification;
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

        $notify = SendSubscriptionReminders::mailTemplateEnabled('subscription_expired');

        Subscription::with(['plan', 'user.servers'])
            ->where('expiry_at', '<', Carbon::now())
            ->chunkById(200, function ($subscriptions) use ($freePlan, $notify) {
                foreach ($subscriptions as $subscription) {
                    $wasPremium = $subscription->plan && !$subscription->plan->isFree();
                    // No mail for a suspended (cancelled) subscription or one the
                    // App Store / Google Play renews by itself.
                    $mail = $notify && $wasPremium && !$subscription->isCancelled()
                        && !$subscription->isCoveredByStorePurchase();
                    // The e-mail names the plan and date that ended, not the free plan.
                    $expired = clone $subscription;

                    $subscription->moveToFreePlan($freePlan, Carbon::now()->addMonths(2));

                    // Only a paid plan "expires"; the free plan is just renewed. A moved
                    // subscription is on the free plan with a new expiry date, so this
                    // runs once per expired paid subscription.
                    $user = $subscription->user;
                    if ($mail && $user && !$user->isBanned()) {
                        try {
                            $user->notify(new SubscriptionExpiredNotification($expired));
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }
                }
            });

        $this->info('Expired subscriptions updated successfully.');
        return self::SUCCESS;
    }
}
