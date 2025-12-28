<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonProgressController extends Controller
{
    /**
     * Update or create lesson progress for the authenticated student
     * Called via AJAX from the lesson player
     */
    public function update(Request $request, Lesson $lesson)
    {
        $user = Auth::user();

        // التأكد إن الطالب مسجل في الكورس اللي فيه الدرس ده
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->section->course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        $validated = $request->validate([
            'last_position' => 'nullable|integer|min:0',
            'is_completed'  => 'sometimes|boolean',
        ]);

        $progress = LessonProgress::updateOrCreate(
            [
                'user_id'   => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'last_position' => $validated['last_position'] ?? 0,
                'is_completed'  => $request->has('is_completed'),
                'completed_at'  => $request->has('is_completed') ? now() : null,
            ]
        );

        // تحديث تقدم الكورس الكلي تلقائيًا
        $this->updateCourseProgress($enrollment);

        return response()->json([
            'message' => 'Progress saved successfully.',
            'progress' => $progress,
            'course_progress' => round($enrollment->fresh()->progress_percentage, 2),
        ]);
    }

    /**
     * Get current progress for a lesson (optional - for initial load)
     */
    public function show(Lesson $lesson)
    {
        $user = Auth::user();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->section->course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Not enrolled.'], 403);
        }

        $progress = LessonProgress::firstOrCreate(
            [
                'user_id'   => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'last_position' => 0,
                'is_completed'  => false,
            ]
        );

        return response()->json($progress);
    }

    /**
     * Update overall course progress percentage
     */
    private function updateCourseProgress(Enrollment $enrollment)
    {
        $course = $enrollment->course;

        $totalLessons = $course->sections()->withCount('lessons')->get()->sum('lessons_count');

        if ($totalLessons == 0) {
            $enrollment->update(['progress_percentage' => 0.00]);
            return;
        }

        $completedLessons = LessonProgress::where('user_id', $enrollment->user_id)
            ->whereHas('lesson.section.course', function ($q) use ($course) {
                $q->where('id', $course->id);
            })
            ->where('is_completed', true)
            ->count();

        $percentage = ($completedLessons / $totalLessons) * 100;

        $enrollment->update(['progress_percentage' => round($percentage, 2)]);

        // إذا اكتمل الكورس 100% → حدث تاريخ الإكمال
        if ($percentage >= 100) {
            $enrollment->update(['completed_at' => now()]);
        }
    }
}