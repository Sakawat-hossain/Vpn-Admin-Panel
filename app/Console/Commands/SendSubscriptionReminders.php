<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\Subscriptions\SubscriptionAboutToExpiredNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * E-mails users whose paid subscription ends within the number of days set
 * in Settings > General > Subscription ("about to expire reminder"). Each
 * subscription is reminded once per expiry date: the flag
 * subscriptions.about_to_expire_reminder is cleared whenever the expiry date
 * changes (see Subscription::booted).
 *
 * The "expired" e-mail is sent by subscriptions:update-expired when it moves
 * an expired paid subscription to the free plan.
 */
class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:send-reminders';

    protected $description = 'E-mail users whose paid subscription is about to expire';

    public function handle()
    {
        $days = (int) (settings('subscription')->about_to_expire_reminder ?? 0);
        if ($days < 1) {
            $this->info('No reminder period configured.');
            return self::SUCCESS;
        }
        if (!self::mailTemplateEnabled('subscription_about_expired')) {
            $this->info('The "about to expire" mail template is disabled.');
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $sent = 0;
        Subscription::with(['plan', 'user'])
            ->notFree()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('about_to_expire_reminder', false)
            ->where('expiry_at', '>', $now)
            ->where('expiry_at', '<=', $now->copy()->addDays($days))
            ->chunkById(200, function ($subscriptions) use (&$sent) {
                foreach ($subscriptions as $subscription) {
                    $user = $subscription->user;
                    if (!$user || $user->isBanned() || $subscription->isCoveredByStorePurchase()) {
                        continue; // store subscriptions renew by themselves
                    }
                    try {
                        $user->notify(new SubscriptionAboutToExpiredNotification($subscription));
                    } catch (\Throwable $e) {
                        report($e); // try again on the next run
                        continue;
                    }
                    $subscription->forceFill(['about_to_expire_reminder' => true])->save();
                    $sent++;
                }
            });

        $this->info("Sent {$sent} reminder(s).");
        return self::SUCCESS;
    }

    /**
     * Admin > Settings > Mail Templates can switch a template off.
     */
    public static function mailTemplateEnabled(string $alias): bool
    {
        $template = mailTemplate($alias);
        return $template && (bool) $template->status;
    }
}
