<?php

namespace App\Http\Methods;

use App\Models\GeneratedImage;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;

class SubscriptionManager
{
    public static function subscription()
    {
        if (Auth::user()) {
            $subscription = self::users();
            return (object) $subscription;
        }
        return false;
    }

    private static function users()
    {
        $user = userAuthInfo();
        if ($user->isSubscribed()) {
            $subscription['is_subscribed'] = $user->subscription->isActive() ? true : ($user->subscription->isFree() ? true : false);
            $subscription['plan'] = $user->subscription->plan;
        } else {
            return self::unsubscribed();
        }
        return $subscription;
    }

    private static function unsubscribed()
    {
        $subscription['is_subscribed'] = false;
        $subscription['remaining_images'] = 0;
        return $subscription;
    }

}