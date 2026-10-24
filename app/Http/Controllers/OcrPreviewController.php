<?php

namespace App\Http\Controllers;

use App\Services\OcrService;
use App\Services\KlasifikasiKemendagriService;
use Illuminate\Http\Request;

class OcrPreviewController extends Controller
{
    public function __construct(
        private OcrService $ocrService,
        private KlasifikasiKemendagriService $kemendagriService
    ) {}

    public function process(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,docx,doc,txt,csv,rtf|max:20480',
        ]);

        $file = $request->file('file');
        $tempPath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();

        $result = $this->ocrService->extractText($tempPath, $originalName);

        if (!empty($result['success']) && !empty($result['text'])) {
            $documentNumber = $result['metadata']['document_number'] ?? $result['metadata']['nomor_surat'] ?? null;
            $title = $result['metadata']['title'] ?? null;
            $result['kemendagri'] = $this->kemendagriService->suggest($result['text'], $title, $documentNumber);
        }

        return response()->json($result);
    }
}
