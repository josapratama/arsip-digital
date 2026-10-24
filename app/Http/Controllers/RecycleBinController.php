<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RecycleBinController extends Controller
{
    public function index()
    {
        $documents = Document::with(['category', 'uploader'])
            ->inRecycleBin()
            ->latest('updated_at')
            ->paginate(12);

        return Inertia::render('RecycleBin/Index', [
            'documents' => $documents->through(fn($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'category' => $doc->category?->name,
                'uploader' => $doc->uploader?->name,
                'deleted_at' => $doc->updated_at->format('d M Y H:i'),
            ]),
        ]);
    }

    public function restore(Document $document)
    {
        if ($document->status !== 'recycle_bin') {
            abort(400, 'Dokumen tidak berada di recycle bin.');
        }

        if (!auth()->user()->can('restore-documents') && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak untuk memulihkan dokumen ini.');
        }

        $document->update(['status' => 'active']);
        ActivityLog::record('restore', "Memulihkan dokumen: {$document->title}", $document);

        return back()->with('success', __('messages.document_restored'));
    }

    public function forceDelete(Document $document)
    {
        if ($document->status !== 'recycle_bin') {
            abort(400, 'Dokumen harus berada di recycle bin sebelum dihapus permanen.');
        }

        if (!auth()->user()->hasRole('admin') && $document->uploaded_by !== auth()->id()) {
            abort(403, 'Hanya Administrator atau pengunggah asli yang dapat menghapus dokumen secara permanen.');
        }

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        ActivityLog::record('force_delete', "Menghapus permanen: {$document->title}");
        $document->forceDelete();

        return back()->with('success', __('messages.document_permanently_deleted'));
    }
}
