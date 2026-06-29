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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    ];

}