<?php

namespace App\Listeners;

use Imdhemy\Purchases\Events\AppStore\Subscribed;
use App\Models\Plan;
use App\Models\Subscription;
// use App\Repositories\SubscriptionRepository;
use App\User;
use Illuminate\Support\Facades\Log;

class AppStoreSubscribed
{
    //  /**
    //   * @param SubscriptionRepository
    //   */
    //  public function __construct(SubscriptionRepository $subscriptionRepository)
    //  {
    //      $this->subscriptionRepository = $subscriptionRepository;
    //  }

    /**
    * The subscription.
    *
    * @param Subscribed $subscribed
    * @return void
    */
    public function handle(Subscribed $subscribed)
    {
        Log::debug(print_r($subscribed,true));
        die;

        if($receiptStatus->isValid()) {
            $latestReceiptInfo = $receiptResponse->getLatestReceiptInfo();
            // You can loop all of them or either get the first one (recently purchased).
            $receiptInfo = $latestReceiptInfo[0];
            print_r($receiptInfo);

            // $productId = $receiptInfo->getProductId();
            // $transactionId = $receiptInfo->getTransactionId();
            // $originalTransactionId = $receiptInfo->getOriginalTransactionId();
            // $expiresDate = $receiptInfo->getExpiresDate();
            // $expirationTime->getCarbon()
            // And so on...

            // coba cari id plan
            // $plan_id='';
            // $plan = Plan::findOrFail($plan_id);

            // // cari user_id
            // $user_id='';
            // $subscription = Subscription::where('user_id', $user_id);

            // $expiry_at = Carbon::parse($expiresDate);
            // $subscription->update([
            //     'plan_id' => $plan->id,
            //     'expiry_at' => $expiry_at,
            //     'status' => 1,
            // ]);
        } else {
            // The receipt is invalid
        }
    }

    /**
     * Notify the user about the subscription update.
     *
     * @param User $user
     * @return void
     */
    private function notifyUserAboutUpdate($user)
    {
        // Send an email to the user
    }
}
