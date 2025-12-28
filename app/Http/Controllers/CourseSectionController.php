<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseSectionController extends Controller
{
    /**
     * Display a listing of sections for a specific course (in dashboard)
     */
    public function index(Course $course)
    {
        $this->authorizeCourseOwnership($course);

        $sections = $course->sections()->orderBy('order')->get();

        return view('dashboard.courses.sections.index', compact('course', 'sections'));
    }

    /**
     * Show the form for creating a new section
     */
    public function create(Course $course)
    {
        $this->authorizeCourseOwnership($course);

        // احسب الترتيب التلقائي (آخر ترتيب + 1)
        $nextOrder = $course->sections()->max('order') + 1 ?? 0;

        return view('dashboard.courses.sections.create', compact('course', 'nextOrder'));
    }

    /**
     * Store a newly created section in storage
     */
    public function store(Request $request, Course $course)
    {
        $this->authorizeCourseOwnership($course);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'order'       => 'required|integer|min:0',
        ]);

        $course->sections()->create($validated);

        return redirect()->route('dashboard.courses.sections.index', $course)
            ->with('success', 'Section created successfully!');
    }

    /**
     * Show the form for editing the specified section
     */
    public function edit(Course $course, CourseSection $section)
    {
        // التأكد إن القسم ينتمي للكورس
        if ($section->course_id !== $course->id) {
            abort(404);
        }

        $this->authorizeCourseOwnership($course);

        return view('dashboard.courses.sections.edit', compact('course', 'section'));
    }

    /**
     * Update the specified section in storage
     */
    public function update(Request $request, Course $course, CourseSection $section)
    {
        if ($section->course_id !== $course->id) {
            abort(404);
        }

        $this->authorizeCourseOwnership($course);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'order'       => 'required|integer|min:0',
        ]);

        $section->update($validated);

        return redirect()->route('dashboard.courses.sections.index', $course)
            ->with('success', 'Section updated successfully!');
    }

    /**
     * Remove the specified section from storage
     */
    public function destroy(Course $course, CourseSection $section)
    {
        if ($section->course_id !== $course->id) {
            abort(404);
        }

        $this->authorizeCourseOwnership($course);

        $section->delete();

        return redirect()->route('dashboard.courses.sections.index', $course)
            ->with('success', 'Section deleted successfully!');
    }

    // ==================================================================
    // Helper Method for Authorization
    // ==================================================================

    private function authorizeCourseOwnership(Course $course)
    {
        if (Auth::user()->role !== 'admin' && $course->instructor_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage sections for this course.');
        }
    }
}