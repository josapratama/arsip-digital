<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\OcrService;
use App\Services\KlasifikasiKemendagriService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OcrPreviewController extends Controller
{
    public function __construct(
        private OcrService $ocrService,
        private KlasifikasiKemendagriService $kemendagriService
    ) {}

    /**
     * Render the dedicated Standalone OCR Scanner Studio page.
     */
    public function scanner(): Response
    {
        return Inertia::render('Ocr/Scanner', [
            'kemendagri_list' => $this->kemendagriService->getAll(),
            'categories' => Category::root()->active()->with('children:id,name,name_en,parent_id')->get(['id', 'name', 'name_en', 'color', 'icon']),
        ]);
    }

    /**
     * Process OCR text extraction on uploaded file or camera capture.
     */
    public function process(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,docx,doc,txt,csv,rtf|max:20480',
            'rotate' => 'nullable|integer|in:0,90,180,270',
        ]);

        $file = $request->file('file');
        $tempPath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();
        $rotation = (int) $request->input('rotate', 0);

        $result = $this->ocrService->extractText($tempPath, $originalName, $rotation);

        if (!empty($result['success']) && !empty($result['text'])) {
            $documentNumber = $result['metadata']['document_number'] ?? $result['metadata']['nomor_surat'] ?? null;
            $title = $result['metadata']['title'] ?? null;
            $result['kemendagri'] = $this->kemendagriService->suggest($result['text'], $title, $documentNumber);
            $result['entities'] = [
                'niks' => $result['metadata']['niks'] ?? [],
                'money_amounts' => $result['metadata']['money_amounts'] ?? [],
                'phone_numbers' => $result['metadata']['phone_numbers'] ?? [],
                'emails' => $result['metadata']['emails'] ?? [],
            ];
            $result['stats'] = [
                'word_count' => str_word_count($result['text']),
                'character_count' => mb_strlen($result['text']),
                'line_count' => count(explode("\n", $result['text'])),
            ];
        }

        return response()->json($result);
    }
}
