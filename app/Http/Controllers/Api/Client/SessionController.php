<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VpnSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Sign-in for the desktop/mobile client apps, in the request/response shape of
 * the IVPN client API (v4/session/*) so the IVPN-based apps can use it with
 * small changes: the login sends the account email as "username" plus a
 * "password" field (IVPN has no passwords, only account ids).
 *
 * Every response is JSON with a "status" field holding an IVPN status code.
 */
class SessionController extends Controller
{
    use ClientResponses;

    private const MAX_LOGIN_ATTEMPTS = 10;
    private const LIMIT_WINDOW_SECONDS = 600;

    public function create(Request $request)
    {
        $email = Str::lower(trim((string) $request->input('username')));
        $password = (string) $request->input('password');
        if ($email === '' || $password === '') {
            return $this->clientError(self::UNAUTHORIZED, __('Enter your email and password'));
        }

        $limiterKey = 'client-login:' . $email . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_LOGIN_ATTEMPTS)) {
            return $this->clientError(429, __('Too many login attempts. Please try again in :minutes minutes.', [
                'minutes' => ceil(RateLimiter::availableIn($limiterKey) / 60),
            ]));
        }

        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($limiterKey, self::LIMIT_WINDOW_SECONDS);
            return $this->clientError(self::UNAUTHORIZED, __('The email or password entered is incorrect'));
        }
        if ($user->isBanned()) {
            return $this->clientError(self::UNAUTHORIZED, __('Your account has been blocked'));
        }
        if (emailVerificationRequired() && is_null($user->email_verified_at)) {
            return $this->clientError(self::ACCOUNT_NOT_ACTIVE, __('Please verify your email address first'));
        }

        if ($user->google2fa_status) {
            $code = trim((string) $request->input('confirmation'));
            if ($code === '') {
                return $this->clientError(self::TWO_FA_REQUIRED, __('Enter the code from your authenticator app'));
            }
            if (!app('pragmarx.google2fa')->verifyKey($user->google2fa_secret, $code)) {
                RateLimiter::hit($limiterKey, self::LIMIT_WINDOW_SECONDS);
                return $this->clientError(self::TWO_FA_INVALID, __('Invalid OTP code'));
            }
        }
        RateLimiter::clear($limiterKey);

        VpnSession::forgetStale($user);
        $limit = deviceLimit();
        if ($limit > 0) {
            $sessions = $user->vpnSessions()->orderBy('last_seen_at')->orderBy('id')->get();
            if ($sessions->count() >= $limit) {
                if (!$request->boolean('force')) {
                    return response()->json([
                        'status' => self::SESSION_LIMIT,
                        'message' => __('You have reached the device limit. Sign out on another device or continue to sign it out.'),
                        'data' => $this->serviceStatus($user),
                    ]);
                }
                // "force": sign out the least recently used devices to make room.
                $sessions->take($sessions->count() - $limit + 1)->each->delete();
            }
        }

        [$session, $token, $vpnPassword] = VpnSession::start($user, $this->deviceName($request), $request->ip());

        return response()->json([
            'status' => self::SUCCESS,
            'token' => $token,
            'vpn_username' => $session->vpn_username,
            'vpn_password' => $vpnPassword,
            'device_name' => $session->device_name,
            'service_status' => $this->serviceStatus($user),
            'wireguard' => $this->wireguardUnavailable($request),
        ]);
    }

    public function status(Request $request)
    {
        $session = VpnSession::findByToken($request->input('session_token'));
        if (!$session || !$session->user || $session->user->isBanned()) {
            return $this->clientError(self::SESSION_NOT_FOUND, __('Session not found'));
        }
        $session->touchLastSeen($request->ip());
        return response()->json([
            'status' => self::SUCCESS,
            'device_name' => $session->device_name,
            'service_status' => $this->serviceStatus($session->user),
        ]);
    }

    public function delete(Request $request)
    {
        $session = VpnSession::findByToken($request->input('session_token'));
        if (!$session) {
            return $this->clientError(self::SESSION_NOT_FOUND, __('Session not found'));
        }
        $session->delete();
        return response()->json(['status' => self::SUCCESS]);
    }

    /**
     * WireGuard keys for the client apps are not supported yet: the panel's
     * WireGuard servers (wg-easy) create their own keys per peer. The apps
     * connect over OpenVPN until then.
     */
    public function wireguardKeySet(Request $request)
    {
        if (!VpnSession::findByToken($request->input('session_token'))) {
            return $this->clientError(self::SESSION_NOT_FOUND, __('Session not found'));
        }
        return $this->clientError(400, __('WireGuard is not available in this app yet, please use OpenVPN'));
    }

    private function wireguardUnavailable(Request $request): array
    {
        if (!$request->filled('wg_public_key')) {
            return ['status' => self::SUCCESS];
        }
        return ['status' => 400, 'message' => __('WireGuard is not available in this app yet, please use OpenVPN')];
    }

    private function deviceName(Request $request): ?string
    {
        $name = trim((string) $request->input('device_name'));
        if ($name === '') {
            $name = trim(Str::limit((string) $request->userAgent(), 60, ''));
        }
        return $name !== '' ? $name : null;
    }
}
