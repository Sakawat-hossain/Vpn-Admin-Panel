<?php

namespace App\Listeners;

use App\Models\Subscription;
use Illuminate\Auth\Events\Registered;

class CreateFreeSubscription
{
    /**
     * Handle the event.
     *
     * The sign-up code normally creates the subscription itself; this only
     * covers a user who has none yet (it used to add a second one).
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Registered $event)
    {
        Subscription::startFree($event->user);
    }
}
