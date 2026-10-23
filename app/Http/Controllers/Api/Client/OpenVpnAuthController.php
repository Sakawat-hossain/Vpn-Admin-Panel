<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use App\Models\VpnSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Called by panel-managed OpenVPN servers (auth-user-pass-verify script) for
 * every connecting user. A server identifies itself with X-Server-Id and its
 * own secret as the bearer token.
 *
 * Accepted logins:
 *  - an app session's VPN username/password (from session/new), or
 *  - the account email and password (downloaded .ovpn profiles).
 *
 * 204 = let the user in, anything else = refuse.
 */
class OpenVpnAuthController extends Controller
{
    private const MAX_ATTEMPTS = 10;
    private const LIMIT_WINDOW_SECONDS = 600;

    // Username the install script uses to check that the server can reach the panel.
    public const CHECK_USERNAME = '__panel_check__';

    public function __invoke(Request $request)
    {
        $server = Server::find((int) $request->header('X-Server-Id'));
        $secret = (string) $request->bearerToken();
        if (!$server || !$server->isManagedOpenVpn() || $secret === '' || !is_string($server->ovpn_secret)
            || !hash_equals($server->ovpn_secret, $secret)) {
            return response()->json(['message' => 'Unknown server'], 403);
        }

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');
        if ($username === self::CHECK_USERNAME) {
            return response()->noContent();
        }
        if (!$server->isEnabled()) {
            return response()->json(['message' => 'Server disabled'], 403);
        }
        if ($username === '' || $password === '') {
            return response()->json(['message' => 'Missing credentials'], 401);
        }

        $limiterKey = 'ovpn-auth:' . $server->id . '|' . Str::lower($username);
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            return response()->json(['message' => 'Too many attempts'], 429);
        }

        [$user, $session] = $this->resolve($username, $password);
        if (!$user) {
            RateLimiter::hit($limiterKey, self::LIMIT_WINDOW_SECONDS);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        RateLimiter::clear($limiterKey);

        if ($user->isBanned()) {
            return response()->json(['message' => 'Account blocked'], 403);
        }
        if (emailVerificationRequired() && is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified'], 403);
        }
        if (!$user->canUseServer($server)) {
            return response()->json(['message' => 'Premium subscription required'], 403);
        }

        // users.server_id is left alone: it tracks the user's WireGuard peer,
        // and changing it would get that peer revoked (wg:prune).
        $session?->touchLastSeen();
        return response()->noContent();
    }

    /**
     * @return array{0: ?User, 1: ?VpnSession}
     */
    private function resolve(string $username, string $password): array
    {
        $session = VpnSession::where('vpn_username', $username)->first();
        if ($session) {
            return $session->checkVpnPassword($password) ? [$session->user, $session] : [null, null];
        }
        $user = User::where('email', Str::lower($username))->first();
        if ($user && Hash::check($password, $user->password)) {
            return [$user, null];
        }
        return [null, null];
    }
}
