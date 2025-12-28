<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,instructor'], // التحقق: فقط طالب أو معلم (لا admin)
            'phone' => ['nullable', 'string', 'max:20'],      // اختياري: إضافة رقم الهاتف
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // اختياري: رفع صورة
        ]);

        // إنشاء المستخدم مع الحقول الجديدة
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            // إذا كان هناك رفع صورة (اختياري)
            'avatar' => $request->hasFile('avatar') 
                ? $request->file('avatar')->store('avatars', 'public') 
                : null,
        ]);

        // إطلاق حدث التسجيل (Breeze يستخدمه لإرسال الإيميل التأكيد)
        event(new Registered($user));

        // تسجيل الدخول التلقائي بعد التسجيل
        Auth::login($user);

        // التوجيه إلى الداشبورد (أو صفحة ترحيب حسب الدور)
        return redirect(route('dashboard', absolute: false));
    }
}