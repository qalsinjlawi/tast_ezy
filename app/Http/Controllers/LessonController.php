<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    /**
     * Display a listing of lessons for a specific section
     */
    public function index(Course $course, CourseSection $section)
    {
        $this->authorizeCourseOwnership($course);

        if ($section->course_id !== $course->id) {
            abort(404);
        }

        $lessons = $section->lessons()->orderBy('order')->get();

        return view('dashboard.courses.sections.lessons.index', compact('course', 'section', 'lessons'));
    }

    /**
     * Show the form for creating a new lesson
     */
    public function create(Course $course, CourseSection $section)
    {
        $this->authorizeCourseOwnership($course);

        if ($section->course_id !== $course->id) {
            abort(404);
        }

        $nextOrder = $section->lessons()->max('order') + 1 ?? 0;

        return view('dashboard.courses.sections.lessons.create', compact('course', 'section', 'nextOrder'));
    }

    /**
     * Store a newly created lesson
     */
    public function store(Request $request, Course $course, CourseSection $section)
    {
        $this->authorizeCourseOwnership($course);

        if ($section->course_id !== $course->id) {
            abort(404);
        }

        $rules = [
            'title'       => 'required|string|max:255',
            'type'        => 'required|in:video,article,quiz,download',
            'description' => 'nullable|string|max:1000',
            'duration'    => 'nullable|integer|min:0',
            'is_free'     => 'sometimes|boolean',
            'order'       => 'required|integer|min:0',
        ];

        // قواعد إضافية حسب نوع الدرس
        if ($request->type === 'video') {
            $rules['video_url'] = 'required|url';
        } elseif ($request->type === 'article') {
            $rules['content'] = 'required|string';
        } elseif ($request->type === 'download') {
            $rules['attachment_url'] = 'required|file|mimes:pdf,zip,doc,docx,ppt,pptx|max:20480'; // 20MB max
        } elseif ($request->type === 'quiz') {
            $rules['content'] = 'required|string'; // يمكن تطويرها لاحقًا لتخزين JSON للأسئلة
        }

        $validated = $request->validate($rules);

        $data = $validated;
        $data['is_free'] = $request->has('is_free');

        // رفع الملف المرفق إذا كان download
        if ($request->hasFile('attachment_url') && $request->type === 'download') {
            $data['attachment_url'] = $request->file('attachment_url')->store('lessons/attachments', 'public');
        }

        $section->lessons()->create($data);

        return redirect()->route('dashboard.courses.sections.lessons.index', [$course, $section])
            ->with('success', 'Lesson created successfully!');
    }

    /**
     * Show the form for editing a lesson
     */
    public function edit(Course $course, CourseSection $section, Lesson $lesson)
    {
        if ($lesson->section_id !== $section->id || $section->course_id !== $course->id) {
            abort(404);
        }

        $this->authorizeCourseOwnership($course);

        return view('dashboard.courses.sections.lessons.edit', compact('course', 'section', 'lesson'));
    }

    /**
     * Update the lesson
     */
    public function update(Request $request, Course $course, CourseSection $section, Lesson $lesson)
    {
        if ($lesson->section_id !== $section->id || $section->course_id !== $course->id) {
            abort(404);
        }

        $this->authorizeCourseOwnership($course);

        $rules = [
            'title'       => 'required|string|max:255',
            'type'        => 'required|in:video,article,quiz,download',
            'description' => 'nullable|string|max:1000',
            'duration'    => 'nullable|integer|min:0',
            'is_free'     => 'sometimes|boolean',
            'order'       => 'required|integer|min:0',
        ];

        if ($request->type === 'video') {
            $rules['video_url'] = 'required|url';
        } elseif ($request->type === 'article') {
            $rules['content'] = 'required|string';
        } elseif ($request->type === 'download') {
            $rules['attachment_url'] = 'nullable|file|mimes:pdf,zip,doc,docx,ppt,pptx|max:20480';
        } elseif ($request->type === 'quiz') {
            $rules['content'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $data = $validated;
        $data['is_free'] = $request->has('is_free');

        // تحديث الملف المرفق إذا تم رفع جديد
        if ($request->hasFile('attachment_url') && $request->type === 'download') {
            // حذف القديم
            if ($lesson->attachment_url) {
                Storage::disk('public')->delete($lesson->attachment_url);
            }
            $data['attachment_url'] = $request->file('attachment_url')->store('lessons/attachments', 'public');
        }

        // إفراغ الحقول غير المتعلقة بالنوع الجديد
        if ($request->type !== 'video') $data['video_url'] = null;
        if ($request->type !== 'article' && $request->type !== 'quiz') $data['content'] = null;
        if ($request->type !== 'download') {
            if ($lesson->attachment_url) {
                Storage::disk('public')->delete($lesson->attachment_url);
            }
            $data['attachment_url'] = null;
        }

        $lesson->update($data);

        return redirect()->route('dashboard.courses.sections.lessons.index', [$course, $section])
            ->with('success', 'Lesson updated successfully!');
    }

    /**
     * Delete the lesson
     */
    public function destroy(Course $course, CourseSection $section, Lesson $lesson)
    {
        if ($lesson->section_id !== $section->id || $section->course_id !== $course->id) {
            abort(404);
        }

        $this->authorizeCourseOwnership($course);

        // حذف الملف المرفق إذا موجود
        if ($lesson->attachment_url) {
            Storage::disk('public')->delete($lesson->attachment_url);
        }

        $lesson->delete();

        return redirect()->route('dashboard.courses.sections.lessons.index', [$course, $section])
            ->with('success', 'Lesson deleted successfully!');
    }

    // ==================================================================
    // Helper Method
    // ==================================================================

    private function authorizeCourseOwnership(Course $course)
    {
        if (Auth::user()->role !== 'admin' && $course->instructor_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this course content.');
        }
    }
}