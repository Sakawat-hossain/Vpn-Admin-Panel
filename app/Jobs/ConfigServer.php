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
        $sshCmd = "$sshpass ssh -p $sshPort $vpsUsername@$ip -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o ConnectTimeout=$sshTimeout -o LogLevel=QUIET";
        
        // Region-aware provisioning script. China-based servers usually can't
        // reach get.docker.com / Docker Hub, so we auto-detect that and fall
        // back to the Aliyun installer + China registry mirrors; global servers
        // take the fast direct path. The script is base64-encoded and piped to
        // bash on the target to avoid all SSH quoting issues.
        $provisionScript = <<<BASH
#!/bin/bash
set -e
WG_HOST="$ip"

# Detect a restricted network (e.g. China): can we reach Docker Hub quickly?
CN=0
if ! curl -s -o /dev/null --max-time 6 https://registry-1.docker.io/v2/; then CN=1; fi
echo "region-check: CN=\$CN"

# Install Docker if missing (use the Aliyun mirror when restricted).
if ! command -v docker >/dev/null 2>&1; then
  if [ "\$CN" = "1" ]; then
    curl -fsSL https://get.docker.com | sh -s -- --mirror Aliyun
  else
    curl -fsSL https://get.docker.com | sh
  fi
fi
systemctl enable --now docker >/dev/null 2>&1 || true

# On restricted networks, add registry mirrors so image pulls succeed.
if [ "\$CN" = "1" ]; then
  mkdir -p /etc/docker
  printf '%s' '{"registry-mirrors":["https://docker.m.daocloud.io","https://dockerproxy.com","https://docker.1panel.live","https://hub.rat.dev"]}' > /etc/docker/daemon.json
  systemctl restart docker >/dev/null 2>&1 || true
  sleep 4
fi

# Open ports on the host firewall (cloud security groups are separate).
ufw allow 51820/udp >/dev/null 2>&1 || true
ufw allow 51821/tcp >/dev/null 2>&1 || true

# (Re)deploy wg-easy — idempotent.
docker rm -f wg-easy >/dev/null 2>&1 || true
docker pull ombapit/wg-easy >/dev/null 2>&1 || true
docker run -d --name=wg-easy -e LANG=en -e WG_HOST="\$WG_HOST" -v ~/.wg-easy:/etc/wireguard -p 51820:51820/udp -p 51821:51821/tcp --cap-add=NET_ADMIN --cap-add=SYS_MODULE --sysctl net.ipv4.conf.all.src_valid_mark=1 --sysctl net.ipv4.ip_forward=1 --restart unless-stopped ombapit/wg-easy
echo "provision done (CN=\$CN)"
BASH;

        $provisionB64 = base64_encode($provisionScript);

        $cmds = [
            [
                // Whole provision (Docker install + mirrors + wg-easy) as one
                // base64-piped script so quoting/region logic stays reliable.
                'action' => "Provision server (region-aware: Docker + wg-easy)",
                'command' => "$sshCmd \"echo $provisionB64 | base64 -d | bash\"",
            ],
            [
                // Only mark success once the wg-easy API actually answers (~60s).
                'action' => "Verify wg-easy is reachable",
                'command' => "$sshCmd \"curl -sf --retry 30 --retry-delay 2 --retry-connrefused http://127.0.0.1:51821/ >/dev/null\"",
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
                $action->result = "Success";
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
