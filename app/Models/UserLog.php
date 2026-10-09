<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Column lengths in user_logs; longer values (long city names, client-sent OS
     * strings) are cut instead of failing the insert on strict MySQL.
     */
    private const MAX_LENGTHS = [
        'ip' => 100,
        'country' => 100,
        'country_code' => 100,
        'timezone' => 150,
        'location' => 60,
        'latitude' => 60,
        'longitude' => 60,
        'browser' => 60,
        'os' => 60,
    ];

    public function setAttribute($key, $value)
    {
        if (isset(self::MAX_LENGTHS[$key]) && is_string($value)) {
            $value = mb_substr($value, 0, self::MAX_LENGTHS[$key]);
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
