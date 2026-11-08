<?php

use App\Services\SubscriptionRepair;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Web sign-up used to create two subscription rows per user (the sign-up
     * code and the Registered listener). Keep one per user: the paid row with
     * the latest expiry date, else the newest row. No unique index is added.
     */
    public function up()
    {
        SubscriptionRepair::removeDuplicates();
    }

    public function down()
    {
        // the deleted rows were duplicates
    }
};
