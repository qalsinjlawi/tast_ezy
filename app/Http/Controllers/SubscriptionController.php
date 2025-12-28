<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Display all available subscription plans (public page)
     */
    public function plans()
    {
        // يمكنك إنشاء جدول منفصل للخطط (plans)، لكن حاليًا هنعرضها يدويًا أو من DB
        $plans = [
            [
                'plan_name' => 'basic',
                'price_monthly' => 9.99,
                'price_yearly' => 99.99,
                'features' => ['3 courses limit', 'Basic support', 'No downloads'],
            ],
            [
                'plan_name' => 'pro',
                'price_monthly' => 19.99,
                'price_yearly' => 199.99,
                'features' => ['Unlimited courses', 'Priority support', 'Download lessons', 'No ads'],
            ],
            [
                'plan_name' => 'premium',
                'price_monthly' => 29.99,
                'price_yearly' => 299.99,
                'features' => ['Everything in Pro', '1-on-1 mentoring', 'Certificates', 'Early access'],
            ],
        ];

        return view('subscriptions.plans', compact('plans'));
    }

    /**
     * Show the authenticated user's current subscription
     */
    public function mySubscription()
    {
        $subscription = Auth::user()->subscriptions()
            ->with('payments')
            ->latest('starts_at')
            ->first();

        return view('subscriptions.my-subscription', compact('subscription'));
    }

    /**
     * Display a listing of all subscriptions (Admin only)
     */
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $subscriptions = Subscription::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Cancel the current subscription (Student)
     */
    public function cancel()
    {
        $subscription = Auth::user()->subscriptions()
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return redirect()->route('subscriptions.my')
                ->with('info', 'You do not have an active subscription to cancel.');
        }

        $subscription->update([
            'status' => 'cancelled',
            'expires_at' => now(), // أو اتركه لينتهي طبيعي
        ]);

        return redirect()->route('subscriptions.my')
            ->with('success', 'Your subscription has been cancelled successfully.');
    }

    /**
     * Admin can manually activate/renew a subscription
     */
    public function activate(Request $request, Subscription $subscription)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'months' => 'required|integer|min:1|max:36',
        ]);

        $newExpiresAt = $subscription->expires_at && $subscription->expires_at > now()
            ? $subscription->expires_at->addMonths($validated['months'])
            : now()->addMonths($validated['months']);

        $subscription->update([
            'status' => 'active',
            'starts_at' => $subscription->starts_at ?? now(),
            'expires_at' => $newExpiresAt,
        ]);

        return redirect()->back()->with('success', 'Subscription activated/renewed successfully.');
    }

    /**
     * Admin can change subscription plan
     */
    public function changePlan(Request $request, Subscription $subscription)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'plan_name' => 'required|in:basic,pro,premium',
            'billing_period' => 'required|in:monthly,yearly',
            'price' => 'required|numeric|min:0',
        ]);

        $subscription->update($validated);

        return redirect()->back()->with('success', 'Subscription plan updated.');
    }

    /**
     * Check and update expired subscriptions (can be called by scheduler)
     */
    public static function updateExpiredSubscriptions()
    {
        Subscription::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}