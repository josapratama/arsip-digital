<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::root()
            ->with(['children' => fn($q) => $q->orderBy('sort_order')->withCount('documents')])
            ->withCount('documents')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Categories', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:500',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $maxSort = Category::where('parent_id', $request->parent_id)->max('sort_order') ?? 0;

        $category = Category::create([
            'name' => $request->name,
            'name_en' => $request->name_en,
            'slug' => $slug,
            'parent_id' => $request->parent_id,
            'icon' => $request->icon ?? 'folder',
            'color' => $request->color ?? 'emerald',
            'description' => $request->description,
            'sort_order' => $maxSort + 1,
            'is_active' => true,
        ]);

        ActivityLog::record('settings', "Menambahkan kategori arsip: {$category->name}", $category);

        return back()->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $request->name,
            'name_en' => $request->name_en,
            'icon' => $request->icon ?? $category->icon,
            'color' => $request->color ?? $category->color,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::record('settings', "Memperbarui kategori arsip: {$category->name}", $category);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $totalDocs = $category->documents()->count();
        if ($totalDocs > 0) {
            return back()->with('error', "Kategori tidak dapat dihapus karena masih memiliki {$totalDocs} dokumen terkait.");
        }

        $childDocs = $category->children()->withCount('documents')->get()->sum('documents_count');
        if ($childDocs > 0) {
            return back()->with('error', "Kategori tidak dapat dihapus karena subkategorinya masih memiliki {$childDocs} dokumen terkait.");
        }

        $name = $category->name;
        $category->children()->delete();
        $category->delete();

        ActivityLog::record('settings', "Menghapus kategori arsip: {$name}");

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
