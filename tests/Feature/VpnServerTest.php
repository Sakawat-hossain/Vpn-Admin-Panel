<?php

namespace Tests\Feature;

use App\Exceptions\WgEasyException;
use App\Http\Controllers\Backend\ServerController as AdminServerController;
use App\Jobs\RevokeWireGuardPeer;
use App\Models\Admin;
use App\Models\Server;
use App\Models\User;
use App\Services\WgEasyClient;
use Illuminate\Support\Facades\Queue;

class VpnServerTest extends ApiTestCase
{
    /** @var array<int, array> calls recorded by the fake wg-easy client */
    private array $calls = [];
    private bool $wgDown = false;

    protected function setUp(): void
    {
        parent::setUp();
        $test = $this;
        $this->app->bind(WgEasyClient::class, function ($app, $params) use ($test) {
            return new class($params['server'], $test) extends WgEasyClient {
                private $test;
                private $srv;
                public function __construct(Server $server, $test)
                {
                    $this->srv = $server;
                    $this->test = $test;
                }
                public function createClient(string $name): void
                {
                    $this->test->record('create', $this->srv, $name);
                }
                public function getConfiguration(string $clientId, string $dns): string
                {
                    $this->test->record('config', $this->srv, $clientId, $dns);
                    return "[Interface]\nDNS = {$dns}\n";
                }
                public function deleteClient(string $clientId): void
                {
                    $this->test->record('delete', $this->srv, $clientId);
                }
                public function listClients(): array
                {
                    return [];
                }
            };
        });
    }

    public function record(string $op, Server $server, ...$args): void
    {
        if ($this->wgDown) {
            throw new WgEasyException('down');
        }
        $this->calls[] = [$op, $server->id, ...$args];
    }

    public function test_server_list_hides_ovpn_config_and_secrets()
    {
        $this->makeServer(['is_ovpn' => 1, 'ovpn_config' => 'SECRET-PROFILE']);
        $server = $this->makeServer();
        $server->forceFill(['wg_password' => 'hunter2'])->save();

        $response = $this->withHeaders(['x-api-key' => self::API_KEY])->postJson('/api/v1/server')->assertOk();
        $this->assertStringNotContainsString('SECRET-PROFILE', $response->getContent());
        $this->assertStringNotContainsString('wg_password', $response->getContent());
    }

    public function test_connect_requires_login_and_app_key()
    {
        $server = $this->makeServer();
        $user = $this->makeUser();
        $this->flushHeaders()->withHeaders(['x-api-key' => self::API_KEY, 'Accept' => 'application/json'])
            ->getJson("/api/v1/server/connect/{$server->id}")->assertStatus(401);
        $this->asUser($user)->getJson("/api/v1/server/connect/{$server->id}")->assertStatus(403);
    }

    public function test_free_user_cannot_connect_to_premium_server()
    {
        $wg = $this->makeServer(['is_premium' => 1]);
        $ovpn = $this->makeServer(['is_premium' => 1, 'is_ovpn' => 1, 'ovpn_config' => "client\nremote x 1194\n"]);
        $user = $this->makeUser();

        $this->asUser($user, true)->getJson("/api/v1/server/connect/{$wg->id}")->assertStatus(403);
        $this->asUser($user, true)->getJson("/api/v1/server/connect/{$ovpn->id}")->assertStatus(403);
        $this->assertSame([], $this->calls);

        $premium = $this->makeUser([], true);
        $this->asUser($premium, true)->getJson("/api/v1/server/connect/{$wg->id}")
            ->assertOk()->assertJsonPath('data.protocol', 'wireguard');
        $this->asUser($premium, true)->getJson("/api/v1/server/connect/{$ovpn->id}")
            ->assertOk()->assertJsonPath('data.protocol', 'openvpn')->assertJsonPath('data.conf', "client\nremote x 1194\n");
    }

    public function test_expired_premium_counts_as_free()
    {
        $wg = $this->makeServer(['is_premium' => 1]);
        $user = $this->makeUser([], true);
        $user->subscription->update(['expiry_at' => now()->subDay()]);
        $this->asUser($user, true)->getJson("/api/v1/server/connect/{$wg->id}")->assertStatus(403);
    }

    public function test_wireguard_connect_uses_safe_dns_and_revokes_previous_server_peer()
    {
        Queue::fake();
        $a = $this->makeServer();
        $b = $this->makeServer(['ip_address' => '203.0.113.11']);
        $user = $this->makeUser(['dns' => 'bogus/../x', 'server_id' => $a->id]);

        $this->asUser($user, true)->getJson("/api/v1/server/connect/{$b->id}")
            ->assertOk()
            ->assertJsonPath('data.client_id', 'wg' . $user->id)
            ->assertJsonPath('data.conf', "[Interface]\nDNS = 1.1.1.1\n");

        $this->assertSame(['config', $b->id, 'wg' . $user->id, '1.1.1.1'], $this->calls[1]);
        $this->assertSame($b->id, $user->fresh()->server_id);
        Queue::assertPushed(RevokeWireGuardPeer::class, fn ($job) => $job->serverId === $a->id && $job->clientName === 'wg' . $user->id);
    }

    public function test_connect_reports_unreachable_server()
    {
        $server = $this->makeServer();
        $user = $this->makeUser();
        $this->wgDown = true;
        $this->asUser($user, true)->getJson("/api/v1/server/connect/{$server->id}")->assertStatus(502);
    }

    public function test_deleting_a_server_keeps_its_users()
    {
        $server = $this->makeServer();
        $user = $this->makeUser(['server_id' => $server->id]);
        $admin = Admin::forceCreate([
            'name' => 'Admin', 'firstname' => 'A', 'lastname' => 'D', 'email' => 'admin@example.com',
            'avatar' => 'images/avatars/default.png', 'password' => bcrypt('x'),
        ]);

        $this->actingAs($admin, 'admin')->delete(route('admin.servers.destroy', $server->id))->assertRedirect();

        $this->assertNull(Server::find($server->id));
        $this->assertNotNull($user = User::find($user->id));
        $this->assertNull($user->server_id);
        $this->assertNotNull($user->subscription);
    }

    public function test_revoke_job_deletes_peer()
    {
        $server = $this->makeServer();
        (new RevokeWireGuardPeer($server->id, 'wg42'))->handle();
        $this->assertSame([['delete', $server->id, 'wg42']], $this->calls);
    }

    public function test_prune_removes_stale_peers_only()
    {
        $premium = $this->makeServer(['is_premium' => 1]);
        $paying = $this->makeUser(['server_id' => $premium->id], true);
        $lapsed = $this->makeUser(['server_id' => $premium->id]);
        $moved = $this->makeUser(['server_id' => null], true);

        $this->app->bind(WgEasyClient::class, function ($app, $params) use ($paying, $lapsed, $moved) {
            $test = $this;
            return new class($params['server'], $test, [$paying, $lapsed, $moved]) extends WgEasyClient {
                public function __construct(private Server $srv, private $test, private array $users)
                {
                }
                public function listClients(): array
                {
                    return array_merge(
                        array_map(fn ($u) => ['id' => 'wg' . $u->id], $this->users),
                        [['id' => 'wg999999'], ['id' => 'manual-peer']]
                    );
                }
                public function deleteClient(string $clientId): void
                {
                    $this->test->record('delete', $this->srv, $clientId);
                }
            };
        });

        $this->artisan('wg:prune')->assertExitCode(0);

        $deleted = array_column($this->calls, 2);
        sort($deleted);
        $expected = ['wg' . $lapsed->id, 'wg' . $moved->id, 'wg999999'];
        sort($expected);
        $this->assertSame($expected, $deleted);
    }

    public function test_ovpn_config_validation()
    {
        $good = "client\ndev tun\nproto udp\nremote vpn.example.com 1194\n<ca>\n-----BEGIN CERTIFICATE-----\nx\n-----END CERTIFICATE-----\n</ca>\n<tls-crypt>\nk\n</tls-crypt>\nauth-user-pass\n";
        $this->assertNull(AdminServerController::ovpnConfigError($good));
        $this->assertNotNull(AdminServerController::ovpnConfigError("dev tun\nremote x 1194\n<ca>\nx\n</ca>\n"));
        $this->assertNotNull(AdminServerController::ovpnConfigError("client\n<ca>\nx\n</ca>\n"));
        $this->assertNotNull(AdminServerController::ovpnConfigError("client\nremote x 1194\nca ca.crt\n"));
        $this->assertNotNull(AdminServerController::ovpnConfigError($good . "script-security 2\nup /tmp/x.sh\n"));
        $this->assertNotNull(AdminServerController::ovpnConfigError("client\nremote x 1194\n<ca>\nx\n"));
    }
}
