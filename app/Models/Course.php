<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'instructor_id',
        'category_id',
        'price',
        'thumbnail',
        'level',
        'is_published',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_published' => 'boolean',
    ];

    // ==================== العلاقات ====================
/**
 * Scope to get only published courses
 */
public function scopePublished($query)
{
    return $query->where('is_published', true);
}
    // المعلم (Instructor) اللي أنشأ الكورس
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    // التصنيف اللي ينتمي له الكورس
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // الأقسام داخل الكورس
    public function sections()
    {
        return $this->hasMany(CourseSection::class)->orderBy('order');
    }

    // الطلاب المسجلين في الكورس
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    // التقييمات على الكورس
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // دفعات شراء الكورس
    public function coursePayments()
    {
        return $this->hasMany(CoursePayment::class);
    }
    /**
 * Check if the current user is enrolled in the course
 */
public function isEnrolled($userId = null)
{
    $userId = $userId ?? auth()->id();

    if (!$userId) return false;

    return $this->enrollments()->where('user_id', $userId)->exists();
}

/**
 * Check if the course is free
 */
public function isFree()
{
    return $this->price == 0;
}
}