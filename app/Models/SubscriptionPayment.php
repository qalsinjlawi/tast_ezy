<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_id',
        'status',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // ==================== العلاقات ====================

    // المستخدم اللي دفع
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // الاشتراك اللي تم الدفع عليه
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}