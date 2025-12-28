<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'bio',
        'subscription_type',
        'course_limit',
        'is_active',
        'email_verified_at',   // ← أضف هذا السطر
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'course_limit' => 'integer',          // تحسين: يضمن أن يكون عدد صحيح
        'role' => 'string',                   // تحسين: واضح جدًا
    ];

    // ==================== Methods مساعدة للأدوار ====================

    /**
     * Check if the user is a student
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if the user is an instructor
     */
    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ==================== العلاقات ====================

    // الكورسات اللي المستخدم مسجل فيها (كطالب)
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    // الكورسات اللي المستخدم أنشأها (كمعلم)
    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    // تقييمات المستخدم
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // تقدم الدروس
    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    // الاشتراكات
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    // دفعات الكورسات
    public function coursePayments()
    {
        return $this->hasMany(CoursePayment::class);
    }

    // دفعات الاشتراكات
    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}