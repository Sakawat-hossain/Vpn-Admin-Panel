<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    // In-app purchase rows: they verify App Store / Google Play receipts for
    // the mobile apps (API v1) and can never take a payment on the website.
    public const IN_APP_ALIASES = ['appstore', 'playstore'];

    public function scopeHasCurrency($query)
    {
        $code = settings('currency')->code;
        if ($query->getConnection()->getDriverName() === 'sqlite') {
            // SQLite (tests) has no JSON_CONTAINS; the column holds a JSON array of codes.
            return $query->where('supported_currencies', 'like', '%"' . $code . '"%');
        }
        return $query->whereRaw('JSON_CONTAINS(supported_currencies, JSON_ARRAY(?))', [$code]);
    }

    /**
     * Gateways that can take a payment at the website checkout.
     */
    public function scopeForWebCheckout($query)
    {
        return $query->whereNotIn('alias', self::IN_APP_ALIASES);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'logo',
        'fees',
        'test_mode',
        'credentials',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'credentials' => 'object',
        'supported_currencies' => 'array',
    ];
}