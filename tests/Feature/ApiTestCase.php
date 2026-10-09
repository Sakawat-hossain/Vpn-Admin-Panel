<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Server;
use App\Models\Settings;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected const API_KEY = 'test-fast-api-key';

    protected Plan $freePlan;
    protected Plan $premiumPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freePlan = Plan::create(['name' => 'Free', 'product_id' => 'free', 'interval' => 1, 'price' => 0, 'is_free' => 1]);
        $this->premiumPlan = Plan::create(['name' => 'Pro', 'product_id' => 'pro_monthly, pro.monthly.ios', 'interval' => 1, 'price' => 9.99, 'is_free' => 0]);
        $this->setEmailVerification(false);
    }

    protected function setEmailVerification(bool $required): void
    {
        $setting = Settings::where('key', 'actions')->first() ?: new Settings();
        $setting->forceFill(['key' => 'actions', 'value' => ['email_verification_status' => $required ? 1 : 0]])->save();
    }

    protected function makeUser(array $attributes = [], bool $premium = false): User
    {
        $user = User::factory()->create($attributes);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $premium ? $this->premiumPlan->id : $this->freePlan->id,
            'expiry_at' => now()->addMonth(),
            'status' => 1,
        ]);
        return $user;
    }

    protected function makeServer(array $attributes = []): Server
    {
        return Server::create(array_merge([
            'country' => 'Germany',
            'state' => 'Berlin',
            'latitude' => '52.5',
            'longitude' => '13.4',
            'status' => 1,
            'ip_address' => '203.0.113.10',
            'recommended' => 0,
            'is_premium' => 0,
            'is_ovpn' => 0,
            'ovpn_config' => '',
        ], $attributes));
    }

    protected function asUser(User $user, bool $withAppKey = false): self
    {
        // Each call starts a fresh "client": no leftover headers, no cached guard user.
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $headers = ['Authorization' => 'Bearer ' . $user->api_token, 'Accept' => 'application/json'];
        if ($withAppKey) {
            $headers['x-api-key'] = self::API_KEY;
        }
        return $this->withHeaders($headers);
    }
}
