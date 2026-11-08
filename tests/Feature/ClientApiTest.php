<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\Settings;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserLog;
use App\Models\VpnSession;
use App\Services\OpenVpnPki;
use Illuminate\Support\Facades\File;

class ClientApiTest extends ApiTestCase
{
    private string $pkiDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pkiDir = sys_get_temp_dir() . '/ovpn-pki-' . uniqid();
        $this->app->instance(OpenVpnPki::class, new OpenVpnPki($this->pkiDir));
        $this->setGeneral(['site_name' => 'TestVPN', 'active_device_limit' => 0]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pkiDir);
        parent::tearDown();
    }

    private function setGeneral(array $values): void
    {
        $setting = Settings::where('key', 'general')->first() ?: new Settings();
        $setting->forceFill(['key' => 'general', 'value' => $values])->save();
    }

    private function login(User $user, array $extra = [])
    {
        return $this->flushHeaders()->postJson('/api/client/v4/session/new', array_merge([
            'username' => $user->email,
            'password' => 'password',
        ], $extra));
    }

    private function managedServer(array $attributes = []): Server
    {
        $server = $this->makeServer(array_merge(['is_ovpn' => 1, 'ovpn_managed' => 1, 'country' => 'Japan', 'state' => 'Tokyo'], $attributes));
        $server->forceFill(['ovpn_secret' => 'server-secret-' . $server->id])->save();
        $this->deployJob($server, 'success');
        return $server;
    }

    private function deployJob(Server $server, string $status): void
    {
        (new \App\Models\ConfigServerJob())->forceFill([
            'server_id' => $server->id, 'ip' => $server->ip_address, 'ssh_port' => 22,
            'vps_username' => 'root', 'vps_password' => '', 'status' => $status,
        ])->save();
    }

    private function auth(Server $server, string $username, string $password, ?string $secret = null)
    {
        return $this->flushHeaders()->withHeaders([
            'X-Server-Id' => $server->id,
            'Authorization' => 'Bearer ' . ($secret ?? 'server-secret-' . $server->id),
        ])->postJson('/api/client/openvpn/auth', ['username' => $username, 'password' => $password]);
    }

    public function test_login_rejects_wrong_password()
    {
        $user = $this->makeUser();
        $this->login($user, ['password' => 'wrong'])->assertJson(['status' => 401]);
        $this->assertSame(0, VpnSession::count());
    }

    public function test_login_creates_a_session_with_vpn_credentials()
    {
        $user = $this->makeUser([], true);

        $response = $this->login($user, ['wg_public_key' => 'abc='])->assertOk()->assertJson([
            'status' => 200,
            'service_status' => ['is_active' => true, 'capabilities' => ['premium'], 'upgradable' => false],
            'wireguard' => ['status' => 400],
        ]);

        $session = VpnSession::first();
        $this->assertSame($user->id, $session->user_id);
        $this->assertSame($session->vpn_username, $response->json('vpn_username'));
        $this->assertTrue($session->checkVpnPassword($response->json('vpn_password')));
        $this->assertSame($session->id, VpnSession::findByToken($response->json('token'))->id);
        // Secrets are only stored hashed.
        $this->assertNotSame($response->json('token'), $session->token_hash);
    }

    public function test_banned_and_unverified_users_cannot_log_in()
    {
        $banned = $this->makeUser(['status' => 0]);
        $this->login($banned)->assertJson(['status' => 401]);

        $this->setEmailVerification(true);
        $unverified = $this->makeUser(['email_verified_at' => null]);
        $this->login($unverified)->assertJson(['status' => 702]);
    }

    public function test_two_factor_code_is_required_when_enabled()
    {
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();
        $user = $this->makeUser(['google2fa_status' => 1, 'google2fa_secret' => encrypt($secret)]);

        $this->login($user)->assertJson(['status' => 70011]);
        $this->login($user, ['confirmation' => '000000'])->assertJson(['status' => 70012]);
        $this->login($user, ['confirmation' => $google2fa->getCurrentOtp($secret)])->assertJson(['status' => 200]);
    }

    public function test_device_limit_and_force_login()
    {
        $this->setGeneral(['site_name' => 'TestVPN', 'active_device_limit' => 1]);
        $user = $this->makeUser();

        $first = $this->login($user)->assertJson(['status' => 200])->json('token');
        $this->login($user)->assertJson(['status' => 602, 'data' => ['limit' => 1]]);
        $this->login($user, ['force' => true])->assertJson(['status' => 200]);

        $this->assertSame(1, VpnSession::count());
        $this->assertNull(VpnSession::findByToken($first));
    }

    public function test_long_unused_devices_are_forgotten_at_the_next_login()
    {
        $this->setGeneral(['site_name' => 'TestVPN', 'active_device_limit' => 1]);
        $user = $this->makeUser();
        $this->login($user)->assertJson(['status' => 200]);
        VpnSession::query()->update(['last_seen_at' => now()->subDays(VpnSession::STALE_AFTER_DAYS + 1)]);

        $this->login($user)->assertJson(['status' => 200]);
        $this->assertSame(1, VpnSession::count());
    }

    public function test_session_status_and_delete()
    {
        $user = $this->makeUser();
        $token = $this->login($user)->json('token');

        $this->postJson('/api/client/v4/session/status', ['session_token' => $token])
            ->assertJson(['status' => 200, 'service_status' => ['current_plan' => 'Free']]);
        $this->postJson('/api/client/v4/session/status', ['session_token' => 'nope'])->assertJson(['status' => 601]);

        $this->postJson('/api/client/v4/session/delete', ['session_token' => $token])->assertJson(['status' => 200]);
        $this->postJson('/api/client/v4/session/status', ['session_token' => $token])->assertJson(['status' => 601]);
    }

    public function test_device_name_comes_from_the_app_or_falls_back_to_desktop_app()
    {
        $user = $this->makeUser();

        $this->withHeaders(['User-Agent' => 'Go-http-client/1.1'])
            ->postJson('/api/client/v4/session/new', ['username' => $user->email, 'password' => 'password'])
            ->assertJson(['status' => 200, 'device_name' => 'Desktop app']);
        $this->flushHeaders()->withHeaders(['User-Agent' => 'Go-http-client/2.0'])
            ->postJson('/api/client/v4/session/new', ['username' => $user->email, 'password' => 'password'])
            ->assertJson(['status' => 200, 'device_name' => 'Desktop app']);
        // The name the app sends wins over the User-Agent.
        $this->flushHeaders()->withHeaders(['User-Agent' => 'Go-http-client/1.1'])
            ->postJson('/api/client/v4/session/new', ['username' => $user->email, 'password' => 'password', 'device_name' => 'DESKTOP-1AB2C (Windows)'])
            ->assertJson(['status' => 200, 'device_name' => 'DESKTOP-1AB2C (Windows)']);
        // Other clients keep their User-Agent.
        $this->flushHeaders()->withHeaders(['User-Agent' => 'SomeClient/3.0'])
            ->postJson('/api/client/v4/session/new', ['username' => $user->email, 'password' => 'password'])
            ->assertJson(['status' => 200, 'device_name' => 'SomeClient/3.0']);

        $this->assertSame(
            ['Desktop app', 'Desktop app', 'DESKTOP-1AB2C (Windows)', 'SomeClient/3.0'],
            VpnSession::orderBy('id')->pluck('device_name')->all()
        );
    }

    public function test_app_login_is_written_to_the_login_history()
    {
        $user = $this->makeUser();

        $this->login($user, ['device_name' => 'DESKTOP-1AB2C (Windows)'])->assertJson(['status' => 200]);

        $log = UserLog::where('user_id', $user->id)->sole();
        $this->assertSame('127.0.0.1', $log->ip);
        $this->assertSame('VPN app', $log->browser);
        $this->assertSame('Windows', $log->os);
        $this->assertSame('Japan', $log->country);

        // A second sign-in from the same IP updates that row, like the website login.
        $this->login($user, ['device_name' => 'MacBook (macOS)'])->assertJson(['status' => 200]);
        $this->assertSame(1, UserLog::where('user_id', $user->id)->count());
        $this->assertSame('macOS', UserLog::where('user_id', $user->id)->sole()->os);

        // Failed logins are not logged.
        $other = $this->makeUser();
        $this->login($other, ['password' => 'wrong'])->assertJson(['status' => 401]);
        $this->assertSame(0, UserLog::where('user_id', $other->id)->count());
    }

    public function test_server_list_needs_app_key_and_lists_managed_openvpn_servers_only()
    {
        $managed = $this->managedServer(['is_premium' => 1]);
        $this->managedServer(['status' => 0]);
        $this->makeServer(['is_ovpn' => 1, 'ovpn_config' => 'client']);
        $this->makeServer();

        $this->getJson('/api/client/v5/servers.json')->assertStatus(403);
        config(['services.openvpn.api_ips' => '203.0.113.7, 2001:db8::1']);

        $response = $this->withHeaders(['x-api-key' => self::API_KEY])->getJson('/api/client/v5/servers.json')->assertOk();
        $response->assertJsonCount(1, 'openvpn')
            ->assertJsonCount(0, 'wireguard')
            ->assertJsonPath('openvpn.0.country_code', 'JP')
            ->assertJsonPath('openvpn.0.is_premium', true)
            ->assertJsonPath('openvpn.0.hosts.0.host', $managed->ip_address)
            ->assertJsonPath('config.ports.openvpn.0', ['type' => 'UDP', 'port' => 1194])
            ->assertJsonPath('openvpn.0.hosts.0.hostname', 'server-' . $managed->id . '.gw')
            ->assertJsonPath('config.api.ips', ['203.0.113.7'])
            ->assertJsonPath('config.api.ipv6s', ['2001:db8::1']);
        $this->assertStringContainsString('BEGIN CERTIFICATE', $response->json('config.openvpn.ca'));
        $this->assertStringContainsString('BEGIN OpenVPN Static key V1', $response->json('config.openvpn.tls_auth'));
        $this->assertSame('tls-crypt', $response->json('config.openvpn.tls_mode'));
        $this->assertStringNotContainsString('server-secret', $response->getContent());
    }

    public function test_server_list_leaves_out_servers_whose_latest_deploy_failed()
    {
        $ok = $this->managedServer(['state' => 'Osaka']);
        $broken = $this->managedServer(['state' => 'Tokyo']);
        $this->deployJob($broken, 'failed');          // a redeploy failed after an earlier success
        $never = $this->makeServer(['is_ovpn' => 1, 'ovpn_managed' => 1, 'country' => 'Japan', 'state' => 'Kyoto']);
        $this->deployJob($never, 'running');

        $response = $this->withHeaders(['x-api-key' => self::API_KEY])->getJson('/api/client/v5/servers.json')->assertOk();
        $this->assertSame(['Osaka'], array_column($response->json('openvpn'), 'city'));
        $this->assertSame('server-' . $ok->id, $response->json('openvpn.0.gateway'));
    }

    public function test_server_list_groups_servers_of_one_city_into_one_location()
    {
        $premium = $this->managedServer(['is_premium' => 1, 'country' => 'Bangladesh', 'state' => 'Dhaka District']);
        $free = $this->managedServer(['is_premium' => 0, 'country' => 'Bangladesh', 'state' => 'Dhaka District']);
        $tokyo = $this->managedServer(['is_premium' => 0]);

        $response = $this->withHeaders(['x-api-key' => self::API_KEY])->getJson('/api/client/v5/servers.json')->assertOk();
        $response->assertJsonCount(2, 'openvpn');

        $locations = collect($response->json('openvpn'))->keyBy('city');
        $dhaka = $locations['Dhaka District'];
        $this->assertSame('server-' . $premium->id, $dhaka['gateway']);
        $this->assertSame('BD', $dhaka['country_code']);
        $this->assertFalse($dhaka['is_premium'], 'a location with a free host is not premium');
        $this->assertCount(2, $dhaka['hosts']);
        $this->assertSame(['Dhaka District #1', 'Dhaka District #2'], array_column($dhaka['hosts'], 'name'));
        $this->assertSame([true, false], array_column($dhaka['hosts'], 'is_premium'));
        $this->assertSame(['server-' . $premium->id . '.gw', 'server-' . $free->id . '.gw'], array_column($dhaka['hosts'], 'hostname'));
        $this->assertSame([$premium->ip_address, $free->ip_address], array_column($dhaka['hosts'], 'host'));

        $single = $locations['Tokyo'];
        $this->assertSame('server-' . $tokyo->id, $single['gateway']);
        $this->assertSame('Tokyo', $single['hosts'][0]['name']);
        $this->assertFalse($single['is_premium']);
    }

    public function test_openvpn_auth_checks_the_server_secret()
    {
        $server = $this->managedServer();
        $user = $this->makeUser();

        $this->auth($server, $user->email, 'password', 'wrong-secret')->assertStatus(403);
        $this->flushHeaders()->withHeaders(['Authorization' => 'Bearer server-secret-' . $server->id])
            ->postJson('/api/client/openvpn/auth', ['username' => $user->email, 'password' => 'password'])->assertStatus(403);
        $this->auth($server, '__panel_check__', 'x')->assertNoContent();
    }

    public function test_openvpn_auth_accepts_session_credentials_and_account_password()
    {
        $server = $this->managedServer();
        $user = $this->makeUser();
        $login = $this->login($user);

        $this->auth($server, $login->json('vpn_username'), $login->json('vpn_password'))->assertNoContent();
        $this->auth($server, $login->json('vpn_username'), 'wrong')->assertStatus(401);
        $this->auth($server, $user->email, 'password')->assertNoContent();
        $this->auth($server, $user->email, 'wrong')->assertStatus(401);

        // Signing the device out revokes its VPN password.
        VpnSession::query()->delete();
        $this->auth($server, $login->json('vpn_username'), $login->json('vpn_password'))->assertStatus(401);
    }

    public function test_openvpn_auth_enforces_premium_ban_and_server_status()
    {
        $premiumServer = $this->managedServer(['is_premium' => 1]);
        $free = $this->makeUser();
        $premium = $this->makeUser([], true);

        $this->auth($premiumServer, $free->email, 'password')->assertStatus(403);
        $this->auth($premiumServer, $premium->email, 'password')->assertNoContent();

        $premium->subscription->update(['expiry_at' => now()->subDay()]);
        $this->auth($premiumServer, $premium->email, 'password')->assertStatus(403);

        $server = $this->managedServer();
        $free->update(['status' => 0]);
        $this->auth($server, $free->email, 'password')->assertStatus(403);

        $server->update(['status' => 0]);
        $this->auth($server, $premium->email, 'password')->assertStatus(403);
    }

    public function test_openvpn_auth_requires_the_same_active_subscription_as_the_app()
    {
        $server = $this->managedServer(); // a free server
        $free = $this->makeUser();
        $cancelled = $this->makeUser();
        $cancelled->subscription->update(['status' => 0]);
        $expired = $this->makeUser([], true);
        $expired->subscription->update(['expiry_at' => now()->subDay()]);

        $this->auth($server, $free->email, 'password')->assertNoContent();
        foreach ([$cancelled, $expired] as $user) {
            $this->auth($server, $user->email, 'password')->assertStatus(403);
            // ...and the app shows the same account as inactive.
            $token = $this->login($user)->assertJson(['status' => 200])->json('token');
            $this->postJson('/api/client/v4/session/status', ['session_token' => $token])
                ->assertJson(['status' => 200, 'service_status' => ['is_active' => false]]);
        }

        // A free plan is active whatever its expiry date says.
        $free->subscription->update(['expiry_at' => now()->subDay()]);
        $this->auth($server, $free->email, 'password')->assertNoContent();
    }

    public function test_a_user_without_any_subscription_row_gets_the_free_plan_instead_of_a_lockout()
    {
        $server = $this->managedServer();

        // OpenVPN login check
        $viaVpn = User::factory()->create();
        $this->auth($server, $viaVpn->email, 'password')->assertNoContent();
        $this->assertSame($this->freePlan->id, (int) $viaVpn->subscription()->sole()->plan_id);

        // App sign-in
        $viaApp = User::factory()->create();
        $token = $this->login($viaApp)->assertJson(['status' => 200, 'service_status' => ['is_active' => true, 'current_plan' => 'Free']])->json('token');
        $this->assertSame(1, Subscription::where('user_id', $viaApp->id)->count());

        // Session status of a device whose user lost the row later
        Subscription::where('user_id', $viaApp->id)->delete();
        $this->postJson('/api/client/v4/session/status', ['session_token' => $token])
            ->assertJson(['status' => 200, 'service_status' => ['is_active' => true, 'current_plan' => 'Free']]);
        $this->assertSame(1, Subscription::where('user_id', $viaApp->id)->count());
    }

    public function test_openvpn_auth_ignores_servers_that_are_not_panel_managed()
    {
        $server = $this->makeServer(['is_ovpn' => 1, 'ovpn_config' => 'client']);
        $server->forceFill(['ovpn_secret' => 'server-secret-' . $server->id])->save();
        $user = $this->makeUser();
        $this->auth($server, $user->email, 'password')->assertStatus(403);
    }

    public function test_v1_connect_returns_a_generated_profile_for_managed_servers()
    {
        $server = $this->managedServer();
        $user = $this->makeUser();

        $conf = $this->asUser($user, true)->getJson("/api/v1/server/connect/{$server->id}")->assertOk()
            ->assertJsonPath('data.auth_required', true)
            ->json('data.conf');

        $this->assertStringContainsString("remote {$server->ip_address} 1194", $conf);
        $this->assertStringContainsString('auth-user-pass', $conf);
        $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $conf);
        $this->assertStringContainsString('-----BEGIN OpenVPN Static key V1-----', $conf);
        $this->assertStringNotContainsString('PRIVATE KEY', $conf);
    }
}
