<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;

    public const STATUS_FREE = 0;
    public const STATUS_PREMIUM = 1;

    public function scopeFree($query)
    {
        $query->where('is_premium', self::STATUS_FREE);
    }
    
    public function scopePremium($query)
    {
        $query->where('is_premium', self::STATUS_PREMIUM);
    }

    public function printStatus()
    {
        return $this->status == 1 ? 'Enable' : 'Disable';
    }

    public function printRecommended()
    {
        return $this->recommended == 1 ? 'True' : 'False';
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
		'country',
		'state',
		'latitude',
		'longitude',
		'status',
		'ip_address',
		'recommended',
		'is_premium',
		'is_ovpn',
		'ovpn_config',
    ];

    /**
     * Never expose the OpenVPN profile or the wg-easy password in listings;
     * the profile is only handed out by the connect endpoint to entitled users.
     *
     * @var array
     */
    protected $hidden = [
        'ovpn_config',
        'wg_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'wg_password' => 'encrypted',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'server_id');
    }

    public function isOpenVpn(): bool
    {
        return (int) $this->is_ovpn === 1;
    }

    public function isPremium(): bool
    {
        return (int) $this->is_premium === self::STATUS_PREMIUM;
    }

    public function isEnabled(): bool
    {
        return (int) $this->status === 1;
    }

}