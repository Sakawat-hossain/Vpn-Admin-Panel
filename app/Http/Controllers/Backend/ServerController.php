<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\WgEasyException;
use App\Http\Controllers\Controller;
use App\Jobs\ConfigServer;
use App\Models\ConfigServerAction;
use App\Models\ConfigServerJob;
use App\Models\Server;
use App\Services\WgEasyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class ServerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.servers.index', [
            'countries' => listCountries(),
            'statusOptions' => ['1' => 'Enabled', '0' => 'Disabled'],
            'recommendOptions' => ['1' => 'True', '0' => 'False'],
            'serverOptions' => ['1' => 'Premium', '0' => 'Free'],
            'freeServers' => $this->withLatestJobStatus(Server::free())->get(),
            'premiumServers' => $this->withLatestJobStatus(Server::premium())->get(),
        ]);
    }

    /**
     * Add the status of the server's most recent deployment as job_status
     * (one row per server, even after several deployments).
     */
    private function withLatestJobStatus($query)
    {
        return $query->select('servers.*')->addSelect(['job_status' => ConfigServerJob::select('status')
            ->whereColumn('config_server_jobs.server_id', 'servers.id')
            ->orderByDesc('id')
            ->limit(1)]);
    }

    /**
     * Get deployment detail (latest deployment only)
     *
     * @return \Illuminate\Http\Response as JSON
     */
    public function getDeployment(Server $server)
    {
        $job = ConfigServerJob::where('server_id', $server->id)->latest('id')->first();
        $data = $job
            ? ConfigServerAction::where('config_job_id', $job->id)->orderBy('order')->orderBy('id')->get(['action', 'result', 'result_code'])
            : collect();
        if ($data->count() > 0) {
            return response()->json((object) ['empty' => false, 'data' => $data]);
        } else {
            return response()->json((object) ['empty' => true]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return abort(404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $install = $request->installWgEasy === 'on' && !$request->isOVPN;
        $validator = Validator::make($request->all(), $this->rules($request) + ($install ? $this->sshRules() : []));
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back()->withInput($request->except('vps_password'));
        }

        $createServer = Server::create($this->serverData($request));
        if ($createServer) {
            if ($install) {
                $this->dispatchDeployment($createServer, $request);
            }

            toastr()->success(admin_lang('Added successfully'));
            return back();
        }
    }

    /**
     * (Re)install wg-easy on an existing server. Existing peers are kept
     * (they live in ~/.wg-easy on the VPS); this also applies the API password
     * and firewall to servers deployed by older versions of the panel.
     */
    public function deploy(Request $request, Server $server)
    {
        if ($server->isOpenVpn()) {
            toastr()->error(admin_lang('wg-easy cannot be installed on an OpenVPN server'));
            return back();
        }
        $validator = Validator::make($request->all(), $this->sshRules());
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        $this->dispatchDeployment($server, $request);
        toastr()->success(admin_lang('Deployment started'));
        return back();
    }

    private function dispatchDeployment(Server $server, Request $request): void
    {
        $job = new ConfigServerJob();
        $job->server_id = $server->id;
        $job->ip = $server->ip_address;
        $job->ssh_port = (int) $request->ssh_port;
        $job->vps_username = $request->vps_username;
        // The password is only carried inside the encrypted queue payload.
        $job->vps_password = '';
        $job->status = 'running';
        $job->save();

        $action = new ConfigServerAction();
        $action->config_job_id = $job->id;
        $action->order = 0;
        $action->action = "Server config. IP={$job->ip}";
        $action->result_code = 0;
        $action->result = "Started";
        $action->save();

        ConfigServer::dispatch($job, (string) $request->vps_username, (string) $request->vps_password);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function show(Server $server)
    {
        $logs = [];
        $error = null;
        if ($server->isOpenVpn()) {
            $error = admin_lang('This is an OpenVPN server; peers are not managed by the panel.');
        } else {
            try {
                $logs = json_decode(json_encode(WgEasyClient::for($server)->listClients()));
            } catch (WgEasyException $e) {
                report($e);
                $error = admin_lang('Could not reach wg-easy on this server') . ': ' . $e->getMessage();
            }
        }
        return view('backend.servers.show', ['server' => $server, 'logs' => $logs, 'error' => $error]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function edit(Server $server)
    {
        $form = (object) [
            'countries' => listCountries(),
            'statusOptions' => ['1' => 'Enabled', '0' => 'Disabled'],
            'recommendOptions' => ['1' => 'True', '0' => 'False'],
            'serverOptions' => ['1' => 'Premium', '0' => 'Free'],
        ];
        return view('backend.servers.edit', ['server' => $server, 'form' => $form]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Server $server)
    {
        $validator = Validator::make($request->all(), $this->rules($request));
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        $updateServer = $server->update($this->serverData($request));
        if ($updateServer) {
            toastr()->success(admin_lang('Updated successfully'));
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * Users assigned to the server are detached (server_id set to NULL), not deleted.
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function destroy(Server $server)
    {
        DB::transaction(function () use ($server) {
            $server->users()->update(['server_id' => null]);
            $jobIds = ConfigServerJob::where('server_id', $server->id)->pluck('id');
            ConfigServerAction::whereIn('config_job_id', $jobIds)->delete();
            ConfigServerJob::whereIn('id', $jobIds)->delete();
            $server->delete();
        });
        toastr()->success(admin_lang('Deleted successfully'));
        return back();
    }

    private function rules(Request $request): array
    {
        return [
            'country' => ['required', 'string', 'max:191'],
            'state' => ['required', 'string', 'max:191'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['required', 'in:0,1'],
            'ip_address' => ['required', 'string', 'max:191', function ($attribute, $value, $fail) {
                if (!filter_var($value, FILTER_VALIDATE_IP) && !filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    $fail(admin_lang('The IP address must be a valid IP or hostname'));
                }
            }],
            'recommended' => ['required', 'in:0,1'],
            'is_premium' => ['required', 'in:0,1'],
            'ovpn_config' => $request->isOVPN ? ['required', 'string', 'max:100000', function ($attribute, $value, $fail) {
                if ($error = self::ovpnConfigError($value)) {
                    $fail($error);
                }
            }] : ['nullable'],
        ];
    }

    private function sshRules(): array
    {
        return [
            'ssh_port' => ['required', 'integer', 'between:1,65535'],
            'vps_username' => ['required', 'string', 'max:32', 'regex:/^[a-z_][a-z0-9_-]*$/i'],
            'vps_password' => ['required', 'string', 'max:255'],
        ];
    }

    private function serverData(Request $request): array
    {
        return [
            'country' => $request->country,
            'state' => $request->state,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status,
            'ip_address' => $request->ip_address,
            'recommended' => $request->recommended,
            'is_premium' => $request->is_premium,
            'is_ovpn' => $request->isOVPN ? 1 : 0,
            'ovpn_config' => $request->isOVPN ? self::normalizeOvpnConfig($request->ovpn_config) : '',
        ];
    }

    public static function normalizeOvpnConfig(?string $config): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", (string) $config)) . "\n";
    }

    /**
     * Sanity-check an OpenVPN client profile. Returns an error message or null.
     *
     * The profile is handed to every user of the server as-is, so it must be a
     * self-contained client config: client mode, at least one remote, and the CA
     * inline. Server-side or script directives are rejected.
     */
    public static function ovpnConfigError(?string $config): ?string
    {
        $config = (string) $config;
        $lines = preg_split('/\R/', $config);
        $directives = [];
        $blocks = [];
        $inBlock = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($inBlock !== null) {
                if (strcasecmp($line, "</$inBlock>") === 0) {
                    $inBlock = null;
                }
                continue;
            }
            if (preg_match('/^<([a-z0-9-]+)>$/i', $line, $m)) {
                $inBlock = $m[1];
                $blocks[strtolower($m[1])] = true;
                continue;
            }
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            $directives[strtolower(strtok($line, " \t"))] = true;
        }
        if ($inBlock !== null) {
            return admin_lang('OVPN config: unclosed') . " <$inBlock> " . admin_lang('block');
        }
        if (!isset($directives['client']) && !(isset($directives['tls-client']) && isset($directives['pull']))) {
            return admin_lang('OVPN config must be a client profile (missing "client")');
        }
        if (!isset($directives['remote']) && !isset($blocks['connection'])) {
            return admin_lang('OVPN config has no "remote" server address');
        }
        if (!isset($blocks['ca'])) {
            return admin_lang('OVPN config must include the CA certificate inline (<ca>...</ca>)');
        }
        // Mobile clients can't read files referenced by path; everything must be inline.
        foreach (['ca', 'cert', 'key', 'tls-auth', 'tls-crypt', 'tls-crypt-v2', 'pkcs12', 'secret'] as $fileDirective) {
            if (isset($directives[$fileDirective])) {
                return admin_lang('OVPN config references a file; paste it inline instead') . ": $fileDirective";
            }
        }
        foreach (['up', 'down', 'route-up', 'route-pre-down', 'ipchange', 'learn-address', 'tls-verify', 'auth-user-pass-verify', 'client-connect', 'client-disconnect', 'script-security', 'plugin', 'server', 'mode'] as $forbidden) {
            if (isset($directives[$forbidden])) {
                return admin_lang('OVPN config contains a directive not allowed in a client profile') . ": $forbidden";
            }
        }
        return null;
    }
}
