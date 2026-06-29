<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use Carbon\Carbon;

class UpdateExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:update-expired';

    protected $description = 'Update expired subscriptions to free plan';

    public function handle()
    {
        $users = User::whereHas('subscription', function ($query) {
            $query->where('expiry_at', '<', Carbon::now());
        })->get();

        foreach ($users as $user) {
            $freePlan = Plan::find(13);
            if ($freePlan) {
                $user->subscription->update([
                    'plan_id' => $freePlan->id,
                    'expiry_at' => Carbon::now()->addMonths(2),
                ]);
            }
        }

        $this->info('Expired subscriptions updated successfully.');
    }
}
