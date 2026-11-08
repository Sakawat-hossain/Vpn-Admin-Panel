<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A user's social login ids (Facebook sign-in), see LoginController and
 * RegisterController::complete().
 */
class SocialProvider extends Model
{
    protected $fillable = [
        'user_id',
        'facebook',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
