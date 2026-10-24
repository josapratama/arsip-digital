<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Services\KlasifikasiKemendagriService;
use App\Services\OcrService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentBatchController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private OcrService $ocrService,
        private KlasifikasiKemendagriService $kemendagriService
    ) {}

    public function create(): Response
    {
        $this->authorize('create', Document::class);

        return Inertia::render('Documents/BatchUpload', [
            'categories' => Category::root()
                ->active()
                ->with('children:id,name,name_en,parent_id')
                ->get(['id', 'name', 'name_en', 'color', 'icon']),
            'kemendagri_list' => $this->kemendagriService->getAll(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

        $request->validate([
            'items' => 'required|array|min:1|max:50',
            'items.*.title' => 'required|string|max:255',
            'items.*.nomor_surat' => 'nullable|string|max:100',
            'items.*.kode_klasifikasi' => 'nullable|string|max:30',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.subcategory_id' => 'nullable|exists:categories,id',
            'items.*.document_date' => 'nullable|date',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.ocr_text' => 'nullable|string',
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,docx,doc,txt,csv,rtf|max:20480',
        ]);

        $items = $request->input('items', []);
        $files = $request->file('files', []);
        $uploadedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($items as $index => $item) {
                if (!isset($files[$index])) {
                    continue;
                }

                $file = $files[$index];
                $path = $file->store('documents/' . date('Y/m'), 'local');
                $ocrText = $item['ocr_text'] ?? null;

                // If OCR text was not supplied from client preview, extract text automatically
                if (empty($ocrText)) {
                    $fullPath = Storage::disk('local')->path($path);
                    if (file_exists($fullPath)) {
                        $auto = $this->ocrService->extractText($fullPath, $file->getClientOriginalName());
                        if (!empty($auto['success']) && !empty($auto['text'])) {
                            $ocrText = $auto['text'];
                        }
                    }
                }

                $document = Document::create([
                    'title' => $item['title'],
                    'nomor_surat' => $item['nomor_surat'] ?? null,
                    'kode_klasifikasi' => $item['kode_klasifikasi'] ?? null,
                    'category_id' => $item['category_id'],
                    'subcategory_id' => !empty($item['subcategory_id']) ? $item['subcategory_id'] : null,
                    'document_date' => !empty($item['document_date']) ? $item['document_date'] : null,
                    'description' => $item['description'] ?? null,
                    'file_path' => $path,
                    'file_original_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'ocr_text' => $ocrText,
                    'ocr_processed' => !empty($ocrText),
                    'ocr_processed_at' => !empty($ocrText) ? now() : null,
                    'uploaded_by' => auth()->id(),
                    'status' => 'aktif',
                ]);

                ActivityLog::record('upload', "Unggah masal dokumen: {$document->title}", $document);
                $uploadedCount++;
            }

            DB::commit();

            return redirect()->route('documents.index')->with('success', "Berhasil mengunggah {$uploadedCount} dokumen sekaligus!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal memproses unggah masal: ' . $e->getMessage()]);
        }
    }
}
