<?php

namespace App\Models;

use App\Jobs\RevokeWireGuardPeer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 1;
    public const STATUS_CANCELLED = 0;

    protected static function booted()
    {
        // The reminder flags belong to one expiry date: a new expiry date
        // (renewal, plan change, admin edit) may be reminded again.
        static::saving(function (Subscription $subscription) {
            if ($subscription->exists && $subscription->isDirty('expiry_at')) {
                if (!$subscription->isDirty('about_to_expire_reminder')) {
                    $subscription->about_to_expire_reminder = false;
                }
                if (!$subscription->isDirty('expired_reminder')) {
                    $subscription->expired_reminder = false;
                }
            }
        });
    }

    /**
     * Put a user without a subscription on the free plan (sign-up). Returns
     * the user's existing subscription if there already is one, and null
     * when no free plan is configured.
     */
    public static function startFree(User $user, ?Plan $plan = null): ?self
    {
        $plan = $plan ?: freePlan();
        if (!$plan) {
            return null;
        }
        $existing = $user->subscription()->first();
        if ($existing) {
            return $existing;
        }
        return static::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'expiry_at' => $plan->expiryFrom(),
            'is_viewed' => 0,
        ]);
    }

    /**
     * Move this subscription to the free plan (premium expired, cancelled or
     * removed by an admin). The free plan's own interval sets the expiry
     * unless one is given; the status is left as it is. A WireGuard config
     * keeps working until its peer
     * is removed, so leaving premium revokes the user's peer on a premium
     * server.
     */
    public function moveToFreePlan(Plan $freePlan, ?\DateTimeInterface $expiryAt = null): void
    {
        $wasPremium = $this->plan && !$this->plan->isFree();

        $this->update([
            'plan_id' => $freePlan->id,
            'expiry_at' => $expiryAt ?? $freePlan->expiryFrom(),
        ]);
        $this->setRelation('plan', $freePlan);

        $user = $this->user;
        if ($wasPremium && $user && $user->servers && $user->servers->isPremium()) {
            RevokeWireGuardPeer::forUser($user);
        }
    }

    /**
     * Whether an App Store / Google Play purchase covers this subscription up
     * to its expiry date. Store subscriptions renew on their own (the app
     * re-validates the receipt), so the reminder and "expired" mails skip them.
     */
    public function isCoveredByStorePurchase(): bool
    {
        if (!$this->expiry_at) {
            return false;
        }
        return IapPurchase::where('user_id', $this->user_id)
            ->where('plan_id', $this->plan_id)
            ->where('expires_at', '>=', $this->expiry_at)
            ->exists();
    }

    public function scopeExpired($query)
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->where('expiry_at', '<', Carbon::now());
    }

    public function isExpired()
    {
        return $this->status == self::STATUS_ACTIVE &&
        !$this->plan->isFree() && $this->expiry_at < Carbon::now();
    }

    public function scopeIsAboutToExpire($query)
    {
        $now = Carbon::now();
        $expiryThreshold = $now->copy()->addDays(settings('subscription')->about_to_expire_reminder);
        return $query->whereHas('plan', function ($planQuery) {
            $planQuery->where('is_free', false);
        })->where('status', self::STATUS_ACTIVE)->where('expiry_at', '>=', $now)->where('expiry_at', '<=', $expiryThreshold);
    }

    public function isAboutToExpire()
    {
        $expiry = Carbon::parse($this->expiry_at);
        $now = Carbon::now();
        $daysLeft = $expiry->diffInDays($now);
        return !$this->plan->isFree() && $daysLeft <= settings('subscription')->about_to_expire_reminder && $now < $expiry;
    }

    public function scopeFree($query)
    {
        $query->whereHas('plan', function ($query) {
            $query->where('is_free', true);
        });
    }

    public function scopeNotFree($query)
    {
        $query->whereHas('plan', function ($query) {
            $query->where('is_free', false);
        });
    }

    public function isFree()
    {
        return $this->plan->isFree();
    }

    public function scopeActive($query)
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->where('expiry_at', '>', Carbon::now());
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE && $this->expiry_at > Carbon::now();
    }

    public function scopeCancelled($query)
    {
        $query->where('status', self::STATUS_CANCELLED);
    }

    public function isCancelled()
    {
        return $this->status == self::STATUS_CANCELLED;
    }

    public function scopeAboutToExpireReminderNotSent($query)
    {
        return $query->where('about_to_expire_reminder', false);
    }

    public function scopeExpiredReminderNotSent($query)
    {
        return $query->where('expired_reminder', false);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'expiry_at',
        'about_to_expire_reminder',
        'expired_reminder',
        'is_viewed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expiry_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

}