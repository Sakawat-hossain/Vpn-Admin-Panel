<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'ip',
        'country',
        'country_code',
        'timezone',
        'location',
        'latitude',
        'longitude',
        'browser',
        'os',
    ];

    /**
     * Record a sign-in like the website login does: one row per user and IP,
     * updated on the next sign-in from that IP. $browser and $os replace the
     * values detected from the User-Agent (the client apps have no browser).
     */
    public static function recordLogin(User $user, ?string $browser = null, ?string $os = null): self
    {
        $info = ipInfo();
        $log = static::where([['user_id', $user->id], ['ip', $info->ip]])->first() ?: new static();
        $log->user_id = $user->id;
        $log->ip = $info->ip;
        $log->country = $info->location->country;
        $log->country_code = $info->location->country_code;
        $log->timezone = $info->location->timezone;
        $log->location = Str::limit($info->location->city . ', ' . $info->location->country, 60, '');
        $log->latitude = $info->location->latitude;
        $log->longitude = $info->location->longitude;
        $log->browser = Str::limit($browser ?? $info->system->browser, 60, '');
        $log->os = Str::limit($os ?? $info->system->os, 60, '');
        $log->save();
        return $log;
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
