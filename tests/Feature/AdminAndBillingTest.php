<?php

namespace Tests\Feature;

use App\Listeners\CreateFreeSubscription;
use App\Listeners\SyncAppStoreSubscription;
use App\Models\Admin;
use App\Models\IapPurchase;
use App\Models\Settings;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Imdhemy\Purchases\Contracts\SubscriptionContract;
use Imdhemy\Purchases\Events\AppStore\DidRenew;
use Imdhemy\Purchases\Events\AppStore\Refund;
use Imdhemy\Purchases\ValueObjects\Time;

class AdminAndBillingTest extends ApiTestCase
{
    private function admin(): Admin
    {
        return Admin::forceCreate([
            'name' => 'Admin', 'firstname' => 'A', 'lastname' => 'D', 'email' => 'admin' . uniqid() . '@example.com',
            'avatar' => 'images/avatars/default.png', 'password' => bcrypt('x'),
        ]);
    }

    public function test_requests_without_user_agent_do_not_crash()
    {
        $user = $this->makeUser();
        $this->asUser($user)->withHeaders(['User-Agent' => ''])
            ->postJson('/api/v1/create-log', ['os' => 'Android'])->assertStatus(201);
    }

    public function test_admin_user_list_escapes_user_data_and_sorts_safely()
    {
        $this->makeUser(['name' => '<img src=x onerror=alert(1)>', 'email' => 'x@example.com']);
        $response = $this->actingAs($this->admin(), 'admin')->postJson('/admin/users/ajax', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'order' => [['column' => 0, 'dir' => 'desc; drop table users']],
            'columns' => [['data' => 'id'], [], [], ['search' => ['value' => '']]],
            'search' => ['value' => ''],
        ])->assertOk();

        $this->assertStringNotContainsString('<img src=x', $response->getContent());
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $response->json('data.0.name'));
    }

    public function test_plan_period_end_handles_every_interval()
    {
        $from = Carbon::parse('2026-01-15 10:00:00');
        $this->premiumPlan->interval = 3;
        $this->assertEquals('2026-01-22', $this->premiumPlan->periodEnd($from)->toDateString());
        $this->premiumPlan->interval = 4;
        $this->assertEquals('2026-07-15', $this->premiumPlan->periodEnd($from)->toDateString());
        $this->premiumPlan->interval = 2;
        $this->assertEquals('2027-01-15', $this->premiumPlan->periodEnd($from)->toDateString());
        $this->assertEquals('2026-01-15 10:00:00', $from->toDateTimeString(), 'input date must not be mutated');
    }

    public function test_admin_can_assign_a_plan_to_a_free_user()
    {
        $user = $this->makeUser();
        $this->premiumPlan->update(['interval' => 3]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.subscriptions.store'), ['user' => $user->id, 'plan' => $this->premiumPlan->id])
            ->assertRedirect();

        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
        $subscription = $user->fresh()->subscription;
        $this->assertSame($this->premiumPlan->id, $subscription->plan_id);
        $this->assertTrue($subscription->expiry_at->between(now()->addDays(6), now()->addDays(8)));
    }

    public function test_registered_event_does_not_duplicate_the_free_subscription()
    {
        $user = $this->makeUser();
        (new CreateFreeSubscription())->handle(new Registered($user));
        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());

        $fresh = User::factory()->create();
        (new CreateFreeSubscription())->handle(new Registered($fresh));
        $this->assertSame(1, Subscription::where('user_id', $fresh->id)->count());
    }

    public function test_cancelled_transaction_totals_use_the_right_columns()
    {
        $this->seedSiteSettings();
        $user = $this->makeUser();
        Transaction::forceCreate([
            'checkout_id' => 'c1', 'user_id' => $user->id, 'plan_id' => $this->premiumPlan->id,
            'price' => 10, 'tax' => 2, 'fees' => 1, 'total' => 13, 'type' => 1, 'status' => Transaction::STATUS_CANCELLED,
        ]);
        $response = $this->actingAs($this->admin(), 'admin')->get(route('admin.transactions.index'));
        $this->assertNull($response->exception, (string) optional($response->exception)->getMessage());
        $amount = $response->viewData('canceledAmount');
        $this->assertEquals(13, $amount['total']);
        $this->assertEquals(10, $amount['subscriptions']);
        $this->assertEquals(2, $amount['taxes']);
    }

    public function test_app_store_notifications_renew_and_refund_known_purchases()
    {
        $user = $this->makeUser([], true);
        IapPurchase::create([
            'platform' => IapPurchase::PLATFORM_ITUNES, 'original_transaction_id' => '777',
            'user_id' => $user->id, 'plan_id' => $this->premiumPlan->id, 'expires_at' => now()->addDay(),
        ]);
        $newExpiry = now()->addMonths(2)->startOfSecond();

        $renew = $this->eventFor(DidRenew::class, '777', $newExpiry);
        (new SyncAppStoreSubscription())->handle($renew);
        $this->assertEquals($newExpiry->timestamp, $user->fresh()->subscription->expiry_at->timestamp);

        $refund = $this->eventFor(Refund::class, '777', $newExpiry);
        (new SyncAppStoreSubscription())->handle($refund);
        $this->assertTrue($user->fresh()->subscription->expiry_at->isPast());
    }

    private function seedSiteSettings(): void
    {
        foreach (json_decode(file_get_contents(database_path('seeders/data/settings.json')), true) as $row) {
            $setting = Settings::where('key', $row['key'])->first() ?: new Settings();
            $value = $row['value'];
            if ($row['key'] === 'actions' && $setting->exists) {
                // keep the test's own email-verification setting
                $value = array_merge($value, (array) $setting->value);
            }
            $setting->forceFill(['key' => $row['key'], 'value' => (object) $value])->save();
        }
    }

    private function eventFor(string $class, string $originalTransactionId, Carbon $expiry)
    {
        $storeSubscription = $this->createMock(SubscriptionContract::class);
        $storeSubscription->method('getUniqueIdentifier')->willReturn($originalTransactionId);
        $storeSubscription->method('getExpiryTime')->willReturn(Time::fromCarbon($expiry));
        $event = $this->getMockBuilder($class)->disableOriginalConstructor()->onlyMethods(['getSubscription'])->getMock();
        $event->method('getSubscription')->willReturn($storeSubscription);
        return $event;
    }
}
