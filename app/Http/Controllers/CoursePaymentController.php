<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoursePaymentController extends Controller
{
    /**
     * Display a listing of course payments.
     * - Admin/Instructor: sees payments for their courses or all
     * - Student: sees only his own payments
     */
    public function index()
    {
        $user = Auth::user();
        $query = CoursePayment::with(['user', 'course.instructor']);

        if ($user->role === 'student') {
            // الطالب يشوف دفعاته هو بس
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'instructor') {
            // المعلم يشوف دفعات كورساته هو بس
            $query->whereHas('course', function ($q) use ($user) {
                $q->where('instructor_id', $user->id);
            });
        }
        // Admin يشوف الكل

        $payments = $query->orderByDesc('paid_at')->paginate(15);

        // إحصائيات (للأدمن أو المعلم)
        $stats = $user->role !== 'student' ? $this->getPaymentStats() : null;

        return view('course-payments.index', compact('payments', 'stats'));
    }

    /**
     * Display course payments for a specific course (Instructor Dashboard)
     */
    public function coursePayments(Course $course)
    {
        $this->authorizeCourseOwnership($course);

        $payments = $course->coursePayments()
            ->with('user')
            ->orderByDesc('paid_at')
            ->paginate(20);

        $totalRevenue = $payments->sum('amount');

        return view('dashboard.courses.payments.index', compact('course', 'payments', 'totalRevenue'));
    }

    /**
     * Show specific payment details
     */
    public function show(CoursePayment $coursePayment)
    {
        // Authorization
        if (Auth::user()->role === 'student' && $coursePayment->user_id !== Auth::id()) {
            abort(403);
        }

        $coursePayment->load(['user', 'course.instructor']);

        return view('course-payments.show', compact('coursePayment'));
    }

    /**
     * Admin marks payment as paid (manual/offline payments)
     */
    public function markAsPaid(Request $request, CoursePayment $coursePayment)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'transaction_id' => 'required|string|max:255|unique:course_payments,transaction_id,' . $coursePayment->id,
            'paid_at'        => 'required|date',
            'amount'         => 'required|numeric|min:0',
        ]);

        $coursePayment->update([
            'transaction_id' => $validated['transaction_id'],
            'amount'         => $validated['amount'],
            'status'         => 'paid',
            'paid_at'        => $validated['paid_at'],
        ]);

        // إنشاء enrollment تلقائي بعد الدفع الناجح
        if (!$coursePayment->course->isEnrolled($coursePayment->user_id)) {
            $coursePayment->course->enrollments()->create([
                'user_id' => $coursePayment->user_id,
                'progress_percentage' => 0.00,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);
        }

        return redirect()->route('course.payments.index')
            ->with('success', 'Payment marked as paid successfully. Student enrolled automatically.');
    }

    /**
     * Update payment status (refund, cancel, etc.)
     */
    public function updateStatus(Request $request, CoursePayment $coursePayment)
    {
        if (!in_array(Auth::user()->role, ['admin', 'instructor'])) {
            abort(403);
        }

        // التأكد إن المعلم صاحب الكورس
        if (Auth::user()->role === 'instructor' && $coursePayment->course->instructor_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,paid,failed,refunded,cancelled',
        ]);

        $oldStatus = $coursePayment->status;
        $coursePayment->update(['status' => $request->status]);

        // إذا تم الاسترداد، ألغِ التسجيل
        if ($request->status === 'refunded' && $oldStatus === 'paid') {
            $coursePayment->course->enrollments()
                ->where('user_id', $coursePayment->user_id)
                ->update(['status' => 'cancelled']);
        }

        return redirect()->back()
            ->with('success', 'Payment status updated to ' . $request->status . '.');
    }

    /**
     * Get payment statistics (for dashboard)
     */
    private function getPaymentStats()
    {
        return [
            'total_payments' => CoursePayment::count(),
            'total_revenue'  => CoursePayment::where('status', 'paid')->sum('amount'),
            'pending_count'  => CoursePayment::where('status', 'pending')->count(),
            'this_month'     => CoursePayment::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
        ];
    }

    /**
     * Authorize course ownership for instructors
     */
    private function authorizeCourseOwnership(Course $course)
    {
        if (Auth::user()->role !== 'admin' && $course->instructor_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage payments for this course.');
        }
    }
}