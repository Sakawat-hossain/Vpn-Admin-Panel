<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public function scopeMonthly($query)
    {
        $query->where('interval', 1);
    }

    public function scopeYearly($query)
    {
        $query->where('interval', 2);
    }
    public function scopeWeekly($query)
    {
        $query->where('interval', 3);
    }

    public function scopeHalfYearly($query)
    {
        $query->where('interval', 4);
    }
    public function scopeFree($query)
    {
        $query->where('is_free', 1);
    }

    public function scopeNotFree($query)
    {
        $query->where('is_free', 0);
    }

    public function isFree()
    {
        return $this->is_free;
    }

    public function isFeatured()
    {
        return $this->is_featured;
    }

    /**
     * When a subscription to this plan that starts (or is extended) at $from
     * ends: one billing interval later. Defaults to now.
     */
    public function expiryFrom(?\DateTimeInterface $from = null): Carbon
    {
        $from = $from ? Carbon::instance($from) : Carbon::now();
        switch ((int) $this->interval) {
            case 2: // Yearly
                return $from->addYear();
            case 3: // Weekly
                return $from->addWeek();
            case 4: // Half-yearly
                return $from->addMonths(6);
            default: // 1: Monthly
                return $from->addMonth();
        }
    }

    /**
     * Store product id(s) for this plan. product_id may hold several ids
     * separated by commas (e.g. different App Store and Google Play ids).
     *
     * @return string[]
     */
    public function productIds(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', (string) $this->product_id))));
    }

    public function scopeForGuests($query)
    {
        $query->where('is_free', 1)->where('login_require', 0);
    }

    public function isForGuests()
    {
        return $this->is_free && !$this->login_require;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'product_id',
        'interval',
        'price',
        'expiration',
        'advertisements',
        'custom_features',
        'is_free',
        'login_require',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'custom_features' => 'object',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
