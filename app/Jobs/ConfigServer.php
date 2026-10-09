<?php

namespace App\Jobs;

use App\Exceptions\WgEasyException;
use App\Models\ConfigServerAction;
use App\Models\ConfigServerJob;
use App\Models\Server;
use App\Services\WgEasyClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * Installs wg-easy on a fresh VPS over SSH.
 *
 * The VPS credentials only live inside this job's payload, which is encrypted
 * in the queue (ShouldBeEncrypted); they are never written to the database or logs.
 */
class ConfigServer implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Docker install + image pull can be slow (China mirrors). Keep this below
    // the queue's retry_after (QUEUE_RETRY_AFTER, 900s) so the job is never
    // handed to a second worker while it is still running.
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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->configJob->job_id = $this->job ? $this->job->getJobId() : null;
        $this->configJob->status = 'running';
        $this->configJob->save();

        $server = Server::find($this->configJob->server_id);
        if (!$server) {
            $this->finish(false, 'Server was deleted before it could be provisioned.');
            return;
        }

        // Every server gets its own wg-easy password; the panel sends it with each API call.
        if (empty($server->wg_password)) {
            $server->forceFill(['wg_password' => Str::random(40)])->save();
        }

        $action = $this->startAction(1, 'Provision & verify wg-easy');
        [$resultCode, $output] = $this->runRemote($this->provisionScript($server));
        $this->finishAction($action, $resultCode, $output ?: ($resultCode === 0 ? 'Success' : 'No output'));
        if ($resultCode !== 0) {
            $this->finish(false);
            return;
        }

        // Make sure the panel itself can reach the API through the firewall.
        $action = $this->startAction(2, 'Check panel → wg-easy API access');
        try {
            $clients = WgEasyClient::for($server)->listClients();
            $this->finishAction($action, 0, 'OK — API reachable from the panel (' . count($clients) . ' peers).');
        } catch (WgEasyException $e) {
            $this->finishAction($action, 1, "wg-easy is running, but the panel cannot reach its API on port "
                . config('services.wg_easy.api_port') . ". If the panel's outgoing IP differs from the one it "
                . "uses for SSH, set WG_EASY_ALLOWED_IPS and redeploy; also check the provider's firewall.\n"
                . $e->getMessage());
            $this->finish(false);
            return;
        }

        $this->finish(true);
    }

    /**
     * Called by the queue when handle() throws or times out.
     */
    public function failed(Throwable $e)
    {
        $action = $this->startAction(99, 'Job error');
        $this->finishAction($action, 1, $this->redact($e->getMessage()));
        $this->finish(false);
    }

    /**
     * Run a bash script on the VPS as root. Returns [exitCode, output].
     */
    private function runRemote(string $script): array
    {
        $ip = $this->configJob->ip;
        $port = (int) $this->configJob->ssh_port;

        $knownHosts = storage_path('app/ssh/known_hosts');
        File::ensureDirectoryExists(dirname($knownHosts), 0700);

        // The remote login shell expands ${SSH_CLIENT%% *} to the panel's IP as the
        // VPS sees it; the script uses it to restrict the wg-easy API port.
        $remote = 'bash -s -- "${SSH_CLIENT%% *}"';
        if ($this->vpsUsername !== 'root') {
            $remote = 'sudo -n ' . $remote;
        }

        // sshpass -e reads the password from $SSHPASS, so it never appears in the
        // command line (ps) or in any log.
        $command = implode(' ', [
            'sshpass -e ssh',
            '-p ' . $port,
            '-o ' . escapeshellarg('UserKnownHostsFile=' . $knownHosts),
            '-o StrictHostKeyChecking=accept-new',
            '-o ConnectTimeout=30',
            '-o LogLevel=ERROR',
            escapeshellarg($this->vpsUsername . '@' . $ip),
            escapeshellarg($remote),
            '2>&1',
        ]);

        $env = array_merge(getenv(), ['SSHPASS' => $this->vpsPassword]);
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $pipes, null, $env);
        if (!is_resource($process)) {
            return [-1, 'Could not start ssh (is sshpass installed on the panel server?)'];
        }
        fwrite($pipes[0], $script);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $resultCode = proc_close($process);

        if ($resultCode === 6 || str_contains($output, 'REMOTE HOST IDENTIFICATION HAS CHANGED')) {
            $output .= "\nThe VPS host key changed since the last deployment. If the VPS was reinstalled, "
                . "remove its line from storage/app/ssh/known_hosts on the panel and redeploy.";
        }
        if ($resultCode === 5) {
            $output .= "\nSSH login failed: wrong VPS username or password.";
        }

        return [$resultCode, $this->redact(trim($output))];
    }

    /**
     * Region-aware wg-easy install. China-based servers usually can't reach
     * get.docker.com / Docker Hub, so it falls back to the Aliyun installer and
     * China registry mirrors. Every phase prints, and on failure it prints the
     * reason + the last wg-easy container logs.
     */
    private function provisionScript(Server $server): string
    {
        $vars = implode("\n", [
            'WG_HOST=' . escapeshellarg($this->configJob->ip),
            'WG_PASSWORD=' . escapeshellarg($server->wg_password),
            'WG_IMAGE=' . escapeshellarg(config('services.wg_easy.image')),
            'API_PORT=' . (int) config('services.wg_easy.api_port', 51821),
            'WG_PORT=' . (int) config('services.wg_easy.wg_port', 51820),
            'KEEPALIVE=' . (int) config('services.wg_easy.persistent_keepalive', 25),
            'ALLOWED_IPS=' . escapeshellarg($this->allowedIps()),
        ]);

        return <<<BASH
#!/bin/bash
$vars
SSH_PANEL_IP="\$1"
[ -z "\$ALLOWED_IPS" ] && ALLOWED_IPS="\$SSH_PANEL_IP"
echo "=== wg-easy provision start (host=\$WG_HOST) ==="

CN=0
if ! curl -s -o /dev/null --max-time 6 https://registry-1.docker.io/v2/; then CN=1; fi
echo "[1/7] region-check: CN=\$CN  (1 = restricted/China path)"

if command -v docker >/dev/null 2>&1; then
  echo "[2/7] docker present: \$(docker --version)"
else
  echo "[2/7] installing docker (CN=\$CN)..."
  if [ "\$CN" = "1" ]; then
    curl -fsSL https://get.docker.com | sh -s -- --mirror Aliyun
  else
    curl -fsSL https://get.docker.com | sh
  fi
fi
systemctl enable --now docker 2>/dev/null || service docker start 2>/dev/null || true
if ! docker info >/dev/null 2>&1; then echo "ERROR: docker daemon is not running"; exit 11; fi

if [ "\$CN" = "1" ]; then
  echo "[3/7] adding China registry mirrors..."
  mkdir -p /etc/docker
  printf '%s' '{"registry-mirrors":["https://docker.m.daocloud.io","https://dockerproxy.com","https://docker.1panel.live","https://hub.rat.dev"]}' > /etc/docker/daemon.json
  systemctl restart docker 2>/dev/null || service docker restart 2>/dev/null || true
  sleep 4
else
  echo "[3/7] global network — no registry mirrors needed"
fi

echo "[4/7] checking WireGuard kernel support..."
if lsmod | grep -q '^wireguard' || modprobe wireguard 2>/dev/null; then
  echo "       wireguard kernel module OK"
else
  echo "WARN: wireguard kernel module not available — on an OpenVZ/LXC VPS wg-easy CANNOT work; you need a KVM VPS."
fi

echo "[5/7] firewall: WireGuard \$WG_PORT/udp open; API \$API_PORT/tcp only from: \${ALLOWED_IPS:-<nobody>}"
ufw allow "\$WG_PORT"/udp 2>/dev/null || true
ufw delete allow "\$API_PORT"/tcp >/dev/null 2>&1 || true
# Docker-published ports bypass ufw/INPUT, so the API port is filtered in the
# DOCKER-USER chain. A systemd unit re-applies the rules after reboots and
# docker restarts.
cat > /usr/local/sbin/wg-easy-firewall.sh <<FW
#!/bin/sh
iptables -N DOCKER-USER 2>/dev/null || true
iptables -N WGEASY-API 2>/dev/null || iptables -F WGEASY-API
i=0; while [ \\\$i -lt 20 ] && iptables -D DOCKER-USER -p tcp -m conntrack --ctorigdstport \$API_PORT --ctdir ORIGINAL -j WGEASY-API 2>/dev/null; do i=\\\$((i+1)); done
for ip in \$(echo "\$ALLOWED_IPS" | tr ',' ' '); do
  case "\\\$ip" in *:*) continue ;; esac
  iptables -A WGEASY-API -s "\\\$ip" -j RETURN
done
iptables -A WGEASY-API -j DROP
iptables -I DOCKER-USER -p tcp -m conntrack --ctorigdstport \$API_PORT --ctdir ORIGINAL -j WGEASY-API
FW
chmod 700 /usr/local/sbin/wg-easy-firewall.sh
if command -v systemctl >/dev/null 2>&1; then
  cat > /etc/systemd/system/wg-easy-firewall.service <<UNIT
[Unit]
Description=Restrict wg-easy API port to the admin panel
After=docker.service
PartOf=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=/usr/local/sbin/wg-easy-firewall.sh

[Install]
WantedBy=multi-user.target docker.service
UNIT
  systemctl daemon-reload
  systemctl enable wg-easy-firewall.service >/dev/null 2>&1 || true
fi
if [ -z "\$ALLOWED_IPS" ]; then
  echo "WARN: could not detect the panel IP; API port is closed to everyone (set WG_EASY_ALLOWED_IPS and redeploy)."
fi
if ! command -v iptables >/dev/null 2>&1; then
  echo "WARN: iptables not found — API port is NOT firewalled (still password-protected)."
fi

echo "[6/7] pulling + running wg-easy (\$WG_IMAGE)..."
docker pull "\$WG_IMAGE" || { echo "ERROR: image pull failed (network/registry-mirror issue)"; exit 12; }
docker rm -f wg-easy 2>/dev/null || true
docker run -d --name=wg-easy \\
  -e LANG=en -e WG_HOST="\$WG_HOST" -e PASSWORD="\$WG_PASSWORD" \\
  -e WG_PERSISTENT_KEEPALIVE="\$KEEPALIVE" \\
  -v "\$HOME/.wg-easy":/etc/wireguard \\
  -p "\$WG_PORT":51820/udp -p "\$API_PORT":51821/tcp \\
  --cap-add=NET_ADMIN --cap-add=SYS_MODULE \\
  --sysctl net.ipv4.conf.all.src_valid_mark=1 --sysctl net.ipv4.ip_forward=1 \\
  --restart unless-stopped "\$WG_IMAGE" || { echo "ERROR: docker run failed (port \$WG_PORT/\$API_PORT already in use?)"; exit 13; }
# The container start re-creates docker's chains; (re)apply our filter after it.
/usr/local/sbin/wg-easy-firewall.sh 2>&1 || echo "WARN: could not apply API firewall rules"

sleep 5
if ! docker ps --format '{{.Names}}' | grep -q '^wg-easy\$'; then
  echo "ERROR: wg-easy container exited right after start. Last logs:"
  docker logs wg-easy 2>&1 | tail -n 40
  exit 14
fi

echo "[7/7] container up — checking API + WireGuard interface on :\$API_PORT..."
if ! curl -sf --retry 30 --retry-delay 2 --retry-connrefused -o /dev/null http://127.0.0.1:\$API_PORT/api/release; then
  echo "ERROR: wg-easy API not answering on \$API_PORT. Last logs:"
  docker logs wg-easy 2>&1 | tail -n 40
  exit 15
fi
# Authenticated call: proves the password works and that wg0 came up.
if ! curl -sf -o /dev/null -H "Authorization: \$WG_PASSWORD" http://127.0.0.1:\$API_PORT/api/wireguard/client; then
  echo "ERROR: wg-easy API rejected the password or WireGuard failed to start. Last logs:"
  docker logs wg-easy 2>&1 | tail -n 40
  exit 16
fi
echo "SUCCESS: wg-easy is up (CN=\$CN)"
BASH;
    }

    /**
     * WG_EASY_ALLOWED_IPS, keeping only syntactically valid IPv4/IPv6 addresses or CIDRs.
     */
    private function allowedIps(): string
    {
        $ips = array_filter(array_map('trim', explode(',', (string) config('services.wg_easy.allowed_ips'))));
        $ips = array_filter($ips, function ($entry) {
            [$ip, $mask] = array_pad(explode('/', $entry, 2), 2, null);
            return filter_var($ip, FILTER_VALIDATE_IP) && ($mask === null || ctype_digit($mask));
        });
        return implode(',', $ips);
    }

    private function redact(string $text): string
    {
        foreach ([$this->vpsPassword, Server::find($this->configJob->server_id)?->wg_password] as $secret) {
            if (is_string($secret) && $secret !== '') {
                $text = str_replace($secret, '******', $text);
            }
        }
        return $text;
    }

    private function startAction(int $order, string $name): ConfigServerAction
    {
        $action = new ConfigServerAction();
        $action->config_job_id = $this->configJob->id;
        $action->order = $order;
        $action->result_code = -1;
        $action->action = $name;
        $action->result = 'Running...';
        $action->save();
        return $action;
    }

    private function finishAction(ConfigServerAction $action, int $resultCode, string $result): void
    {
        $action->result_code = $resultCode;
        $action->result = $result;
        $action->save();
    }

    private function finish(bool $success, ?string $message = null): void
    {
        $this->configJob->status = $success ? 'success' : 'failed';
        if ($message !== null) {
            $this->configJob->message = $message;
        }
        $this->configJob->save();
    }
}
