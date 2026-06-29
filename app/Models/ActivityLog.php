<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'activity_type',
        'request_data',
        'ip',
        'user_agent',
        'user_id',
        'browser',
        'platform',
        'device',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
