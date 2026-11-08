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

    /**
     * The servers the client apps list (v5/servers.json): enabled,
     * panel-managed OpenVPN, and the latest deployment succeeded. A server
     * whose deploy failed (wrong password, changed host key, port in use) has
     * no working OpenVPN, and the apps would keep trying it.
     */
    public function scopeListedInApps($query)
    {
        $query->where('status', 1)->where('is_ovpn', 1)->where('ovpn_managed', 1)
            ->where(fn($q) => $q->selectRaw('status')->from('config_server_jobs')
                ->whereColumn('config_server_jobs.server_id', 'servers.id')
                ->orderByDesc('id')->limit(1), 'success');
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
		'ovpn_managed',
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
        'ovpn_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'wg_password' => 'encrypted',
        'ovpn_secret' => 'encrypted',
        'ovpn_managed' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'server_id');
    }

    public function isOpenVpn(): bool
    {
        return (int) $this->is_ovpn === 1;
    }

    /**
     * OpenVPN installed by the panel: shares the panel CA and checks each
     * user's login against the panel (see OpenVpnAuthController).
     */
    public function isManagedOpenVpn(): bool
    {
        return $this->isOpenVpn() && (bool) $this->ovpn_managed;
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