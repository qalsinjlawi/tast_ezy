<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
    ];

    // ==================== العلاقات ====================

    // الكورس اللي ينتمي له القسم
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // الدروس داخل القسم
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}