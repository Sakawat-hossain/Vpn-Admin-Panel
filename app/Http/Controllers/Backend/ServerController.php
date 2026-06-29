<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ConfigServerAction;
use App\Models\ConfigServerJob;
use App\Models\Server;
use App\Jobs\ConfigServer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;
use GuzzleHttp\Client;

class ServerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $freeServers = Server::free()->select('servers.*', 'config_server_jobs.status AS job_status')->join('config_server_jobs', 'servers.id', '=', 'config_server_jobs.server_id', 'left')->get();
        $premiumServers = Server::premium()->select('servers.*', 'config_server_jobs.status AS job_status')->join('config_server_jobs', 'servers.id', '=', 'config_server_jobs.server_id', 'left')->get();
        return view('backend.servers.index', [
            'countries' => listCountries(),
            'statusOptions' => ['1' => 'Enabled', '0' => 'Disabled'],
            'recommendOptions' => ['1' => 'True', '0' => 'False'],
            'serverOptions' => ['1' => 'Premium', '0' => 'Free'],
            'freeServers' => $freeServers,
            'premiumServers' => $premiumServers,
        ]);
    }

    /**
     * Get deployment detail
     *
     * @return \Illuminate\Http\Response as JSON
     */
    public function getDeployment(Server $server)
    {
        $data = ConfigServerAction::select('config_server_actions.*')
            ->join('config_server_jobs', 'config_server_actions.config_job_id', '=', 'config_server_jobs.id')
            ->join('servers', 'config_server_jobs.server_id', '=', 'servers.id')
            ->where('servers.id', $server->id)
            ->get();
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
        $validator = Validator::make($request->all(), [
            'country' => ['required'],
            'state' => ['required'],
            'latitude' => ['required'],
            'longitude' => ['required'],
            'status' => ['required'],
            'ip_address' => ['required'],
            'recommended' => ['required'],
            'is_premium' => ['required'],
            // 'ssh_port' => ['required'],
            // 'vps_username' => ['required'],
            // 'vps_password' => ['required'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }

        $createServer = Server::create([
            'country' => $request->country,
            'state' => $request->state,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status,
            'ip_address' => $request->ip_address,
            'recommended' => $request->recommended,
            'is_premium' => $request->is_premium,
            'is_ovpn' => $request->isOVPN ? 1 : 0,
            'ovpn_config' => $request->isOVPN ? $request->ovpn_config ?? '' : '',
        ]);
        if ($createServer) {
            if ($request->installWgEasy === "on") {
                $job = new ConfigServerJob();
                $job->server_id = $createServer->id;
                $job->ip = $request->ip_address;
                $job->ssh_port = $request->ssh_port;
                $job->vps_username = $request->vps_username;
                $job->vps_password = $request->vps_password ?: "";
                $job->status = 'running';
                $job->save();

                $action = new ConfigServerAction();
                $action->config_job_id = $job->id;
                $action->action = "Server config. IP={$job->ip}";
                $action->result_code = 0;
                $action->result = "Started";
                $action->save();

                ConfigServer::dispatch($job);
            }

            toastr()->success(admin_lang('Added successfully'));
            return back();
        }
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
        // get client config
        $url = "http://$server->ip_address:51821/api/wireguard/client";
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $client = new Client();
        $response = $client->get($url, [
            'headers' => $headers,
        ]);
        $statusCode = $response->getStatusCode();
        if ($statusCode == 200) {
            $logs = json_decode($response->getBody());
        }
        return view('backend.servers.show', ['server' => $server, 'logs' => $logs]);
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
        $validator = Validator::make($request->all(), [
            'country' => ['required'],
            'state' => ['required'],
            'latitude' => ['required'],
            'longitude' => ['required'],
            'status' => ['required'],
            'ip_address' => ['required'],
            'recommended' => ['required'],
            'is_premium' => ['required'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        $updateServer = $server->update([
            'country' => $request->country,
            'state' => $request->state,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status,
            'ip_address' => $request->ip_address,
            'recommended' => $request->recommended,
            'is_premium' => $request->is_premium,
            'is_ovpn' => $request->isOVPN ? 1 : 0,
            'ovpn_config' => $request->isOVPN ? $request->ovpn_config ?? '' : '',
        ]);
        if ($updateServer) {
            toastr()->success(admin_lang('Updated successfully'));
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Server  $server
     * @return \Illuminate\Http\Response
     */
    public function destroy(Server $server)
    {
        $server->delete();
        toastr()->success(admin_lang('Deleted successfully'));
        return back();
    }
}
