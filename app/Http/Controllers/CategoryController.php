<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories (Admin Dashboard).
     */
    public function index()
    {
        $categories = Category::orderBy('order')->paginate(15);

        return view('dashboard.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('dashboard.categories.create');
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'icon'        => 'nullable|image|mimes:jpeg,png,svg,webp|max:1024',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order'       => 'required|integer|min:0',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data = $validated;
        $data['is_active'] = $request->has('is_active');
        $data['slug'] = Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $count = 1;
        while (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count++;
        }

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories/icons', 'public');
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories/images', 'public');
        }

        Category::create($data);

        return redirect()->route('dashboard.categories.index')
            ->with('success', 'Category created successfully!');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        return view('dashboard.categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
            'icon'        => 'nullable|image|mimes:jpeg,png,svg,webp|max:1024',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order'       => 'required|integer|min:0',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data = $validated;
        $data['is_active'] = $request->has('is_active');

        // Update slug if name changed
        if ($request->name !== $category->name) {
            $data['slug'] = Str::slug($validated['name']);
            $originalSlug = $data['slug'];
            $count = 1;
            while (Category::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $count++;
            }
        }

        // Handle new icon
        if ($request->hasFile('icon')) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $request->file('icon')->store('categories/icons', 'public');
        }

        // Handle new image
        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories/images', 'public');
        }

        $category->update($data);

        return redirect()->route('dashboard.categories.index')
            ->with('success', 'Category updated successfully!');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        // Delete associated files
        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('dashboard.categories.index')
            ->with('success', 'Category deleted successfully!');
    }
}