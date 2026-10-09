<?php

namespace App\Models;

use App\Notifications\UserResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public function isSubscribed()
    {
        return $this->subscription;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'email',
        'address',
        'avatar',
        'client_id',
        'password',
        'api_token',
        'google2fa_status',
        'google2fa_secret',
        'is_viewed',
        'status',
        'email_verified_at',
        'email_token',
        'verification_code',
        'verification_code_sent_at',
        'dns',
        'download',
        'upload',
        'server_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'api_token',
        'email_token',
        'verification_code',
        'verification_code_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'address' => 'object',
        'email_verified_at' => 'datetime',
        'verification_code_sent_at' => 'datetime',
    ];

    /**
     * Name of this user's peer on a wg-easy server (wg-easy uses it as the client id).
     */
    public function wgClientName(): string
    {
        return 'wg' . $this->id;
    }

    public function isBanned(): bool
    {
        return (int) $this->status === 0;
    }

    /**
     * Whether the user currently has a paid, unexpired subscription.
     */
    public function hasPremiumAccess(): bool
    {
        $subscription = $this->subscription;
        if (!$subscription || !$subscription->plan) {
            return false;
        }
        return !$subscription->plan->isFree() && $subscription->isActive();
    }

    /**
     * Whether the user may connect to the given server.
     */
    public function canUseServer(Server $server): bool
    {
        return (int) $server->is_premium !== Server::STATUS_PREMIUM || $this->hasPremiumAccess();
    }

    /**
     * Issue a fresh API token, invalidating the previous one.
     */
    public function rotateApiToken(): string
    {
        $token = hash('sha256', \Illuminate\Support\Str::random(60));
        $this->forceFill(['api_token' => $token])->save();
        return $token;
    }

    /**
     * Decrypt the user's google_2fa secret.
     *
     * @param  string  $value
     * @return string
     */
    public function getGoogle2faSecretAttribute($value)
    {
        return decrypt($value);
    }

    /**
     * Send Password Reset Notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new UserResetPasswordNotification($token));
    }

    /**
     * Send Email Verification Notification.
     */
    public function sendEmailVerificationNotification()
    {
        if (settings('actions')->email_verification_status) {
            $this->notify(new VerifyEmailNotification());
        }
    }

    /**
     * Relationships
     */
    public function logs()
    {
        return $this->hasOne(UserLog::class)->latest();
    }
    public function listLogs()
    {
        return $this->hasMany(UserLog::class);
    }
    public function socialProviders()
    {
        return $this->hasMany(SocialProvider::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function servers()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }


}
