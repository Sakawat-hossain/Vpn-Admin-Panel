<?php

use App\Services\SubscriptionRepair;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Users without a subscription row are refused by the apps and the
     * OpenVPN login check. Put them on the free plan (runs after the
     * duplicate clean-up).
     */
    public function up()
    {
        SubscriptionRepair::addMissingFreeSubscriptions();
    }

    public function down()
    {
        // the users keep their free subscription
    }
};
