<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'progress_percentage',
        'status',
        'enrolled_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ==================== العلاقات ====================

    // الطالب المسجل
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // الكورس اللي مسجل فيه
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}