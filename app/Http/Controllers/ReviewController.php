<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Store a new review for a course (from public course page)
     */
    public function store(Request $request, $slug)
    {
        $course = Course::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $user = Auth::user();

        // التحقق إن الطالب مسجل في الكورس
        if (!$course->isEnrolled($user->id)) {
            return redirect()->route('courses.show', $course->slug)
                ->with('error', 'You must be enrolled in the course to leave a review.');
        }

        // التحقق إن الطالب ما كتبش تقييم قبل كده
        if ($course->reviews()->where('user_id', $user->id)->exists()) {
            return redirect()->route('courses.show', $course->slug)
                ->with('info', 'You have already reviewed this course.');
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'rating'      => $validated['rating'],
            'comment'     => $validated['comment'],
            'is_approved' => true, // يظهر فورًا (يمكن تغييره لـ false ويحتاج موافقة)
        ]);

        return redirect()->route('courses.show', $course->slug)
            ->with('success', 'Thank you! Your review has been submitted successfully.');
    }

    /**
     * Update the authenticated user's review for a course
     */
    public function update(Request $request, Course $course, Review $review)
    {
        // التأكد إن التقييم ملك الطالب الحالي
        if ($review->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to edit this review.');
        }

        // التأكد إن التقييم للكورس ده
        if ($review->course_id !== $course->id) {
            abort(404);
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return redirect()->route('courses.show', $course->slug)
            ->with('success', 'Your review has been updated successfully.');
    }

    /**
     * Delete a review (by the owner, instructor, or admin)
     */
    public function destroy(Course $course, Review $review)
    {
        // الطالب يحذف تقييمه الخاص
        // المعلم يحذف أي تقييم في كورساته
        // الأدمن يحذف أي تقييم
        $user = Auth::user();

        if ($review->user_id === $user->id ||
            ($user->role === 'instructor' && $course->instructor_id === $user->id) ||
            $user->role === 'admin') {
            
            $review->delete();

            return redirect()->route('courses.show', $course->slug)
                ->with('success', 'Review deleted successfully.');
        }

        abort(403, 'You are not authorized to delete this review.');
    }

    /**
     * (Optional) Admin/Instructor approve or reject reviews - if you want moderation
     */
    public function toggleApproval(Course $course, Review $review)
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && 
            !($user->role === 'instructor' && $course->instructor_id === $user->id)) {
            abort(403);
        }

        $review->update(['is_approved' => !$review->is_approved]);

        $status = $review->is_approved ? 'approved' : 'hidden';
        return redirect()->back()->with('success', "Review has been {$status}.");
    }
}