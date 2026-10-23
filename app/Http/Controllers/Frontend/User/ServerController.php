<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\OpenVpnProfile;
use Illuminate\Support\Str;

class ServerController extends Controller
{
    /**
     * Download an OpenVPN server's client profile (.ovpn) for use in
     * OpenVPN Connect, Passepartout and similar apps. Same rules as the
     * app's connect endpoint: the server must be enabled and the user
     * entitled to it.
     */
    public function openvpn(Server $server)
    {
        $user = userAuthInfo();

        abort_if($user->isBanned(), 403);
        abort_unless($server->isEnabled() && $server->isOpenVpn(), 404);
        abort_unless($user->canUseServer($server), 403, lang('A premium subscription is required for this server', 'account'));

        $profile = app(OpenVpnProfile::class)->clientProfile($server);
        abort_if(trim($profile) === '', 404);

        $filename = Str::slug(settings('general')->site_name . ' ' . $server->country . ' ' . $server->state . ' ' . $server->id) . '.ovpn';

        return response($profile, 200, [
            'Content-Type' => 'application/x-openvpn-profile',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
