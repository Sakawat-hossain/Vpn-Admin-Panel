<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\SubscriptionController;
use App\Models\IapPurchase;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use ReflectionMethod;

class InAppPurchaseTest extends ApiTestCase
{
    private int $gatewayId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatewayId = PaymentGateway::forceCreate([
            'name' => 'App Store', 'alias' => 'appstore', 'handler' => 'x', 'logo' => 'x',
            'fees' => 0, 'min' => 0, 'credentials' => '{}', 'status' => 1,
        ])->id;
    }

    private function grant(User $user, string $purchaseId, Carbon $expiresAt)
    {
        $method = new ReflectionMethod(SubscriptionController::class, 'grant');
        $method->setAccessible(true);
        return $method->invoke(new SubscriptionController(), $user, $this->premiumPlan, IapPurchase::PLATFORM_ITUNES,
            $purchaseId, 'pro.monthly.ios', $expiresAt, false, $this->gatewayId, ['receipt' => true]);
    }

    public function test_a_purchase_can_only_unlock_one_account()
    {
        $alice = $this->makeUser();
        $bob = $this->makeUser();
        $expires = now()->addMonth();

        $this->assertSame(200, $this->grant($alice, '1000000001', $expires)->getStatusCode());
        $this->assertSame($this->premiumPlan->id, $alice->fresh()->subscription->plan_id);

        $this->assertSame(409, $this->grant($bob, '1000000001', $expires)->getStatusCode());
        $this->assertSame($this->freePlan->id, $bob->fresh()->subscription->plan_id);
    }

    public function test_revalidating_the_same_period_does_not_duplicate_transactions()
    {
        $alice = $this->makeUser();
        $expires = now()->addMonth();

        $this->grant($alice, '2000000001', $expires);
        $this->grant($alice, '2000000001', $expires);
        $this->assertSame(1, Transaction::where('user_id', $alice->id)->count());

        // A renewal (later expiry) is a new billing period.
        $this->grant($alice, '2000000001', $expires->copy()->addMonth());
        $this->assertSame(2, Transaction::where('user_id', $alice->id)->count());
    }

    public function test_request_validation_and_plan_product_ids()
    {
        $user = $this->makeUser();
        $this->asUser($user)->postJson('/api/v1/update-subscription', ['platform' => 'itunes'])->assertStatus(422);
        $this->asUser($user)->postJson('/api/v1/update-subscription', [
            'platform' => 'itunes', 'receipt_data' => 'x', 'plan' => $this->freePlan->id, 'payment_gateway_id' => $this->gatewayId,
        ])->assertStatus(422);
        $this->assertSame(['pro_monthly', 'pro.monthly.ios'], $this->premiumPlan->productIds());
    }
}
