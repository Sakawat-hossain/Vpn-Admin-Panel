<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A signed-in device of the client apps. The session token and the OpenVPN
 * password are only stored as SHA-256 hashes (they are long random strings,
 * so a fast hash is enough and allows lookups).
 */
class VpnSession extends Model
{
    public const STALE_AFTER_DAYS = 90;

    protected $fillable = [
        'user_id',
        'token_hash',
        'vpn_username',
        'vpn_password_hash',
        'device_name',
        'wg_public_key',
        'ip',
        'last_seen_at',
    ];

    protected $hidden = [
        'token_hash',
        'vpn_password_hash',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hashSecret(string $secret): string
    {
        return hash('sha256', $secret);
    }

    public static function findByToken(?string $token): ?self
    {
        if (!is_string($token) || $token === '') {
            return null;
        }
        return static::where('token_hash', static::hashSecret($token))->first();
    }

    /**
     * Forget the user's devices that haven't been used for a long time, so
     * they don't count against the device limit forever.
     */
    public static function forgetStale(User $user): void
    {
        static::where('user_id', $user->id)->where('last_seen_at', '<', now()->subDays(self::STALE_AFTER_DAYS))->delete();
    }

    /**
     * Start a session for the user.
     *
     * @return array{0: self, 1: string, 2: string} the session, its token and its OpenVPN password
     */
    public static function start(User $user, ?string $deviceName, ?string $ip): array
    {
        $token = Str::random(64);
        $vpnPassword = Str::random(32);
        $session = static::create([
            'user_id' => $user->id,
            'token_hash' => static::hashSecret($token),
            'vpn_username' => 'v' . strtolower(Str::random(23)),
            'vpn_password_hash' => static::hashSecret($vpnPassword),
            'device_name' => $deviceName ? Str::limit($deviceName, 97) : null,
            'ip' => $ip,
            'last_seen_at' => now(),
        ]);
        return [$session, $token, $vpnPassword];
    }

    public function checkVpnPassword(string $password): bool
    {
        return hash_equals($this->vpn_password_hash, static::hashSecret($password));
    }

    public function touchLastSeen(?string $ip = null): void
    {
        // Avoid a write on every call; minute resolution is plenty.
        if (!$this->last_seen_at || $this->last_seen_at->lt(now()->subMinute()) || ($ip && $ip !== $this->ip)) {
            $this->forceFill(['last_seen_at' => now(), 'ip' => $ip ?? $this->ip])->save();
        }
    }
}
