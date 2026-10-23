<?php

namespace Tests\Feature;

use App\Jobs\ConfigOpenVpnServer;
use App\Jobs\ConfigServer;
use App\Models\Admin;
use App\Models\ConfigServerJob;
use App\Models\Server;
use App\Services\OpenVpnPki;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminOpenVpnServerTest extends TestCase
{
    use RefreshDatabase;

    private string $pkiDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Server::query()->delete();
        $this->pkiDir = sys_get_temp_dir() . '/ovpn-pki-' . uniqid();
        $this->app->instance(OpenVpnPki::class, new OpenVpnPki($this->pkiDir));
        Queue::fake();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pkiDir);
        parent::tearDown();
    }

    private function serverForm(array $overrides = []): array
    {
        return array_merge([
            'country' => 'Japan',
            'state' => 'Tokyo',
            'latitude' => '35.6',
            'longitude' => '139.6',
            'status' => '1',
            'ip_address' => '203.0.113.30',
            'recommended' => '1',
            'is_premium' => '0',
            'vpn_type' => 'openvpn',
        ], $overrides);
    }

    private function admin()
    {
        return $this->actingAs(Admin::first(), 'admin');
    }

    public function test_creating_a_managed_openvpn_server_installs_openvpn()
    {
        $this->admin()->post(route('admin.servers.store'), $this->serverForm([
            'install' => 'on', 'ssh_port' => '22', 'vps_username' => 'root', 'vps_password' => 'pw',
        ]))->assertRedirect();

        $server = Server::firstOrFail();
        $this->assertTrue($server->isManagedOpenVpn());
        $this->assertSame('', $server->ovpn_config);
        Queue::assertPushed(ConfigOpenVpnServer::class);
        Queue::assertNotPushed(ConfigServer::class);
        $this->assertSame(1, ConfigServerJob::where('server_id', $server->id)->count());
    }

    public function test_server_types_map_to_the_right_flags()
    {
        $this->admin()->post(route('admin.servers.store'), $this->serverForm(['vpn_type' => 'wireguard', 'ip_address' => 'vpn.example.com']));
        $this->admin()->post(route('admin.servers.store'), $this->serverForm(['vpn_type' => 'openvpn_profile', 'ovpn_config' => "client\nremote 1.2.3.4 1194\n<ca>\nX\n</ca>"]));

        [$wireguard, $profile] = Server::orderBy('id')->get()->all();
        $this->assertFalse($wireguard->isOpenVpn());
        $this->assertTrue($profile->isOpenVpn());
        $this->assertFalse($profile->isManagedOpenVpn());
        Queue::assertNothingPushed();
    }

    public function test_managed_openvpn_needs_an_ip_address()
    {
        $this->admin()->post(route('admin.servers.store'), $this->serverForm(['ip_address' => 'vpn.example.com']));
        $this->assertSame(0, Server::count());
    }

    public function test_redeploy_picks_the_installer_for_the_server_type()
    {
        $ssh = ['ssh_port' => '22', 'vps_username' => 'root', 'vps_password' => 'pw'];
        $managed = Server::create($this->serverForm() + ['is_ovpn' => 1, 'ovpn_managed' => 1, 'ovpn_config' => '']);
        $profile = Server::create($this->serverForm() + ['is_ovpn' => 1, 'ovpn_managed' => 0, 'ovpn_config' => 'client']);

        $this->admin()->post(route('admin.servers.deploy', $managed->id), $ssh);
        Queue::assertPushed(ConfigOpenVpnServer::class, 1);

        $this->admin()->post(route('admin.servers.deploy', $profile->id), $ssh);
        Queue::assertPushed(ConfigOpenVpnServer::class, 1);
        Queue::assertNotPushed(ConfigServer::class);
    }

    public function test_admin_can_download_the_ca_and_tls_auth_key()
    {
        $ca = $this->admin()->get(route('admin.openvpn.pki', 'ca.crt'))->assertOk()->getContent();
        $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $ca);
        $this->assertStringNotContainsString('PRIVATE KEY', $ca);

        $ta = $this->admin()->get(route('admin.openvpn.pki', 'ta.key'))->assertOk()->getContent();
        $this->assertStringContainsString('-----BEGIN OpenVPN Static key V1-----', $ta);

        $this->get('/' . adminPath() . '/openvpn-pki/ca.key.enc')->assertNotFound();
    }

    public function test_guests_cannot_download_the_pki()
    {
        $this->get(route('admin.openvpn.pki', 'ca.crt'))->assertRedirect();
    }

    public function test_install_script_is_valid_bash_and_points_at_the_panel()
    {
        config(['app.url' => 'https://panel.example.com', 'services.openvpn.proto' => 'tcp', 'services.openvpn.port' => 443]);
        $server = Server::create($this->serverForm() + ['is_ovpn' => 1, 'ovpn_managed' => 1, 'ovpn_config' => '']);
        $server->forceFill(['ovpn_secret' => 'secret-for-test'])->save();
        $pki = app(OpenVpnPki::class);

        $job = new ConfigOpenVpnServer(new ConfigServerJob(), 'root', 'pw');
        $method = new \ReflectionMethod($job, 'provisionScript');
        $method->setAccessible(true);
        $script = $method->invoke($job, $server, $pki, $pki->issueServerCertificate('server-' . $server->id));

        $this->assertStringContainsString("AUTH_URL='https://panel.example.com/api/client/openvpn/auth'", $script);
        $this->assertStringContainsString('PROTO=tcp', $script);
        $this->assertStringContainsString('PORT=443', $script);
        $this->assertStringContainsString('auth-user-pass-verify /etc/openvpn/server/panel-auth.sh via-file', $script);
        $this->assertStringNotContainsString('explicit-exit-notify', $script);
        $this->assertStringContainsString('is already used by another program', $script);
        $this->assertStringContainsString("\ntls-crypt ta.key\n", $script);
        $this->assertStringNotContainsString('tls-auth ta.key', $script);

        $file = tempnam(sys_get_temp_dir(), 'ovpn-script');
        file_put_contents($file, $script);
        exec('bash -n ' . escapeshellarg($file) . ' 2>&1', $output, $code);
        unlink($file);
        $this->assertSame(0, $code, implode("\n", $output));
    }
}
