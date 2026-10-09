<?php

namespace App\Listeners;

use App\Models\IapPurchase;
use App\Models\Subscription;
use Carbon\Carbon;
use Imdhemy\Purchases\Events\AppStore\Refund;
use Imdhemy\Purchases\Events\AppStore\Revoke;
use Imdhemy\Purchases\Events\PurchaseEvent;
use Illuminate\Support\Facades\Log;

/**
 * App Store server notifications: keep a known purchase's subscription in sync
 * without waiting for the app to re-validate its receipt.
 *  - renewals / recoveries extend the expiry date;
 *  - refunds and revocations end it now (the expiry cron then moves the user
 *    to the free plan and removes premium-server access).
 */
class SyncAppStoreSubscription
{
    public function handle(PurchaseEvent $event)
    {
        try {
            $storeSubscription = $event->getSubscription();
            $originalTransactionId = $storeSubscription->getUniqueIdentifier();
        } catch (\Throwable $e) {
            Log::warning('App Store notification without subscription data: ' . $e->getMessage());
            return;
        }

        $purchase = IapPurchase::where('platform', IapPurchase::PLATFORM_ITUNES)
            ->where('original_transaction_id', $originalTransactionId)
            ->first();
        if (!$purchase) {
            return; // purchase not linked to an account yet; the app's receipt validation will do it
        }
        $subscription = Subscription::where('user_id', $purchase->user_id)->first();
        if (!$subscription) {
            return;
        }

        if ($event instanceof Refund || $event instanceof Revoke) {
            $subscription->update(['expiry_at' => Carbon::now()->subSecond()]);
            $purchase->update(['expires_at' => Carbon::now()->subSecond()]);
            return;
        }

        $expiresAt = $storeSubscription->getExpiryTime()->toCarbon();
        if ($expiresAt->isFuture() && (int) $subscription->plan_id === (int) $purchase->plan_id
            && $expiresAt->gt($subscription->expiry_at)) {
            $subscription->update([
                'expiry_at' => $expiresAt,
                'status' => Subscription::STATUS_ACTIVE,
                'about_to_expire_reminder' => false,
                'expired_reminder' => false,
            ]);
        }
        if (!$purchase->expires_at || $expiresAt->gt($purchase->expires_at)) {
            $purchase->update(['expires_at' => $expiresAt]);
        }
    }
}
