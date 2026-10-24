<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Category;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_documents' => Document::active()->count(),
            'total_categories' => Category::root()->active()->count(),
            'documents_this_month' => Document::active()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'recycle_bin_count' => Document::inRecycleBin()->count(),
        ];

        $recentDocuments = Document::with(['category', 'subcategory', 'uploader'])
            ->active()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'document_number' => $doc->document_number,
                'nomor_surat' => $doc->document_number,
                'category' => $doc->category?->name,
                'subcategory' => $doc->subcategory?->name,
                'uploader' => $doc->uploader?->name,
                'created_at' => $doc->created_at->diffForHumans(),
                'file_type' => $doc->file_type,
                'is_image' => $doc->is_image,
            ]);

        $categoryStats = Category::root()
            ->active()
            ->withCount(['documents' => fn($q) => $q->whereIn('status', ['active', 'aktif'])])
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'name_en' => $cat->name_en,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'documents_count' => $cat->documents_count,
            ]);

        $recentActivity = ActivityLog::with('user')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name ?? 'System',
                'created_at' => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentDocuments' => $recentDocuments,
            'categoryStats' => $categoryStats,
            'recentActivity' => $recentActivity,
        ]);
    }
}
