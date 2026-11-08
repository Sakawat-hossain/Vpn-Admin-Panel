<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * One-off clean-ups of the subscriptions table (run by migrations):
 *  - users with several rows (web sign-up used to create two);
 *  - users with none (deleted by an admin before deleting moved users to the
 *    free plan, or a Facebook sign-up that crashed half-way).
 */
class SubscriptionRepair
{
    private const CHUNK = 200;

    /**
     * Keep one subscription per user: the row on a paid plan with the latest
     * expiry date if there is one, otherwise the newest row. Returns the
     * number of rows deleted.
     */
    public static function removeDuplicates(): int
    {
        $deleted = 0;
        $lastUserId = 0;
        while (true) {
            $userIds = DB::table('subscriptions')
                ->select('user_id')
                ->where('user_id', '>', $lastUserId)
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->orderBy('user_id')
                ->limit(self::CHUNK)
                ->pluck('user_id')
                ->map(fn($id) => (int) $id)
                ->all();
            if (!$userIds) {
                break;
            }
            $lastUserId = max($userIds);

            $rows = DB::table('subscriptions')
                ->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->whereIn('subscriptions.user_id', $userIds)
                ->select('subscriptions.id', 'subscriptions.user_id', 'subscriptions.expiry_at', 'plans.id as plan_exists', 'plans.is_free')
                ->get()
                ->groupBy('user_id');

            $remove = [];
            foreach ($rows as $userRows) {
                $keep = self::rowToKeep($userRows->all());
                foreach ($userRows as $row) {
                    if ((int) $row->id !== $keep) {
                        $remove[] = (int) $row->id;
                    }
                }
            }
            foreach (array_chunk($remove, self::CHUNK) as $ids) {
                $deleted += DB::table('subscriptions')->whereIn('id', $ids)->delete();
            }
        }
        return $deleted;
    }

    /**
     * @param  object[]  $rows  one user's subscription rows
     */
    private static function rowToKeep(array $rows): int
    {
        $paid = array_filter($rows, fn($row) => $row->plan_exists !== null && !(int) $row->is_free);
        if ($paid) {
            usort($paid, fn($a, $b) => strcmp((string) $b->expiry_at, (string) $a->expiry_at) ?: ((int) $b->id <=> (int) $a->id));
            return (int) $paid[0]->id;
        }
        return max(array_map(fn($row) => (int) $row->id, $rows));
    }

    /**
     * Put every user without a subscription on the free plan. Returns the
     * number of subscriptions created (0 when there is no free plan).
     */
    public static function addMissingFreeSubscriptions(): int
    {
        $plan = freePlan();
        if (!$plan) {
            return 0;
        }
        $created = 0;
        User::whereDoesntHave('subscription')->chunkById(self::CHUNK, function ($users) use ($plan, &$created) {
            foreach ($users as $user) {
                if (Subscription::startFree($user, $plan)) {
                    $created++;
                }
            }
        });
        return $created;
    }
}
