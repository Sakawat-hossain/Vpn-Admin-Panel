<?php

namespace Tests\Feature;

use App\Jobs\RevokeWireGuardPeer;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\Facades\Queue;

class AuthApiTest extends ApiTestCase
{
    public function test_register_response_does_not_leak_code_or_token_when_verification_required()
    {
        $this->setEmailVerification(true);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertOk();

        $this->assertArrayNotHasKey('verification_code', $response->json('data'));
        $this->assertArrayNotHasKey('api_token', $response->json('data'));
        $user = User::where('email', 'alice@example.com')->first();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $user->verification_code);
        $this->assertNotNull($user->subscription);
        $this->assertSame($this->freePlan->id, $user->subscription->plan_id);
    }

    public function test_login_rejects_unverified_and_banned_users()
    {
        $this->setEmailVerification(true);
        $this->makeUser(['email' => 'u@example.com', 'email_verified_at' => null]);
        $this->postJson('/api/v1/auth/login', ['email' => 'u@example.com', 'password' => 'password'])
            ->assertStatus(403)->assertJsonPath('data.verification_required', true);

        $this->makeUser(['email' => 'b@example.com', 'status' => 0]);
        $this->postJson('/api/v1/auth/login', ['email' => 'b@example.com', 'password' => 'password'])
            ->assertStatus(403);

        $ok = $this->makeUser(['email' => 'ok@example.com']);
        $this->postJson('/api/v1/auth/login', ['email' => 'ok@example.com', 'password' => 'password'])
            ->assertOk()->assertJsonPath('data.token', $ok->api_token);
    }

    public function test_banned_users_token_stops_working()
    {
        $user = $this->makeUser(['status' => 0]);
        $this->asUser($user)->getJson('/api/v1/profiles')->assertStatus(403);
    }

    public function test_verify_code_expires_and_is_rate_limited()
    {
        $user = $this->makeUser(['email' => 'v@example.com', 'email_verified_at' => null]);
        $user->forceFill(['verification_code' => '123456', 'verification_code_sent_at' => now()->subHours(2)])->save();

        $this->postJson('/api/v1/auth/verify', ['email' => 'v@example.com', 'verification_code' => '123456'])
            ->assertStatus(422);

        $user->forceFill(['verification_code_sent_at' => now()])->save();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/verify', ['email' => 'v@example.com', 'verification_code' => '000000'])->assertStatus(422);
        }
        // Even the right code is refused once the attempt limit is hit.
        $this->postJson('/api/v1/auth/verify', ['email' => 'v@example.com', 'verification_code' => '123456'])
            ->assertStatus(429);
    }

    public function test_reset_password_rotates_token()
    {
        $user = $this->makeUser(['email' => 'r@example.com']);
        $oldToken = $user->api_token;
        $user->forceFill(['verification_code' => '654321', 'verification_code_sent_at' => now()])->save();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'r@example.com',
            'verification_code' => '654321',
            'new_password' => 'newsecret',
            'new_password_confirmation' => 'newsecret',
        ])->assertOk();

        $newToken = $response->json('data.api_token');
        $this->assertNotEmpty($newToken);
        $this->assertNotSame($oldToken, $newToken);
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withHeaders(['Authorization' => 'Bearer ' . $oldToken, 'Accept' => 'application/json'])
            ->getJson('/api/v1/profiles')->assertStatus(401);
    }

    public function test_update_profile_only_changes_allowed_fields()
    {
        $user = $this->makeUser(['status' => 1]);
        $email = $user->email;

        $this->asUser($user)->postJson('/api/v1/profiles', [
            'name' => 'New Name',
            'email' => 'attacker@example.com',
            'status' => 0,
            'email_verified_at' => null,
            'email_token' => 'chosen',
            'avatar' => '../.env',
            'dns' => '9.9.9.9',
        ])->assertOk();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('9.9.9.9', $user->dns);
        $this->assertSame($email, $user->email);
        $this->assertNotSame('chosen', $user->email_token);
        $this->assertSame('images/avatars/default.png', $user->avatar);
        $this->assertNotNull($user->email_verified_at);

        $this->asUser($user)->postJson('/api/v1/profiles', ['dns' => '1.1.1.1/../../x'])->assertStatus(422);
    }

    public function test_users_can_only_delete_their_own_account()
    {
        Queue::fake();
        $alice = $this->makeUser();
        $bob = $this->makeUser();

        // The app key alone no longer deletes accounts.
        $this->flushHeaders()->withHeaders(['x-api-key' => self::API_KEY, 'Accept' => 'application/json'])
            ->deleteJson("/api/v1/users/{$bob->id}")->assertStatus(401);
        $this->asUser($alice)->deleteJson("/api/v1/users/{$bob->id}")->assertStatus(403);
        $this->assertNotNull(User::find($bob->id));

        $this->asUser($alice)->deleteJson("/api/v1/users/{$alice->id}")->assertOk();
        $this->assertNull(User::find($alice->id));
    }

    public function test_users_can_only_delete_their_own_logs()
    {
        $alice = $this->makeUser();
        $bob = $this->makeUser();
        $bobLog = UserLog::forceCreate(['user_id' => $bob->id, 'ip' => '198.51.100.1']);

        $this->asUser($alice)->deleteJson("/api/v1/userLogs/{$bobLog->id}")->assertStatus(404);
        $this->assertNotNull(UserLog::find($bobLog->id));

        $this->asUser($bob)->deleteJson("/api/v1/userLogs/{$bobLog->id}")->assertOk();
        $this->assertNull(UserLog::find($bobLog->id));
    }

    public function test_plans_endpoint_works()
    {
        $user = $this->makeUser();
        $this->asUser($user)->getJson('/api/v1/plans')->assertOk()->assertJsonCount(2, 'data');
    }
}
