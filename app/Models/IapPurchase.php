<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IapPurchase extends Model
{
    public const PLATFORM_ITUNES = 'itunes';
    public const PLATFORM_GOOGLE_PLAY = 'googleplay';

    protected $fillable = [
        'platform',
        'original_transaction_id',
        'user_id',
        'plan_id',
        'product_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
