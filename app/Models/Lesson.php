<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'section_id',
        'title',
        'type',
        'video_url',
        'content',
        'attachment_url',
        'duration',
        'is_free',
        'order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_free' => 'boolean',
    ];

    // ==================== العلاقات ====================

    // القسم اللي ينتمي له الدرس
    public function section()
    {
        return $this->belongsTo(CourseSection::class);
    }

    // تقدم الطلاب في الدرس ده
    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }
}