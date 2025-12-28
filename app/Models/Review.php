<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'rating',
        'comment',
        'is_approved',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_approved' => 'boolean',
    ];

    // ==================== العلاقات ====================

    // الطالب اللي كتب التقييم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // الكورس اللي التقييم عليه
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}