<?php

namespace App\Jobs\Concerns;

use App\Models\ConfigServerAction;
use Illuminate\Support\Facades\File;

/**
 * SSH + deployment-log plumbing shared by the server setup jobs.
 *
 * The using class has $configJob (ConfigServerJob), $vpsUsername and
 * $vpsPassword, and lists its secrets in secretsToRedact().
 */
trait RunsServerSetup
{
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
        // VPS sees it and passes it to the script as $1 (wg-easy uses it to
        // restrict its API port).
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

    private function redact(string $text): string
    {
        foreach ($this->secretsToRedact() as $secret) {
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
