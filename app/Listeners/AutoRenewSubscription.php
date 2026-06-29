<?php

namespace App\Listeners;

use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRenewed;
use App\Repositories\SubscriptionRepository;
use App\User;

class AutoRenewSubscription
{
     /**
      * @param SubscriptionRepository
      */
     public function __construct(SubscriptionRepository $subscriptionRepository)
     {
         $this->subscriptionRepository = $subscriptionRepository;
     }

    /**
    * Auto-renews the subscription.
    *
    * @param SubscriptionRenewed $event
    * @return void
    */
    public function handle(SubscriptionRenewed $event)
    {
       // The following data can be retrieved from the event
       $subscription = $event->getServerNotification()->getSubscription();
       $uniqueIdentifier = $subscription->getUniqueIdentifier();
       $expirationTime = $subscription->getExpiryTime();

       // The following steps are up to you, this is only an imagined scenario.
       $subscription = $this->subscriptionRepository->find($uniqueIdentifier);
       $subscription->setExpiryTime($expirationTime->getCarbon());
       $subscription->save();

        // Let's say you want to send a notification to the user
       $this->notifyUserAboutUpdate($subscription->getUser());
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