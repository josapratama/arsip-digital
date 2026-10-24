<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Services\OcrService;
use App\Services\KlasifikasiKemendagriService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private OcrService $ocrService,
        private KlasifikasiKemendagriService $kemendagriService
    ) {}

    public function index(Request $request): Response
    {
        $query = Document::with(['category', 'subcategory', 'uploader'])
            ->active();

        // Full-text search
        if ($request->filled('q')) {
            $query->search($request->q);
        }

        // Filters
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('document_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('document_date', '<=', $request->date_to);
        }

        if (!$request->filled('q')) {
            $query->latest();
        }

        $documents = $query->paginate(12)->withQueryString();

        return Inertia::render('Documents/Index', [
            'documents' => $documents->through(fn($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'document_number' => $doc->document_number,
                'nomor_surat' => $doc->document_number,
                'category' => ['id' => $doc->category_id, 'name' => $doc->category?->name, 'color' => $doc->category?->color],
                'subcategory' => $doc->subcategory ? ['id' => $doc->subcategory_id, 'name' => $doc->subcategory?->name] : null,
                'file_type' => $doc->file_type,
                'file_size_formatted' => $doc->file_size_formatted,
                'is_image' => $doc->is_image,
                'is_pdf' => $doc->is_pdf,
                'is_word' => $doc->is_word,
                'is_text' => $doc->is_text,
                'file_extension' => $doc->file_extension,
                'classification_code' => $doc->classification_code,
                'kode_klasifikasi' => $doc->classification_code,
                'verification_code' => $doc->verification_code,
                'document_date' => $doc->document_date?->format('d M Y'),
                'uploader' => $doc->uploader?->name,
                'ocr_processed' => $doc->ocr_processed,
                'created_at' => $doc->created_at->format('d M Y'),
            ]),
            'filters' => $request->only(['q', 'category', 'subcategory', 'date_from', 'date_to']),
            'categories' => Category::root()->active()->with('children:id,name,parent_id')->get(['id', 'name', 'name_en', 'color', 'icon']),
            'can_create' => auth()->user()->can('create', Document::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Document::class);

        return Inertia::render('Documents/Create', [
            'categories' => Category::root()->active()->with('children:id,name,name_en,parent_id')->get(['id', 'name', 'name_en', 'color', 'icon']),
            'kemendagri_list' => $this->kemendagriService->getAll(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

        $request->validate([
            'title' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'nomor_surat' => 'nullable|string|max:100',
            'classification_code' => 'nullable|string|max:30',
            'kode_klasifikasi' => 'nullable|string|max:30',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'document_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,docx,doc,txt,csv,rtf|max:20480',
            'ocr_text' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . date('Y/m'), 'local');

        $ocrText = $request->ocr_text;
        $fullStoredPath = Storage::disk('local')->path($path);

        // Auto-extract text natively if OCR text was not sent from preview
        if (empty($ocrText) && file_exists($fullStoredPath)) {
            $autoExtract = $this->ocrService->extractText($fullStoredPath, $file->getClientOriginalName());
            if (!empty($autoExtract['success']) && !empty($autoExtract['text'])) {
                $ocrText = $autoExtract['text'];
            }
        }

        $docNumber = $request->document_number ?? $request->nomor_surat;
        $classCode = $request->classification_code ?? $request->kode_klasifikasi;

        $document = Document::create([
            'title' => $request->title,
            'document_number' => $docNumber,
            'nomor_surat' => $docNumber,
            'classification_code' => $classCode,
            'kode_klasifikasi' => $classCode,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'document_date' => $request->document_date,
            'description' => $request->description,
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'ocr_text' => $ocrText,
            'ocr_processed' => !empty($ocrText),
            'ocr_processed_at' => !empty($ocrText) ? now() : null,
            'uploaded_by' => auth()->id(),
            'status' => 'active',
        ]);

        ActivityLog::record('upload', "Mengunggah dokumen: {$document->title}", $document);

        return redirect()->route('documents.show', $document)->with('success', __('messages.document_uploaded'));
    }

    public function show(Document $document): Response
    {
        $document->load(['category', 'subcategory', 'uploader', 'updater']);

        ActivityLog::record('view', "Melihat dokumen: {$document->title}", $document);

        return Inertia::render('Documents/Show', [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'document_number' => $document->document_number,
                'nomor_surat' => $document->document_number,
                'classification_code' => $document->classification_code,
                'kode_klasifikasi' => $document->classification_code,
                'verification_code' => $document->verification_code,
                'verification_url' => route('documents.verify', $document->verification_code ?? 'none'),
                'category' => $document->category,
                'subcategory' => $document->subcategory,
                'file_type' => $document->file_type,
                'file_original_name' => $document->file_original_name,
                'file_size_formatted' => $document->file_size_formatted,
                'file_url' => route('documents.file', $document),
                'is_image' => $document->is_image,
                'is_pdf' => $document->is_pdf,
                'is_word' => $document->is_word,
                'is_text' => $document->is_text,
                'file_extension' => $document->file_extension,
                'can_extract_text' => $document->can_extract_text,
                'text_preview' => $document->is_text && Storage::disk('local')->exists($document->file_path)
                    ? mb_substr(Storage::disk('local')->get($document->file_path), 0, 10000)
                    : null,
                'ocr_text' => $document->ocr_text,
                'ocr_processed' => $document->ocr_processed,
                'description' => $document->description,
                'document_date' => $document->document_date?->format('d M Y'),
                'uploader' => $document->uploader?->name,
                'updater' => $document->updater?->name,
                'created_at' => $document->created_at->format('d M Y H:i'),
                'updated_at' => $document->updated_at->format('d M Y H:i'),
            ],
            'enable_document_qr' => (bool) SiteSetting::get('enable_document_qr', true),
            'desa' => [
                'nama' => SiteSetting::get('desa_name', 'Desa Sukarami'),
                'kecamatan' => SiteSetting::get('kecamatan', 'Rambang'),
                'kabupaten' => SiteSetting::get('kabupaten', 'Muara Enim'),
            ],
            'can' => [
                'update' => auth()->user()->can('update', $document),
                'delete' => auth()->user()->can('delete', $document),
            ],
        ]);
    }

    public function edit(Document $document): Response
    {
        $this->authorize('update', $document);

        return Inertia::render('Documents/Edit', [
            'document' => $document->load(['category', 'subcategory']),
            'categories' => Category::root()->active()->with('children:id,name,name_en,parent_id')->get(['id', 'name', 'name_en', 'color', 'icon']),
            'kemendagri_list' => $this->kemendagriService->getAll(),
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $request->validate([
            'title' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'nomor_surat' => 'nullable|string|max:100',
            'classification_code' => 'nullable|string|max:30',
            'kode_klasifikasi' => 'nullable|string|max:30',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'document_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'ocr_text' => 'nullable|string',
        ]);

        $docNumber = $request->has('document_number') ? $request->document_number : ($request->has('nomor_surat') ? $request->nomor_surat : $document->document_number);
        $classCode = $request->has('classification_code') ? $request->classification_code : ($request->has('kode_klasifikasi') ? $request->kode_klasifikasi : $document->classification_code);

        $document->update([
            'title' => $request->title,
            'document_number' => $docNumber,
            'nomor_surat' => $docNumber,
            'classification_code' => $classCode,
            'kode_klasifikasi' => $classCode,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'document_date' => $request->document_date,
            'description' => $request->description,
            'ocr_text' => $request->ocr_text,
            'updated_by' => auth()->id(),
        ]);

        ActivityLog::record('edit', "Mengedit dokumen: {$document->title}", $document);

        return redirect()->route('documents.show', $document)->with('success', __('messages.document_updated'));
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        $document->update(['status' => 'recycle_bin']);
        ActivityLog::record('delete', "Memindahkan ke recycle bin: {$document->title}", $document);

        return back()->with('success', __('messages.document_deleted'));
    }

    public function file(Document $document)
    {
        if ($document->status === 'recycle_bin' && !auth()->user()->hasRole('admin')) {
            abort(403, 'Dokumen berada di recycle bin.');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        return Storage::disk('local')->response($document->file_path, $document->file_original_name);
    }

    public function ocr(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $request->validate(['ocr_text' => 'required|string']);

        $document->update([
            'ocr_text' => $request->ocr_text,
            'ocr_processed' => true,
            'ocr_processed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        ActivityLog::record('ocr', "Menyimpan hasil OCR dokumen: {$document->title}", $document);

        return back()->with('success', __('messages.ocr_saved'));
    }

    public function processOcr(Document $document)
    {
        $this->authorize('update', $document);

        $filePath = Storage::disk('local')->path($document->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'error' => 'File tidak ditemukan'], 404);
        }

        $result = $this->ocrService->extractText($filePath, $document->file_original_name);

        if ($result['success']) {
            $document->update([
                'ocr_text' => $result['text'],
                'ocr_processed' => true,
                'ocr_processed_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            ActivityLog::record('ocr', "Memproses ekstraksi OCR dokumen: {$document->title}", $document);
        }

        return response()->json($result);
    }

    public function export(Request $request)
    {
        $query = Document::with(['category', 'subcategory', 'uploader'])->active();

        if ($request->filled('q')) {
            $query->search($request->q);
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('document_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('document_date', '<=', $request->date_to);
        }

        $documents = $query->latest('document_date')->get();

        ActivityLog::record('view', 'Mengekspor laporan rekapitulasi arsip dokumen');

        $fileName = 'rekap-arsip-sukarami-' . date('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($documents) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel on Windows opens Indonesian characters properly
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'Nomor Surat',
                'Judul Dokumen',
                'Kategori',
                'Subkategori',
                'Tanggal Dokumen',
                'Pengunggah',
                'Status OCR',
                'Keterangan',
                'Waktu Diunggah',
            ]);

            $no = 1;
            foreach ($documents as $doc) {
                fputcsv($handle, [
                    $no++,
                    $doc->document_number ?? $doc->nomor_surat ?? '-',
                    $doc->title,
                    $doc->category?->name ?? '-',
                    $doc->subcategory?->name ?? '-',
                    $doc->document_date ? $doc->document_date->format('d/m/Y') : '-',
                    $doc->uploader?->name ?? '-',
                    $doc->ocr_processed ? 'Selesai OCR' : 'Belum OCR',
                    $doc->description ?? '-',
                    $doc->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
