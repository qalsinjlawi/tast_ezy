<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // جلب معلومات إضافية حسب الدور (يمكن توسيعها)
        $stats = [
            'enrolled_courses' => $user->enrollments()->count(),
            'created_courses' => $user->courses()->count(),
            'reviews_given' => $user->reviews()->count(),
            'current_subscription' => $user->subscriptions()->where('status', 'active')->first(),
            'subscription_type' => $user->subscription_type,
            'course_limit' => $user->course_limit,
            'is_active' => $user->is_active,
        ];

        return view('profile.edit', compact('user', 'stats'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'  => ['nullable', 'string', 'max:20'],
            'bio'    => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            // الحقول الجديدة: نسمح بتعديل بعضها، لكن role ممنوع
            'subscription_type' => ['nullable', 'string', 'in:free,basic,pro,premium'], // اختياري: لو عايز تسمح بتغييرها
            'course_limit'      => ['nullable', 'integer', 'min:0'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        // منع تعديل حقل role تمامًا (أمن)
        if ($request->has('role')) {
            unset($validated['role']); // أو throw exception لو عايز
        }

        // رفع الصورة الجديدة
        if ($request->hasFile('avatar')) {
            // حذف القديمة
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill($validated);

        // إعادة تعيين التحقق من الإيميل لو تغير
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // حذف الصورة
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('success', 'Your account has been deleted successfully.');
    }
}