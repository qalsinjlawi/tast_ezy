<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    // ==================================================================
    // 1. Public Pages - متاحة للزوار والطلاب
    // ==================================================================

    // قائمة الكورسات العامة (المنشورة فقط)
    public function publicIndex()
    {
        $courses = Course::published()
            ->with(['instructor', 'category'])
            ->withCount(['enrollments as students_count', 'reviews as reviews_count'])
            ->withAvg('reviews', 'rating as average_rating')
            ->latest()
            ->paginate(12);

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('courses.index', compact('courses', 'categories'));
    }

    // تفاصيل كورس واحد (عامة)
    public function publicShow($slug)
    {
        $course = Course::published()
            ->with(['instructor', 'category', 'sections.lessons'])
            ->withCount(['enrollments as students_count', 'reviews as reviews_count'])
            ->withAvg('reviews', 'rating as average_rating')
            ->where('slug', $slug)
            ->firstOrFail();

        $isEnrolled = $course->isEnrolled(); // هل الطالب مسجل حاليًا؟

        $relatedCourses = Course::published()
            ->where('category_id', $course->category_id)
            ->where('id', '!=', $course->id)
            ->inRandomOrder()
            ->limit(6)
            ->get();

        return view('courses.show', compact('course', 'isEnrolled', 'relatedCourses'));
    }

    // ==================================================================
    // 2. Student Page - كورسات الطالب
    // ==================================================================

    public function myCourses()
    {
        $enrollments = Auth::user()->enrollments()
            ->with(['course' => function ($query) {
                $query->with(['category', 'instructor'])
                      ->withCount('sections');
            }])
            ->paginate(12);

        return view('student.my-courses', compact('enrollments'));
    }

    // ==================================================================
    // 3. Dashboard Pages - للمعلم والأدمن فقط
    // ==================================================================

    // قائمة الكورسات داخل الداشبورد
    public function index()
    {
        $query = Course::with(['instructor', 'category']);

        if (Auth::user()->role === 'instructor') {
            $query->where('instructor_id', Auth::id());
        }
        // الأدمن يشوف الكل

        $courses = $query->latest()->paginate(12);

        return view('dashboard.courses.index', compact('courses'));
    }

    // نموذج إنشاء كورس جديد
    public function create()
    {
        $this->authorizeInstructorOrAdmin();

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.courses.create', compact('categories'));
    }

    // حفظ كورس جديد
    public function store(Request $request)
    {
        $this->authorizeInstructorOrAdmin();

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'description'       => 'required|string',
            'category_id'       => 'required|exists:categories,id',
            'price'             => 'required|numeric|min:0',
            'level'             => 'required|in:Beginner,Intermediate,Advanced',
            'thumbnail'         => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_published'      => 'sometimes|boolean',
        ]);

        $thumbnailPath = $request->file('thumbnail')->store('courses/thumbnails', 'public');

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $count = 1;
        while (Course::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        Course::create([
            'title'             => $validated['title'],
            'slug'              => $slug,
            'short_description' => $validated['short_description'],
            'description'       => $validated['description'],
            'category_id'       => $validated['category_id'],
            'instructor_id'     => Auth::id(), // دائمًا المستخدم الحالي
            'price'             => $validated['price'],
            'level'             => $validated['level'],
            'thumbnail'         => $thumbnailPath,
            'is_published'      => $request->has('is_published'),
        ]);

        return redirect()->route('dashboard.courses.index')
            ->with('success', 'Course created successfully!');
    }

    // نموذج تعديل كورس
    public function edit(Course $course)
    {
        $this->authorizeCourseOwnership($course);

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.courses.edit', compact('course', 'categories'));
    }

    // تحديث كورس
    public function update(Request $request, Course $course)
    {
        $this->authorizeCourseOwnership($course);

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'description'       => 'required|string',
            'category_id'       => 'required|exists:categories,id',
            'price'             => 'required|numeric|min:0',
            'level'             => 'required|in:Beginner,Intermediate,Advanced',
            'thumbnail'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_published'      => 'sometimes|boolean',
        ]);

        $data = $validated;
        $data['is_published'] = $request->has('is_published');

        // تحديث الـ slug إذا تغير العنوان
        if ($request->title !== $course->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $count = 1;
            while (Course::where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $data['slug'] = $slug;
        }

        // تحديث الصورة إذا تم رفع جديدة
        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }

        $course->update($data);

        return redirect()->route('dashboard.courses.index')
            ->with('success', 'Course updated successfully!');
    }

    // حذف كورس
    public function destroy(Course $course)
    {
        $this->authorizeCourseOwnership($course);

        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();

        return redirect()->route('dashboard.courses.index')
            ->with('success', 'Course deleted successfully!');
    }

    // ==================================================================
    // Helper Methods for Authorization
    // ==================================================================

    private function authorizeInstructorOrAdmin()
    {
        if (!in_array(Auth::user()->role, ['instructor', 'admin'])) {
            abort(403, 'You are not authorized to perform this action.');
        }
    }

    private function authorizeCourseOwnership(Course $course)
    {
        if (Auth::user()->role !== 'admin' && $course->instructor_id !== Auth::id()) {
            abort(403, 'You are not authorized to modify this course.');
        }
    }
}