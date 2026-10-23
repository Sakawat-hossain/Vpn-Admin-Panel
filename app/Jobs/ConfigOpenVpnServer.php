<?php

namespace App\Jobs;

use App\Http\Controllers\Api\Client\OpenVpnAuthController;
use App\Jobs\Concerns\RunsServerSetup;
use App\Models\ConfigServerJob;
use App\Models\Server;
use App\Services\OpenVpnPki;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Installs a panel-managed OpenVPN server on a VPS over SSH: the panel CA,
 * a fresh server certificate, and an auth-user-pass-verify script that asks
 * the panel (OpenVpnAuthController) whether each login may connect.
 *
 * Like ConfigServer, the VPS credentials only live in the encrypted job payload.
 */
class ConfigOpenVpnServer implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, RunsServerSetup;

    // Package install can be slow; stay below the queue's retry_after (900s).
    public $timeout = 840;
    public $failOnTimeout = true;
    public $tries = 1;

    private ConfigServerJob $configJob;
    private string $vpsUsername;
    private string $vpsPassword;

    public function __construct(ConfigServerJob $configJob, string $vpsUsername, string $vpsPassword)
    {
        $this->configJob = $configJob;
        $this->vpsUsername = $vpsUsername;
        $this->vpsPassword = $vpsPassword;
    }

    public function handle(OpenVpnPki $pki)
    {
        $this->configJob->job_id = $this->job ? $this->job->getJobId() : null;
        $this->configJob->status = 'running';
        $this->configJob->save();

        $server = Server::find($this->configJob->server_id);
        if (!$server || !$server->isManagedOpenVpn()) {
            $this->finish(false, 'Server was deleted or is no longer a panel-managed OpenVPN server.');
            return;
        }

        // The secret the server sends with every login check. Kept across
        // redeployments unless it is missing.
        if (empty($server->ovpn_secret)) {
            $server->forceFill(['ovpn_secret' => Str::random(48)])->save();
        }

        $action = $this->startAction(1, 'Install & start OpenVPN');
        $certificate = $pki->issueServerCertificate('server-' . $server->id);
        [$resultCode, $output] = $this->runRemote($this->provisionScript($server, $pki, $certificate));
        $this->finishAction($action, $resultCode, $output ?: ($resultCode === 0 ? 'Success' : 'No output'));
        $this->finish($resultCode === 0);
    }

    public function failed(Throwable $e)
    {
        $action = $this->startAction(99, 'Job error');
        $this->finishAction($action, 1, $this->redact($e->getMessage()));
        $this->finish(false);
    }

    /**
     * URL the server calls to check logins.
     */
    public static function authUrl(): string
    {
        $base = config('services.openvpn.auth_url') ?: config('app.url');
        return rtrim((string) $base, '/') . '/api/client/openvpn/auth';
    }

    /**
     * @param array{cert: string, key: string} $certificate
     */
    private function provisionScript(Server $server, OpenVpnPki $pki, array $certificate): string
    {
        $proto = config('services.openvpn.proto') === 'tcp' ? 'tcp' : 'udp';
        $port = (int) config('services.openvpn.port');
        [$network, $netmask] = array_pad(preg_split('/\s+/', trim((string) config('services.openvpn.subnet'))), 2, '255.255.0.0');
        $dnsPush = collect(explode(',', (string) config('services.openvpn.dns')))
            ->map(fn($ip) => trim($ip))
            ->filter(fn($ip) => filter_var($ip, FILTER_VALIDATE_IP))
            ->map(fn($ip) => 'push "dhcp-option DNS ' . $ip . '"')
            ->implode("\n");

        $vars = implode("\n", [
            'PORT=' . $port,
            'PROTO=' . $proto,
            'NETWORK=' . escapeshellarg($network),
            'NETMASK=' . escapeshellarg($netmask),
            'AUTH_URL=' . escapeshellarg(self::authUrl()),
            'SERVER_ID=' . (int) $server->id,
            'SERVER_SECRET=' . escapeshellarg($server->ovpn_secret),
            'CHECK_USER=' . escapeshellarg(OpenVpnAuthController::CHECK_USERNAME),
        ]);
        $ca = trim($pki->caCertificate());
        $cert = trim($certificate['cert']);
        $key = trim($certificate['key']);
        $ta = trim($pki->tlsAuthKey());
        $udpOnly = $proto === 'udp' ? 'explicit-exit-notify 1' : '';

        return <<<BASH
#!/bin/bash
$vars
echo "=== OpenVPN provision start (server \$SERVER_ID, \$PROTO/\$PORT) ==="

echo "[1/7] installing packages..."
if command -v apt-get >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y >/dev/null 2>&1 || echo "WARN: apt-get update failed"
  apt-get install -y openvpn curl iptables >/dev/null 2>&1 || { echo "ERROR: apt-get install openvpn failed"; exit 21; }
elif command -v dnf >/dev/null 2>&1; then
  dnf install -y epel-release >/dev/null 2>&1 || true
  dnf install -y openvpn curl iptables >/dev/null 2>&1 || { echo "ERROR: dnf install openvpn failed"; exit 21; }
elif command -v yum >/dev/null 2>&1; then
  yum install -y epel-release >/dev/null 2>&1 || true
  yum install -y openvpn curl iptables >/dev/null 2>&1 || { echo "ERROR: yum install openvpn failed"; exit 21; }
else
  echo "ERROR: no apt-get/dnf/yum found; install openvpn manually"; exit 21
fi
command -v openvpn >/dev/null 2>&1 || { echo "ERROR: openvpn not installed"; exit 22; }
OVPN_VERSION=\$(openvpn --version 2>/dev/null | head -n 1)
echo "       \$OVPN_VERSION"
if [ ! -c /dev/net/tun ]; then
  echo "ERROR: /dev/net/tun is missing. On an OpenVZ/LXC VPS ask the provider to enable TUN, or use a KVM VPS."; exit 23
fi

echo "[2/7] writing certificates and keys..."
D=/etc/openvpn/server
mkdir -p "\$D"
chmod 755 /etc/openvpn "\$D"
cat > "\$D/ca.crt" <<'PANEL_EOF'
$ca
PANEL_EOF
cat > "\$D/server.crt" <<'PANEL_EOF'
$cert
PANEL_EOF
cat > "\$D/server.key" <<'PANEL_EOF'
$key
PANEL_EOF
cat > "\$D/ta.key" <<'PANEL_EOF'
$ta
PANEL_EOF
chmod 600 "\$D/server.key" "\$D/ta.key"

echo "[3/7] writing the panel login check..."
cat > "\$D/panel-auth.sh" <<PANEL_EOF
#!/bin/sh
# Asks the panel whether this login may connect. OpenVPN passes a file
# holding the username (line 1) and password (line 2).
U=\\\$(sed -n 1p "\\\$1")
P=\\\$(sed -n 2p "\\\$1")
CODE=\\\$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 \\
  -H "Authorization: Bearer \$SERVER_SECRET" -H "X-Server-Id: \$SERVER_ID" -H "Accept: application/json" \\
  --data-urlencode "username=\\\$U" --data-urlencode "password=\\\$P" "\$AUTH_URL")
[ "\\\$CODE" = "204" ]
PANEL_EOF
chmod 700 "\$D/panel-auth.sh"

case "\$OVPN_VERSION" in
  "OpenVPN 2.4"*) CIPHERS="ncp-ciphers AES-256-GCM:AES-128-GCM:AES-256-CBC" ;;
  *) CIPHERS="data-ciphers AES-256-GCM:AES-128-GCM:CHACHA20-POLY1305:AES-256-CBC" ;;
esac

echo "[4/7] writing server.conf..."
cat > "\$D/server.conf" <<PANEL_EOF
# Managed by the admin panel; redeploy from the panel instead of editing.
port \$PORT
proto \$PROTO
dev tun
topology subnet
server \$NETWORK \$NETMASK
ca ca.crt
cert server.crt
key server.key
dh none
tls-auth ta.key 0
cipher AES-256-CBC
\$CIPHERS
verify-client-cert none
username-as-common-name
duplicate-cn
auth-user-pass-verify /etc/openvpn/server/panel-auth.sh via-file
script-security 2
keepalive 10 60
reneg-sec 3600
persist-key
persist-tun
push "redirect-gateway def1 bypass-dhcp"
$dnsPush
verb 3
$udpOnly
PANEL_EOF

echo "[5/7] forwarding, NAT and firewall..."
echo 'net.ipv4.ip_forward=1' > /etc/sysctl.d/99-openvpn-panel.conf
sysctl -q -w net.ipv4.ip_forward=1
IFACE=\$(ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if(\$i=="dev"){print \$(i+1); exit}}')
[ -z "\$IFACE" ] && { echo "ERROR: could not detect the public network interface"; exit 25; }
cat > /usr/local/sbin/openvpn-panel-nat.sh <<PANEL_EOF
#!/bin/sh
iptables -t nat -C POSTROUTING -s \$NETWORK/\$NETMASK -o \$IFACE -j MASQUERADE 2>/dev/null || iptables -t nat -A POSTROUTING -s \$NETWORK/\$NETMASK -o \$IFACE -j MASQUERADE
iptables -C FORWARD -i tun+ -j ACCEPT 2>/dev/null || iptables -I FORWARD -i tun+ -j ACCEPT
iptables -C FORWARD -o tun+ -m state --state RELATED,ESTABLISHED -j ACCEPT 2>/dev/null || iptables -I FORWARD -o tun+ -m state --state RELATED,ESTABLISHED -j ACCEPT
iptables -C INPUT -p \$PROTO --dport \$PORT -j ACCEPT 2>/dev/null || iptables -I INPUT -p \$PROTO --dport \$PORT -j ACCEPT
PANEL_EOF
chmod 700 /usr/local/sbin/openvpn-panel-nat.sh
/usr/local/sbin/openvpn-panel-nat.sh || echo "WARN: could not apply iptables rules"
ufw allow "\$PORT/\$PROTO" >/dev/null 2>&1 || true
if command -v firewall-cmd >/dev/null 2>&1; then
  firewall-cmd -q --permanent --add-port="\$PORT/\$PROTO" 2>/dev/null && firewall-cmd -q --reload 2>/dev/null || true
fi

echo "[6/7] starting OpenVPN..."
if ! command -v systemctl >/dev/null 2>&1; then
  echo "ERROR: systemd is required"; exit 26
fi
cat > /etc/systemd/system/openvpn-panel-nat.service <<PANEL_EOF
[Unit]
Description=NAT and firewall rules for the panel-managed OpenVPN server
After=network-online.target
Before=openvpn-server@server.service

[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=/usr/local/sbin/openvpn-panel-nat.sh

[Install]
WantedBy=multi-user.target
PANEL_EOF
# Distro units cap processes (LimitNPROC=10), which breaks the login check
# script under load.
mkdir -p /etc/systemd/system/openvpn-server@server.service.d
printf '[Service]\\nLimitNPROC=infinity\\n' > /etc/systemd/system/openvpn-server@server.service.d/panel.conf
systemctl daemon-reload
systemctl enable openvpn-panel-nat.service >/dev/null 2>&1 || true
systemctl enable openvpn-server@server >/dev/null 2>&1 || true
systemctl restart openvpn-server@server
sleep 3
if ! systemctl is-active --quiet openvpn-server@server; then
  echo "ERROR: OpenVPN did not start. Last logs:"
  journalctl -u openvpn-server@server --no-pager -n 40 2>/dev/null
  exit 24
fi

echo "[7/7] checking the panel login check..."
T=\$(mktemp)
printf '%s\\n%s\\n' "\$CHECK_USER" x > "\$T"
if "\$D/panel-auth.sh" "\$T"; then
  rm -f "\$T"
  echo "SUCCESS: OpenVPN is up on \$PROTO/\$PORT and can reach the panel"
else
  rm -f "\$T"
  CODE=\$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 -X POST "\$AUTH_URL")
  echo "ERROR: OpenVPN is running, but the login check could not reach the panel at \$AUTH_URL (HTTP \$CODE)."
  echo "Users will not be able to connect. Check that this VPS can reach the panel (set OPENVPN_AUTH_URL if it must use another address) and redeploy."
  exit 27
fi
BASH;
    }

    /**
     * @return array<int, string|null>
     */
    private function secretsToRedact(): array
    {
        return [$this->vpsPassword, Server::find($this->configJob->server_id)?->ovpn_secret];
    }
}
