<?php

namespace App\Jobs;

ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');

use App\Models\ConfigServerAction;
use App\Models\ConfigServerJob;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConfigServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $failOnTimeout = true;
    public $tries = 0;

    private ConfigServerJob $configJob;

    public function __construct(ConfigServerJob $configJob) {
        $this->configJob = $configJob;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->configJob->job_id = $this->job->getJobId();
        $this->configJob->save();

        $vpsUsername = $this->configJob->vps_username;
        $vpsPassword = $this->configJob->vps_password;
        $sshPort = $this->configJob->ssh_port;
        $ip = $this->configJob->ip;

        //settings
        $sshTimeout = 30;

        $sshpass = "sshpass -p '$vpsPassword'";
        // jika windows,tambahin wsl sebelum $sshpass
        $sshCmd = "$sshpass ssh -p $sshPort $vpsUsername@$ip -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o ConnectTimeout=$sshTimeout -o LogLevel=ERROR";
        
        // Region-aware provisioning script. China-based servers usually can't
        // reach get.docker.com / Docker Hub, so we auto-detect that and fall
        // back to the Aliyun installer + China registry mirrors; global servers
        // take the fast direct path. The script is base64-encoded and piped to
        // bash on the target to avoid all SSH quoting issues.
        $provisionScript = <<<BASH
#!/bin/bash
# Verbose & self-diagnosing: every phase prints, and on failure it prints the
# reason + the last wg-easy container logs so the deployment log shows WHY.
WG_HOST="$ip"
echo "=== wg-easy provision start (host=$ip) ==="

CN=0
if ! curl -s -o /dev/null --max-time 6 https://registry-1.docker.io/v2/; then CN=1; fi
echo "[1/6] region-check: CN=\$CN  (1 = restricted/China path)"

if command -v docker >/dev/null 2>&1; then
  echo "[2/6] docker present: \$(docker --version)"
else
  echo "[2/6] installing docker (CN=\$CN)..."
  if [ "\$CN" = "1" ]; then
    curl -fsSL https://get.docker.com | sh -s -- --mirror Aliyun
  else
    curl -fsSL https://get.docker.com | sh
  fi
fi
systemctl enable --now docker 2>/dev/null || service docker start 2>/dev/null || true
if ! docker info >/dev/null 2>&1; then echo "ERROR: docker daemon is not running"; exit 11; fi

if [ "\$CN" = "1" ]; then
  echo "[3/6] adding China registry mirrors..."
  mkdir -p /etc/docker
  printf '%s' '{"registry-mirrors":["https://docker.m.daocloud.io","https://dockerproxy.com","https://docker.1panel.live","https://hub.rat.dev"]}' > /etc/docker/daemon.json
  systemctl restart docker 2>/dev/null || service docker restart 2>/dev/null || true
  sleep 4
else
  echo "[3/6] global network — no registry mirrors needed"
fi

echo "[4/6] checking WireGuard kernel support..."
if lsmod | grep -q '^wireguard' || modprobe wireguard 2>/dev/null; then
  echo "       wireguard kernel module OK"
else
  echo "WARN: wireguard kernel module not available — on an OpenVZ/LXC VPS wg-easy CANNOT work; you need a KVM VPS."
fi

ufw allow 51820/udp 2>/dev/null || true
ufw allow 51821/tcp 2>/dev/null || true

echo "[5/6] pulling + running wg-easy..."
docker rm -f wg-easy 2>/dev/null || true
docker pull ombapit/wg-easy || { echo "ERROR: image pull failed (network/registry-mirror issue)"; exit 12; }
docker run -d --name=wg-easy -e LANG=en -e WG_HOST="\$WG_HOST" -v ~/.wg-easy:/etc/wireguard -p 51820:51820/udp -p 51821:51821/tcp --cap-add=NET_ADMIN --cap-add=SYS_MODULE --sysctl net.ipv4.conf.all.src_valid_mark=1 --sysctl net.ipv4.ip_forward=1 --restart unless-stopped ombapit/wg-easy || { echo "ERROR: docker run failed (port 51820/51821 already in use?)"; exit 13; }

sleep 5
if ! docker ps --format '{{.Names}}' | grep -q '^wg-easy\$'; then
  echo "ERROR: wg-easy container exited right after start. Last logs:"
  docker logs wg-easy 2>&1 | tail -n 40
  exit 14
fi

echo "[6/6] container up — waiting for API on :51821..."
if ! curl -sf --retry 30 --retry-delay 2 --retry-connrefused http://127.0.0.1:51821/ >/dev/null; then
  echo "ERROR: wg-easy API not answering on 51821. Last logs:"
  docker logs wg-easy 2>&1 | tail -n 40
  exit 15
fi
echo "SUCCESS: wg-easy is up (CN=\$CN)"
BASH;

        $provisionB64 = base64_encode($provisionScript);

        $cmds = [
            [
                // One verbose, self-diagnosing script (base64-piped to bash so
                // quoting/region logic stays reliable). On any failure it prints
                // the reason + docker logs, captured into the action output.
                'action' => "Provision & verify wg-easy",
                'command' => "$sshCmd \"echo $provisionB64 | base64 -d | bash\"",
            ],
        ];

        echo "\nStarting Wg-Easy installation\n";
        foreach ($cmds as $index => $cmd) {
            $output = array();
            $result_code = -1;
            $action = new ConfigServerAction();
            $action->config_job_id = $this->configJob->id;
            $action->order = $index + 1;
            $action->result_code = -1;
            $action->action = $cmd['action'];
            $action->result = "Running...";
            $action->save();

            echo "action={$action->action}\n";
            echo "command={$cmd['command']}\n";
            exec($cmd['command'] . " 2>&1", $output, $result_code);

            sleep(2);

            $action->result_code = $result_code;
            if ($result_code == 0) {
                echo "Command success\n";
                // Keep the full phase log (e.g. [1/6]..SUCCESS) for visibility.
                $action->result = trim(implode("\n", $output)) ?: "Success";
                $action->save();
                continue;
            }

            $action->result = implode("\n", $output);
            echo "Result=$result_code & Output={$action->result}\n";
            $action->save();

            echo "WG Easy server installation failed.\n";
            $this->configJob->status = 'failed';
            $this->configJob->save();
            $this->fail();
            return;
        }

        echo "WG Easy installation finished successfully.\n";

        $this->configJob->status = 'success';
        $this->configJob->save();
    }
}
