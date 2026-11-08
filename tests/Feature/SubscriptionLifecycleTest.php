<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\User\CheckoutController;
use App\Models\Admin;
use App\Models\Country;
use App\Models\IapPurchase;
use App\Models\MailTemplate;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Settings;
use App\Models\SocialProvider;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VpnSession;
use App\Notifications\Subscriptions\SubscriptionAboutToExpiredNotification;
use App\Notifications\Subscriptions\SubscriptionExpiredNotification;
use App\Services\SubscriptionRepair;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

/**
 * Subscriptions over their life: sign-up, admin changes, checkout, cancelled
 * payments, reminders and expiry. Uses the stock seed data (plans, settings,
 * mail templates, gateways).
 */
class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Plan $free;
    private Plan $weekly;
    private Plan $monthly;
    private Plan $halfYearly;
    private Plan $yearly;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Server::query()->delete();
        $actions = Settings::where('key', 'actions')->first();
        $actions->value = array_merge((array) $actions->value, ['email_verification_status' => 0]);
        $actions->save();
        // Skip the ip-api.com lookup (layout, login history).
        Cache::put('ipinfo:127.0.0.1', (object) ['status' => 'success', 'query' => '127.0.0.1', 'countryCode' => 'US'], now()->addHour());
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-09 12:00:00'));

        $this->free = freePlan();
        $this->weekly = Plan::notFree()->where('interval', 3)->firstOrFail();
        $this->monthly = Plan::notFree()->where('interval', 1)->firstOrFail();
        $this->halfYearly = Plan::notFree()->where('interval', 4)->firstOrFail();
        $this->yearly = Plan::notFree()->where('interval', 2)->firstOrFail();
    }

    private function admin()
    {
        return $this->actingAs(Admin::first(), 'admin');
    }

    private function subscribe(User $user, Plan $plan, $expiryAt, int $status = Subscription::STATUS_ACTIVE): Subscription
    {
        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'expiry_at' => $expiryAt,
            'status' => $status,
        ]);
    }

    private function transaction(User $user, Plan $plan, int $status = Transaction::STATUS_PAID, int $type = 1): Transaction
    {
        $details = ['price' => $plan->price, 'tax' => 0, 'total' => $plan->price];
        return Transaction::create([
            'checkout_id' => 'chk_' . Str::random(16), 'user_id' => $user->id, 'plan_id' => $plan->id,
            'details_before_discount' => $details, 'details_after_discount' => $details,
            'price' => $plan->price, 'tax' => 0, 'fees' => 0, 'total' => $plan->price, 'type' => $type, 'status' => $status,
        ]);
    }

    private function storePurchase(User $user, Plan $plan, $expiresAt): IapPurchase
    {
        return IapPurchase::create([
            'platform' => IapPurchase::PLATFORM_GOOGLE_PLAY,
            'original_transaction_id' => 'gp-' . Str::random(12),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'product_id' => 'monthly',
            'expires_at' => $expiresAt,
        ]);
    }

    private function expiry(User $user): string
    {
        return $user->subscription()->firstOrFail()->expiry_at->toDateTimeString();
    }

    public function test_plan_expiry_follows_the_interval()
    {
        $this->assertSame('2026-12-09 12:00:00', $this->monthly->expiryFrom()->toDateTimeString());
        $this->assertSame('2027-11-09 12:00:00', $this->yearly->expiryFrom()->toDateTimeString());
        $this->assertSame('2026-11-16 12:00:00', $this->weekly->expiryFrom()->toDateTimeString());
        $this->assertSame('2027-05-09 12:00:00', $this->halfYearly->expiryFrom()->toDateTimeString());

        $from = Carbon::parse('2027-01-10 08:00:00');
        $this->assertSame('2027-07-10 08:00:00', $this->halfYearly->expiryFrom($from)->toDateTimeString());
        $this->assertSame('2027-01-10 08:00:00', $from->toDateTimeString(), 'the start date is not changed');
    }

    public function test_admin_add_subscription_uses_the_plan_interval()
    {
        $weeklyUser = User::factory()->create();
        $halfYearUser = User::factory()->create();

        $this->admin()->post(route('admin.subscriptions.store'), ['user' => $weeklyUser->id, 'plan' => $this->weekly->id])->assertRedirect();
        $this->admin()->post(route('admin.subscriptions.store'), ['user' => $halfYearUser->id, 'plan' => $this->halfYearly->id])->assertRedirect();

        $this->assertSame('2026-11-16 12:00:00', $this->expiry($weeklyUser));
        $this->assertSame('2027-05-09 12:00:00', $this->expiry($halfYearUser), 'not one year');
    }

    public function test_admin_created_user_gets_the_free_plan_with_its_interval()
    {
        $this->free->update(['interval' => 4]);

        $this->admin()->post(route('admin.users.store'), [
            'firstname' => 'New', 'lastname' => 'User', 'email' => 'new.user@example.com', 'password' => 'secret-password',
        ])->assertRedirect();

        $user = User::where('email', 'new.user@example.com')->firstOrFail();
        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
        $this->assertSame($this->free->id, (int) $user->subscription->plan_id);
        $this->assertSame('2027-05-09 12:00:00', $this->expiry($user));
    }

    public function test_checkout_uses_the_plan_interval_for_new_renewed_and_changed_subscriptions()
    {
        // New subscription (type 1)
        $new = User::factory()->create();
        CheckoutController::updateSubscription($this->transaction($new, $this->weekly));
        $this->assertSame('2026-11-16 12:00:00', $this->expiry($new));

        // Renewal before the end (type 2) adds a period to the current expiry date.
        $renew = User::factory()->create();
        $subscription = $this->subscribe($renew, $this->halfYearly, now()->addDays(2));
        $subscription->forceFill(['about_to_expire_reminder' => true])->save();
        CheckoutController::updateSubscription($this->transaction($renew, $this->halfYearly, Transaction::STATUS_PAID, 2));
        $this->assertSame('2027-05-11 12:00:00', $this->expiry($renew));
        $this->assertFalse((bool) $subscription->fresh()->about_to_expire_reminder);

        // Renewal paid after the cron moved the user to the free plan: back on the
        // renewed plan, with a period that starts now.
        $late = User::factory()->create();
        $this->subscribe($late, $this->free, now()->addMonths(2));
        CheckoutController::updateSubscription($this->transaction($late, $this->monthly, Transaction::STATUS_PAID, 2));
        $this->assertSame($this->monthly->id, (int) $late->subscription()->first()->plan_id);
        $this->assertSame('2026-12-09 12:00:00', $this->expiry($late));

        // Upgrade (type 3) starts a new period of the new plan.
        $upgrade = User::factory()->create();
        $this->subscribe($upgrade, $this->monthly, now()->addDays(20));
        CheckoutController::updateSubscription($this->transaction($upgrade, $this->yearly, Transaction::STATUS_PAID, 3));
        $this->assertSame($this->yearly->id, (int) $upgrade->subscription()->first()->plan_id);
        $this->assertSame('2027-11-09 12:00:00', $this->expiry($upgrade));
    }

    public function test_deleting_a_subscription_moves_the_user_to_the_free_plan()
    {
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, $this->monthly, now()->addDays(20));

        $this->admin()->delete(route('admin.subscriptions.destroy', $subscription->id))->assertRedirect();

        $subscription = $subscription->fresh();
        $this->assertNotNull($subscription, 'the user keeps a subscription');
        $this->assertSame($this->free->id, (int) $subscription->plan_id);
        $this->assertSame(Subscription::STATUS_ACTIVE, (int) $subscription->status);
        $this->assertSame($this->free->expiryFrom()->toDateTimeString(), $subscription->expiry_at->toDateTimeString());
        $this->assertTrue($user->fresh()->hasActiveService(), 'the apps can still connect');
    }

    public function test_deleting_a_suspended_subscription_keeps_the_suspension()
    {
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, $this->monthly, now()->addDays(20), Subscription::STATUS_CANCELLED);

        $this->admin()->delete(route('admin.subscriptions.destroy', $subscription->id))->assertRedirect();

        $subscription = $subscription->fresh();
        $this->assertSame($this->free->id, (int) $subscription->plan_id);
        $this->assertSame(Subscription::STATUS_CANCELLED, (int) $subscription->status);
        $this->assertFalse($user->fresh()->hasActiveService());
    }

    public function test_deleting_a_subscription_without_a_free_plan_still_deletes_it()
    {
        Plan::query()->update(['is_free' => 0]);
        $this->assertNull(freePlan());
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, $this->monthly, now()->addDays(20));

        $this->admin()->delete(route('admin.subscriptions.destroy', $subscription->id))->assertRedirect();

        $this->assertNull(Subscription::find($subscription->id));
    }

    public function test_cancelling_a_paid_transaction_ends_the_premium_it_paid_for()
    {
        $user = User::factory()->create();
        $this->subscribe($user, $this->monthly, now()->addDays(20));
        $older = $this->transaction($user, $this->monthly);
        $latest = $this->transaction($user, $this->monthly);

        $this->admin()->put(route('admin.transactions.update', $latest->id))->assertRedirect();

        $this->assertSame(Transaction::STATUS_CANCELLED, (int) $latest->fresh()->status);
        $this->assertSame(Transaction::STATUS_PAID, (int) $older->fresh()->status, 'other transactions are not touched');
        $subscription = $user->subscription()->first();
        $this->assertSame($this->free->id, (int) $subscription->plan_id);
        $this->assertSame($this->free->expiryFrom()->toDateTimeString(), $subscription->expiry_at->toDateTimeString());
        $this->assertFalse($user->fresh()->hasPremiumAccess());
    }

    public function test_cancelling_an_older_payment_on_the_same_plan_keeps_the_current_period()
    {
        $user = User::factory()->create();
        $this->subscribe($user, $this->monthly, now()->addDays(20));
        $older = $this->transaction($user, $this->monthly);
        $this->transaction($user, $this->monthly); // the payment of the current period

        $this->admin()->put(route('admin.transactions.update', $older->id))->assertRedirect();

        $this->assertSame(Transaction::STATUS_CANCELLED, (int) $older->fresh()->status);
        $this->assertSame($this->monthly->id, (int) $user->subscription()->first()->plan_id);
        $this->assertSame('2026-11-29 12:00:00', $this->expiry($user));
        $this->assertTrue($user->fresh()->hasPremiumAccess());
    }

    public function test_cancelling_an_old_transaction_keeps_a_subscription_on_another_plan()
    {
        $user = User::factory()->create();
        $this->subscribe($user, $this->yearly, now()->addDays(200));
        $old = $this->transaction($user, $this->monthly);

        $this->admin()->put(route('admin.transactions.update', $old->id))->assertRedirect();

        $this->assertSame(Transaction::STATUS_CANCELLED, (int) $old->fresh()->status);
        $this->assertSame($this->yearly->id, (int) $user->subscription()->first()->plan_id);
        $this->assertSame('2027-05-28 12:00:00', $this->expiry($user));
    }

    public function test_web_sign_up_creates_exactly_one_free_subscription()
    {
        $this->post('/register', [
            'firstname' => 'Web', 'lastname' => 'User', 'email' => 'web.user@example.com',
            'password' => 'secret-password', 'password_confirmation' => 'secret-password',
        ])->assertRedirect();

        $user = User::where('email', 'web.user@example.com')->firstOrFail();
        $subscriptions = Subscription::where('user_id', $user->id)->get();
        $this->assertCount(1, $subscriptions);
        $this->assertSame($this->free->id, (int) $subscriptions->first()->plan_id);
        $this->assertSame($this->free->expiryFrom()->toDateTimeString(), $subscriptions->first()->expiry_at->toDateTimeString());
    }

    public function test_facebook_sign_up_gets_the_free_plan()
    {
        $data = ['provider' => 'facebook', 'id' => 'fb-1234', 'firstname' => 'Face', 'lastname' => 'Book', 'email' => 'fb.user@example.com'];
        do {
            $token = encrypt($data); // a "/" in the token would not fit in the URL path
        } while (str_contains($token, '/'));

        $this->withSession(['provider_data' => $token])->post(route('complete.registration', $token), [
            'firstname' => 'Face', 'lastname' => 'Book', 'email' => 'fb.user@example.com',
            'password' => 'secret-password', 'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('user.settings.index'));

        $user = User::where('email', 'fb.user@example.com')->firstOrFail();
        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
        $this->assertSame($this->free->id, (int) $user->subscription->plan_id);
        $this->assertTrue($user->hasActiveService());
        $this->assertSame('fb-1234', SocialProvider::where('user_id', $user->id)->value('facebook'));
    }

    public function test_facebook_login_of_a_new_user_depends_on_registration_not_on_email_verification()
    {
        // setUp switched email verification off; registration is open (seed data).
        $socialUser = new \Laravel\Socialite\Two\User();
        $socialUser->id = 'fb-777';
        $socialUser->name = 'Face Book';
        $socialUser->email = 'new.fb@example.com';
        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('facebook')->andReturn($provider);

        $response = $this->get(route('provider.callback', 'facebook'))->assertRedirect();
        $this->assertStringContainsString('/register/complete/', (string) $response->headers->get('Location'));
        $this->assertNotNull(session('provider_data'));

        // Registration closed: back to the login page.
        $actions = Settings::where('key', 'actions')->first();
        $actions->value = array_merge((array) $actions->value, ['registration_status' => 0]);
        $actions->save();
        $this->get(route('provider.callback', 'facebook'))->assertRedirect(route('login'));
    }

    public function test_duplicate_subscription_rows_are_reduced_to_one_per_user()
    {
        $mixed = User::factory()->create();
        $this->subscribe($mixed, $this->free, now()->addYears(2));
        $this->subscribe($mixed, $this->monthly, now()->addDays(10));
        $keepPaid = $this->subscribe($mixed, $this->yearly, now()->addDays(100));
        $this->subscribe($mixed, $this->free, now()->addYear());

        $freeOnly = User::factory()->create();
        $this->subscribe($freeOnly, $this->free, now()->addYear());
        $keepNewest = $this->subscribe($freeOnly, $this->free, now());

        $single = User::factory()->create();
        $keepSingle = $this->subscribe($single, $this->monthly, now()->addDays(5));

        $this->assertSame(4, SubscriptionRepair::removeDuplicates());
        $this->assertSame(0, SubscriptionRepair::removeDuplicates(), 'nothing left to do');

        $this->assertSame([$keepPaid->id], Subscription::where('user_id', $mixed->id)->pluck('id')->map(fn($id) => (int) $id)->all());
        $this->assertSame([$keepNewest->id], Subscription::where('user_id', $freeOnly->id)->pluck('id')->map(fn($id) => (int) $id)->all());
        $this->assertSame([$keepSingle->id], Subscription::where('user_id', $single->id)->pluck('id')->map(fn($id) => (int) $id)->all());
    }

    public function test_users_without_a_subscription_row_get_the_free_plan()
    {
        $missing = User::factory()->count(2)->create();
        $subscribed = User::factory()->create();
        $this->subscribe($subscribed, $this->monthly, now()->addDays(5));

        $this->assertSame(2, SubscriptionRepair::addMissingFreeSubscriptions());
        $this->assertSame(0, SubscriptionRepair::addMissingFreeSubscriptions());

        foreach ($missing as $user) {
            $subscription = $user->subscription()->sole();
            $this->assertSame($this->free->id, (int) $subscription->plan_id);
            $this->assertSame($this->free->expiryFrom()->toDateTimeString(), $subscription->expiry_at->toDateTimeString());
        }
        $this->assertSame($this->monthly->id, (int) $subscribed->subscription()->sole()->plan_id);
    }

    public function test_paid_plan_can_be_created_without_a_web_gateway_and_product_ids_are_optional()
    {
        // Only the App Store row is active (and supports the currency): it does not count.
        PaymentGateway::query()->update(['status' => 0]);
        PaymentGateway::where('alias', 'appstore')->update(['status' => 1, 'supported_currencies' => json_encode(['USD'])]);

        $this->admin()->post(route('admin.plans.store'), [
            'name' => 'Basic', 'product_id' => '', 'interval' => '1', 'price' => '4.99',
        ])->assertRedirect(route('admin.plans.index'));

        $plan = Plan::where('name', 'Basic')->firstOrFail();
        $this->assertNull($plan->product_id);
        $this->assertSame(0, (int) $plan->is_free);
        $this->assertSame([], $plan->productIds());

        $this->admin()->put(route('admin.plans.update', $plan->id), [
            'name' => 'Basic', 'product_id' => ' basic_monthly, basic.monthly.ios ', 'interval' => '1', 'price' => '4.99',
        ])->assertRedirect();
        $this->assertSame(['basic_monthly', 'basic.monthly.ios'], $plan->fresh()->productIds());
    }

    public function test_checkout_never_offers_the_in_app_purchase_gateways()
    {
        PaymentGateway::whereIn('alias', ['appstore', 'playstore'])->update(['status' => 1]);
        $user = User::factory()->create();
        $this->subscribe($user, $this->free, now()->addYear());
        $transaction = $this->transaction($user, $this->monthly, Transaction::STATUS_UNPAID);

        $this->actingAs($user)->get(route('checkout.index', $transaction->checkout_id))->assertOk()
            ->assertSee('id="paypal_express"', false)
            ->assertDontSee('id="appstore"', false)
            ->assertDontSee('id="playstore"', false);

        // Posting the App Store row anyway is refused. The redirect goes back to the
        // checkout page, which shows the error (toastr).
        $this->actingAs($user)->followingRedirects()->post(route('checkout.proccess', $transaction->checkout_id), [
            'address_1' => 'Street 1', 'city' => 'Dhaka', 'state' => 'Dhaka', 'zip' => '1000',
            'country' => Country::firstOrFail()->id,
            'payment_method' => PaymentGateway::where('alias', 'appstore')->value('id'),
        ])->assertOk()->assertSee('Selected payment method is not active');
        $this->assertSame(Transaction::STATUS_UNPAID, (int) $transaction->fresh()->status);
    }

    public function test_about_to_expire_reminder_is_sent_once_per_expiry_date()
    {
        $this->assertTrue(collect(app(Schedule::class)->events())
            ->contains(fn($event) => str_contains((string) $event->command, 'subscriptions:send-reminders')));

        // Settings > General: remind 3 days before (seed data).
        $soon = User::factory()->create();
        $soonSubscription = $this->subscribe($soon, $this->monthly, now()->addDays(2));
        $later = User::factory()->create();
        $this->subscribe($later, $this->monthly, now()->addDays(10));
        $free = User::factory()->create();
        $this->subscribe($free, $this->free, now()->addDay());
        $cancelled = User::factory()->create();
        $this->subscribe($cancelled, $this->monthly, now()->addDay(), Subscription::STATUS_CANCELLED);
        $store = User::factory()->create(); // renews through Google Play by itself
        $this->subscribe($store, $this->monthly, now()->addDay());
        $this->storePurchase($store, $this->monthly, now()->addDay());

        $this->assertSame(0, Artisan::call('subscriptions:send-reminders'));
        $this->assertSame(0, Artisan::call('subscriptions:send-reminders'));

        Notification::assertSentToTimes($soon, SubscriptionAboutToExpiredNotification::class, 1);
        Notification::assertNotSentTo([$later, $free, $cancelled, $store], SubscriptionAboutToExpiredNotification::class);

        // A new expiry date (renewal) may be reminded again.
        $soonSubscription->fresh()->update(['expiry_at' => now()->addDays(2)->addHours(6)]);
        $this->assertSame(0, Artisan::call('subscriptions:send-reminders'));
        Notification::assertSentToTimes($soon, SubscriptionAboutToExpiredNotification::class, 2);

        // The mail template can be switched off.
        MailTemplate::where('alias', 'subscription_about_expired')->update(['status' => 0]);
        $off = User::factory()->create();
        $this->subscribe($off, $this->monthly, now()->addDay());
        $this->assertSame(0, Artisan::call('subscriptions:send-reminders'));
        Notification::assertNotSentTo($off, SubscriptionAboutToExpiredNotification::class);
    }

    public function test_expired_paid_plan_moves_to_free_and_mails_once()
    {
        $paid = User::factory()->create();
        $this->subscribe($paid, $this->monthly, now()->subHour());
        $free = User::factory()->create();
        $this->subscribe($free, $this->free, now()->subHour());
        $suspended = User::factory()->create();
        $this->subscribe($suspended, $this->monthly, now()->subHour(), Subscription::STATUS_CANCELLED);
        $store = User::factory()->create();
        $this->subscribe($store, $this->monthly, now()->subHour());
        $this->storePurchase($store, $this->monthly, now()->subHour());
        // A store purchase of another plan does not count.
        $otherPlan = User::factory()->create();
        $this->subscribe($otherPlan, $this->monthly, now()->subHour());
        $this->storePurchase($otherPlan, $this->yearly, now()->addMonth());

        $this->assertSame(0, Artisan::call('subscriptions:update-expired'));
        $this->assertSame(0, Artisan::call('subscriptions:update-expired'));

        $this->assertSame($this->free->id, (int) $paid->subscription()->first()->plan_id);
        $this->assertSame($this->free->id, (int) $free->subscription()->first()->plan_id);
        Notification::assertSentToTimes($paid, SubscriptionExpiredNotification::class, 1);
        // The mail names the plan and date that ended, not the free plan.
        Notification::assertSentTo($paid, SubscriptionExpiredNotification::class, function ($notification) {
            return $notification->subscription->plan->name === $this->monthly->name
                && $notification->subscription->expiry_at->toDateTimeString() === '2026-11-09 11:00:00';
        });
        Notification::assertNotSentTo([$free, $suspended, $store], SubscriptionExpiredNotification::class);
        Notification::assertSentToTimes($otherPlan, SubscriptionExpiredNotification::class, 1);
        // Everyone expired is on the free plan, mail or not.
        foreach ([$suspended, $store, $otherPlan] as $user) {
            $this->assertSame($this->free->id, (int) $user->subscription()->first()->plan_id);
        }
    }

    public function test_changing_the_password_signs_the_client_apps_out()
    {
        $user = User::factory()->create();
        $this->subscribe($user, $this->free, now()->addYear());
        VpnSession::start($user, 'Windows PC', null);
        $other = User::factory()->create();
        VpnSession::start($other, 'Not mine', null);

        $this->actingAs($user)->post(route('user.settings.password.update'), [
            'current-password' => 'password',
            'new-password' => 'new-secret-1',
            'new-password_confirmation' => 'new-secret-1',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-secret-1', $user->fresh()->password));
        $this->assertSame(0, VpnSession::where('user_id', $user->id)->count());
        $this->assertSame(1, VpnSession::where('user_id', $other->id)->count());
    }

    public function test_resetting_the_password_by_mail_signs_the_client_apps_out()
    {
        $user = User::factory()->create();
        VpnSession::start($user, 'Windows PC', null);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secret-2',
            'password_confirmation' => 'new-secret-2',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-secret-2', $user->fresh()->password));
        $this->assertSame(0, VpnSession::where('user_id', $user->id)->count());
    }
}
