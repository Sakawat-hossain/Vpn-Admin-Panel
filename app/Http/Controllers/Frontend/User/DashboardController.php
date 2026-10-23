<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Transaction;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = userAuthInfo();
        $user->load('subscription.plan', 'servers');

        $servers = Server::where('status', 1)
            ->orderByDesc('recommended')
            ->orderBy('country')
            ->get();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereIn('status', [2, 3])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('frontend.user.dashboard', [
            'user' => $user,
            'subscription' => $this->subscriptionSummary($user),
            'servers' => $servers,
            'countriesCount' => $servers->pluck('country')->unique()->count(),
            'availableCount' => $servers->filter(fn($server) => $user->canUseServer($server))->count(),
            'transactions' => $transactions,
        ]);
    }

    /**
     * State of the user's plan for the dashboard card:
     * none, cancelled, expired, expiring, active or free.
     */
    private function subscriptionSummary($user): array
    {
        $subscription = $user->subscription;
        if (!$subscription || !$subscription->plan) {
            return ['state' => 'none', 'plan' => null, 'expiry' => null, 'daysLeft' => null, 'progress' => 0];
        }

        $plan = $subscription->plan;
        $expiry = $subscription->expiry_at ? Carbon::parse($subscription->expiry_at) : null;
        $daysLeft = $expiry && $expiry->isFuture() ? (int) ceil(now()->diffInHours($expiry) / 24) : 0;

        if ($subscription->isCancelled()) {
            $state = 'cancelled';
        } elseif ($subscription->isFree()) {
            $state = 'free';
        } elseif ($subscription->isExpired()) {
            $state = 'expired';
        } elseif ($subscription->isAboutToExpire()) {
            $state = 'expiring';
        } else {
            $state = 'active';
        }

        // Share of the current billing period still left, for the progress bar.
        $progress = 0;
        $periodDays = $this->periodDays($plan->interval);
        if ($state !== 'free' && $periodDays) {
            $progress = max(0, min(100, (int) round($daysLeft / $periodDays * 100)));
        }

        return [
            'state' => $state,
            'plan' => $plan,
            'expiry' => $expiry,
            'daysLeft' => $daysLeft,
            'progress' => $progress,
        ];
    }

    private function periodDays($interval): ?int
    {
        return [1 => 30, 2 => 365, 3 => 7, 4 => 182][(int) $interval] ?? null;
    }
}
