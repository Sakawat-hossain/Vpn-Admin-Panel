<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\User;

/**
 * Status codes and payloads of the IVPN-compatible client API.
 */
trait ClientResponses
{
    // Status codes the IVPN clients understand (daemon/api/types/errors.go).
    protected const SUCCESS = 200;
    protected const UNAUTHORIZED = 401;
    protected const SESSION_NOT_FOUND = 601;
    protected const SESSION_LIMIT = 602;
    protected const ACCOUNT_NOT_ACTIVE = 702;
    protected const TWO_FA_REQUIRED = 70011;
    protected const TWO_FA_INVALID = 70012;

    /**
     * Error in the client API's shape. The clients read the "status" field;
     * the HTTP code mirrors it when it is a real HTTP code.
     */
    protected function clientError(int $status, string $message)
    {
        $http = ($status >= 400 && $status < 600) ? $status : 200;
        return response()->json(['status' => $status, 'message' => $message], $http);
    }

    /**
     * The account state shown by the apps ("service_status").
     */
    protected function serviceStatus(User $user): array
    {
        $subscription = $user->subscription;
        $plan = $subscription?->plan;
        $premium = $user->hasPremiumAccess();
        $active = $subscription && $plan && !$subscription->isCancelled() && ($plan->isFree() || $subscription->isActive());

        return [
            'is_active' => (bool) $active,
            'active_until' => $subscription && $subscription->expiry_at && !$plan?->isFree() ? $subscription->expiry_at->getTimestamp() : 0,
            'current_plan' => $plan->name ?? '',
            'payment_method' => '',
            'is_renewable' => (bool) ($plan && !$plan->isFree()),
            'will_auto_rebill' => false,
            'is_on_free_trial' => false,
            'capabilities' => $premium ? ['premium'] : [],
            'upgradable' => !$premium,
            'upgrade_to_plan' => '',
            'upgrade_to_url' => route('pricing'),
            'device_management' => true,
            'device_management_url' => route('user.devices'),
            'limit' => deviceLimit(),
        ];
    }
}
