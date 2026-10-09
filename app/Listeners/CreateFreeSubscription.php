<?php

namespace App\Listeners;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;

class CreateFreeSubscription
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Registered $event)
    {
        $user = $event->user;
        // Registration code paths may already have created the subscription.
        if (Subscription::where('user_id', $user->id)->exists()) {
            return;
        }
        $freePlan = freePlan();
        if ($freePlan) {
            $subscription = new Subscription();
            $subscription->user_id = $user->id;
            $subscription->plan_id = $freePlan->id;
            $subscription->expiry_at = $freePlan->periodEnd(Carbon::now());
            $subscription->save();
        }
    }
}