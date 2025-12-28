<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'plan_name',
        'price',
        'billing_period',
        'status',
        'starts_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==================== العلاقات ====================

    // المستخدم اللي عنده الاشتراك ده
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // دفعات الاشتراك ده
    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}