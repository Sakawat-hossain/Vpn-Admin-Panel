<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Server;
use App\Models\Settings;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Plan $freePlan;
    private Plan $premiumPlan;

    protected function setUp(): void
    {
        parent::setUp();
        // Web pages need the stock settings, languages, extensions, etc.
        $this->seed(DatabaseSeeder::class);
        Server::query()->delete();
        $actions = Settings::where('key', 'actions')->first();
        $actions->value = array_merge((array) $actions->value, ['email_verification_status' => 0]);
        $actions->save();
        // Skip the ip-api.com lookup the frontend layout does for the visitor's IP.
        Cache::put('ipinfo:127.0.0.1', (object) ['status' => 'success', 'query' => '127.0.0.1', 'countryCode' => 'US'], now()->addHour());

        $this->freePlan = Plan::create(['name' => 'Free', 'product_id' => 'free', 'interval' => 1, 'price' => 0, 'is_free' => 1]);
        $this->premiumPlan = Plan::create(['name' => 'Pro', 'product_id' => 'pro', 'interval' => 1, 'price' => 9.99, 'is_free' => 0]);
    }

    private function makeUser(bool $premium = false, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $premium ? $this->premiumPlan->id : $this->freePlan->id,
            'expiry_at' => now()->addDays(20),
            'status' => 1,
        ]);
        return $user;
    }

    private function makeServer(array $attributes = []): Server
    {
        return Server::create(array_merge([
            'country' => 'Japan',
            'state' => 'Tokyo',
            'latitude' => '35.6',
            'longitude' => '139.6',
            'status' => 1,
            'ip_address' => '203.0.113.20',
            'recommended' => 0,
            'is_premium' => 0,
            'is_ovpn' => 0,
            'ovpn_config' => '',
        ], $attributes));
    }

    public function test_guests_are_sent_to_login()
    {
        $this->get('/user/dashboard')->assertRedirect(route('login'));
    }

    public function test_user_root_redirects_to_dashboard()
    {
        $this->actingAs($this->makeUser())->get('/user')->assertRedirect(route('user.dashboard'));
    }

    public function test_dashboard_shows_plan_and_servers()
    {
        $japan = $this->makeServer(['country' => 'Japan', 'is_ovpn' => 1, 'ovpn_config' => "client\nremote jp 1194"]);
        $germany = $this->makeServer(['country' => 'Germany', 'state' => 'Berlin', 'is_premium' => 1, 'is_ovpn' => 1, 'ovpn_config' => 'client']);
        $this->makeServer(['country' => 'Singapore', 'state' => 'Singapore', 'status' => 0]);
        $user = $this->makeUser(true);

        $response = $this->actingAs($user)->get('/user/dashboard')->assertOk();

        $response->assertSee('Pro')
            ->assertSee('Japan')
            ->assertSee('Germany')
            ->assertDontSee('Singapore')
            ->assertSee('fi fi-jp', false)
            ->assertSee(route('user.servers.openvpn', $japan->id), false)
            ->assertSee(route('user.servers.openvpn', $germany->id), false)
            ->assertSee('2 / 2');
        // The profile itself is never rendered into the page.
        $response->assertDontSee('remote jp 1194');
    }

    public function test_free_user_sees_upgrade_instead_of_premium_download()
    {
        $free = $this->makeServer(['is_ovpn' => 1, 'ovpn_config' => 'client']);
        $premium = $this->makeServer(['country' => 'Germany', 'is_premium' => 1, 'is_ovpn' => 1, 'ovpn_config' => 'client']);

        $this->actingAs($this->makeUser())->get('/user/dashboard')->assertOk()
            ->assertSee(route('user.servers.openvpn', $free->id), false)
            ->assertDontSee(route('user.servers.openvpn', $premium->id), false)
            ->assertSee('1 / 2');
    }

    public function test_entitled_user_downloads_ovpn_profile()
    {
        $server = $this->makeServer(['is_premium' => 1, 'is_ovpn' => 1, 'ovpn_config' => "client\nremote jp 1194"]);

        $response = $this->actingAs($this->makeUser(true))->get(route('user.servers.openvpn', $server->id));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/x-openvpn-profile');
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.ovpn', $response->headers->get('Content-Disposition'));
        $this->assertSame("client\nremote jp 1194", $response->getContent());
    }

    public function test_ovpn_download_is_refused_when_not_entitled_or_unavailable()
    {
        $premium = $this->makeServer(['is_premium' => 1, 'is_ovpn' => 1, 'ovpn_config' => 'client']);
        $wireguard = $this->makeServer();
        $disabled = $this->makeServer(['status' => 0, 'is_ovpn' => 1, 'ovpn_config' => 'client']);
        $empty = $this->makeServer(['is_ovpn' => 1, 'ovpn_config' => ' ']);
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('user.servers.openvpn', $premium->id))->assertForbidden();
        $this->actingAs($user)->get(route('user.servers.openvpn', $wireguard->id))->assertNotFound();
        $this->actingAs($user)->get(route('user.servers.openvpn', $disabled->id))->assertNotFound();
        $this->actingAs($user)->get(route('user.servers.openvpn', $empty->id))->assertNotFound();
    }

    public function test_expired_premium_user_cannot_download_premium_profile()
    {
        $server = $this->makeServer(['is_premium' => 1, 'is_ovpn' => 1, 'ovpn_config' => 'client']);
        $user = $this->makeUser(true);
        $user->subscription->update(['expiry_at' => now()->subDay()]);

        $this->actingAs($user)->get('/user/dashboard')->assertOk()->assertSee('Expired');
        $this->actingAs($user)->get(route('user.servers.openvpn', $server->id))->assertForbidden();
    }

    public function test_checkout_and_payment_history_render()
    {
        $user = $this->makeUser();
        $details = ['price' => 9.99, 'tax' => 0, 'total' => 9.99];
        \App\Models\Transaction::create([
            'checkout_id' => 'chk_unpaid', 'user_id' => $user->id, 'plan_id' => $this->premiumPlan->id,
            'details_before_discount' => $details, 'details_after_discount' => $details,
            'price' => 9.99, 'tax' => 0, 'fees' => 0, 'total' => 9.99, 'type' => 1, 'status' => 0,
        ]);
        \App\Models\Transaction::create([
            'checkout_id' => 'chk_paid', 'user_id' => $user->id, 'plan_id' => $this->premiumPlan->id,
            'details_before_discount' => $details, 'details_after_discount' => $details,
            'price' => 9.99, 'tax' => 0, 'fees' => 0, 'total' => 9.99, 'type' => 1, 'status' => 2,
        ]);

        // CheckoutController filters gateways with JSON_CONTAINS (MySQL only), so
        // render the view directly instead of going through the route.
        $this->actingAs($user);
        $html = view('frontend.user.checkout', [
            'user' => $user,
            'transaction' => \App\Models\Transaction::where('checkout_id', 'chk_unpaid')->first(),
            'paymentGateways' => \App\Models\PaymentGateway::where('status', 1)->get(),
        ])->render();
        $this->assertStringContainsString('id="checkoutForm"', $html);
        $this->assertStringContainsString('Pro Plan', $html);
        $this->actingAs($user)->get(route('user.settings.payment-history'))->assertOk()
            ->assertSee('bg-success-lt', false);
        $this->actingAs($user)->get('/user/dashboard')->assertOk()
            ->assertSee('bg-success-lt', false);
    }

    public function test_settings_pages_render_in_new_layout()
    {
        $user = $this->makeUser(true);
        foreach (['user.settings.index', 'user.settings.subscription', 'user.settings.payment-history', 'user.settings.password', 'user.settings.2fa'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()
                ->assertSee('tabler.min.css', false)
                ->assertSee(route('user.dashboard'), false);
        }
    }
}
