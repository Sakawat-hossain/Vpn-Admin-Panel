<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\VpnSession;

/**
 * Devices signed in to the client apps. Signing one out deletes its session,
 * which also invalidates its VPN password on the next connection check.
 */
class DevicesController extends Controller
{
    public function index()
    {
        $user = userAuthInfo();
        return view('frontend.user.devices', [
            'sessions' => $user->vpnSessions()->orderByDesc('last_seen_at')->orderByDesc('id')->get(),
            'limit' => deviceLimit(),
        ]);
    }

    public function destroy(VpnSession $session)
    {
        abort_unless((int) $session->user_id === (int) userAuthInfo()->id, 404);
        $session->delete();
        toastr()->success(lang('The device has been signed out', 'account'));
        return redirect()->route('user.devices');
    }
}
