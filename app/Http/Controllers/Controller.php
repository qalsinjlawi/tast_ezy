<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Check if the authenticated user is admin or instructor
     */
    protected function isInstructorOrAdmin()
    {
        return in_array(Auth::user()->role, ['instructor', 'admin']);
    }

    /**
     * Check if the authenticated user owns the course (or is admin)
     */
    protected function userOwnsCourse($course)
    {
        return Auth::user()->role === 'admin' || $course->instructor_id === Auth::id();
    }

    /**
     * Flash success message
     */
    protected function success(string $message)
    {
        return redirect()->back()->with('success', $message);
    }

    /**
     * Flash error message
     */
    protected function error(string $message)
    {
        return redirect()->back()->with('error', $message);
    }

    /**
     * Flash info message
     */
    protected function info(string $message)
    {
        return redirect()->back()->with('info', $message);
    }

    /**
     * Update course progress percentage for a given enrollment
     */
    protected function updateCourseProgress(Enrollment $enrollment)
    {
        $course = $enrollment->course;

        $totalLessons = $course->sections()
            ->withCount('lessons')
            ->get()
            ->sum('lessons_count');

        if ($totalLessons === 0) {
            $enrollment->update(['progress_percentage' => 0.00]);
            return;
        }

        $completedLessons = LessonProgress::where('user_id', $enrollment->user_id)
            ->whereHas('lesson.section.course', function ($query) use ($course) {
                $query->where('id', $course->id);
            })
            ->where('is_completed', true)
            ->count();

        $percentage = round(($completedLessons / $totalLessons) * 100, 2);

        $enrollment->update(['progress_percentage' => $percentage]);

        // إذا اكتمل الكورس
        if ($percentage >= 100 && is_null($enrollment->completed_at)) {
            $enrollment->update(['completed_at' => now()]);
        }
    }

    /**
     * Common response for unauthorized access
     */
    protected function unauthorized()
    {
        abort(403, 'You are not authorized to perform this action.');
    }
}