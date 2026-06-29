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
        
        $cmds = [
            [
                'action' => "Install Docker",
                'command' => "$sshCmd \"curl -sSL https://get.docker.com | sh\"",
            ],
            [
                'action' => "Install Wg Easy",
                'command' => "$sshCmd \"docker run -d "
                . "--name=wg-easy "
                . "-e LANG=en "
                . "-e WG_HOST=$ip "
                // . "-e PASSWORD=your-wg-easy-password-here "
                . "-v ~/.wg-easy:/etc/wireguard "
                . "-p 51820:51820/udp "
                . "-p 51821:51821/tcp "
                . "--cap-add=NET_ADMIN "
                . "--cap-add=SYS_MODULE "
                . "--sysctl=\"net.ipv4.conf.all.src_valid_mark=1\" "
                . "--sysctl=\"net.ipv4.ip_forward=1\" "
                . "--restart unless-stopped "
                . "ombapit/wg-easy\""
            ]
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
