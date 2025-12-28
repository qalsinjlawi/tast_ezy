<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * Enroll the authenticated student in a course (from public course page)
     */
    public function store(Request $request, $slug)
    {
        $course = Course::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $user = Auth::user();

        // التحقق إذا كان الطالب مسجل بالفعل
        if ($course->isEnrolled($user->id)) {
            return redirect()->route('courses.show', $course->slug)
                ->with('info', 'You are already enrolled in this course.');
        }

        // حاليًا: نسمح بالتسجيل فقط إذا كان الكورس مجاني (price = 0)
        // لاحقًا: لو مدفوع → نوجهه لصفحة الدفع
        if ($course->price > 0) {
            return redirect()->route('courses.show', $course->slug)
                ->with('error', 'This course is paid. Payment gateway coming soon.');
        }

        // إنشاء التسجيل
        Enrollment::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'progress_percentage' => 0.00,
            'status'             => 'active',
            'enrolled_at'        => now(),
        ]);

        return redirect()->route('courses.show', $course->slug)
            ->with('success', 'You have successfully enrolled in "' . $course->title . '"!');
    }

    /**
     * Show list of enrolled students for a course (Instructor Dashboard only)
     */
    public function index(Course $course)
    {
        // التأكد من أن المستخدم صاحب الكورس أو أدمن
        if (Auth::user()->role !== 'admin' && $course->instructor_id !== Auth::id()) {
            abort(403, 'You are not authorized to view enrollments for this course.');
        }

        $enrollments = $course->enrollments()
            ->with('user')
            ->orderByDesc('enrolled_at')
            ->paginate(20);

        return view('dashboard.courses.enrollments.index', compact('course', 'enrollments'));
    }

    /**
     * Remove a student from the course (Instructor/Admin only)
     */
    public function destroy(Course $course, Enrollment $enrollment)
    {
        if (Auth::user()->role !== 'admin' && $course->instructor_id !== Auth::id()) {
            abort(403);
        }

        // التأكد إن التسجيل يخص الكورس ده
        if ($enrollment->course_id !== $course->id) {
            abort(404);
        }

        $enrollment->delete();

        return redirect()->route('dashboard.courses.enrollments.index', $course)
            ->with('success', 'Student removed from the course successfully.');
    }
}