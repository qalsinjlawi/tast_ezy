<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionPaymentController extends Controller
{
    /**
     * Display a listing of subscription payments.
     * - Student: sees only his own payments
     * - Admin: sees all payments with stats
     */
    public function index()
    {
        $user = Auth::user();

        $query = SubscriptionPayment::with(['user', 'subscription']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $payments = $query->orderByDesc('created_at')->paginate(15);

        // إحصائيات للأدمن فقط
        $stats = null;
        if ($user->role === 'admin') {
            $stats = [
                'total_payments' => SubscriptionPayment::count(),
                'total_revenue'  => SubscriptionPayment::where('status', 'paid')->sum('amount'),
                'pending'        => SubscriptionPayment::where('status', 'pending')->count(),
                'failed'         => SubscriptionPayment::where('status', 'failed')->count(),
                'this_month'     => SubscriptionPayment::where('status', 'paid')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount'),
            ];
        }

        return view('subscriptions.payments.index', compact('payments', 'stats'));
    }

    /**
     * Display the specified payment details
     */
    public function show(SubscriptionPayment $subscriptionPayment)
    {
        $user = Auth::user();

        // الطالب يشوف دفعاته هو بس
        if ($user->role !== 'admin' && $subscriptionPayment->user_id !== $user->id) {
            abort(403, 'Unauthorized access to payment details.');
        }

        $subscriptionPayment->load(['user', 'subscription']);

        return view('subscriptions.payments.show', compact('subscriptionPayment'));
    }

    /**
     * Admin manually marks a payment as paid (for offline or manual payments)
     */
    public function markAsPaid(Request $request, SubscriptionPayment $subscriptionPayment)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'transaction_id' => 'required|string|max:255',
            'paid_at'        => 'required|date',
        ]);

        $subscriptionPayment->update([
            'transaction_id' => $validated['transaction_id'],
            'status'        => 'paid',
            'paid_at'        => $validated['paid_at'],
        ]);

        // تحديث حالة الاشتراك تلقائيًا
        $subscription = $subscriptionPayment->subscription;
        if ($subscription && $subscription->status !== 'active') {
            $newExpiresAt = $subscription->expires_at && $subscription->expires_at > now()
                ? $subscription->expires_at->addMonths($subscription->billing_period === 'monthly' ? 1 : 12)
                : now()->addMonths($subscription->billing_period === 'monthly' ? 1 : 12);

            $subscription->update([
                'status' => 'active',
                'expires_at' => $newExpiresAt,
            ]);
        }

        return redirect()->route('subscription.payments.index')
            ->with('success', 'Payment marked as paid and subscription activated.');
    }

    /**
     * Admin updates payment status (e.g., refund, cancel)
     */
    public function updateStatus(Request $request, SubscriptionPayment $subscriptionPayment)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,paid,failed,refunded,cancelled',
        ]);

        $oldStatus = $subscriptionPayment->status;

        $subscriptionPayment->update(['status' => $validated['status']]);

        // إذا تم استرداد الدفعة، ألغِ أو أعد حالة الاشتراك
        if ($validated['status'] === 'refunded' && $oldStatus === 'paid') {
            $subscription = $subscriptionPayment->subscription;
            if ($subscription) {
                $subscription->update(['status' => 'cancelled']);
            }
        }

        return redirect()->back()->with('success', 'Payment status updated to ' . $validated['status'] . '.');
    }
}