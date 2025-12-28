<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'lesson_id',
        'is_completed',
        'last_position',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // ==================== العلاقات ====================

    // الطالب اللي عنده التقدم ده
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // الدرس اللي التقدم فيه
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}