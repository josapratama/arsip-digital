<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\ActivityLog;
use App\Services\OcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 2;

    public function __construct(public Document $document) {}

    public function handle(OcrService $ocrService): void
    {
        $filePath = Storage::disk('local')->path($this->document->file_path);

        if (!file_exists($filePath)) {
            Log::warning("OCR Job: File not found for document #{$this->document->id}");
            return;
        }

        $result = $ocrService->extractText($filePath, $this->document->file_original_name);

        if (!empty($result['success']) && !empty($result['text'])) {
            $this->document->update([
                'ocr_text' => $result['text'],
                'ocr_processed' => true,
                'ocr_processed_at' => now(),
            ]);

            ActivityLog::record('ocr', "OCR Latar Belakang selesai untuk dokumen: {$this->document->title}", $this->document);
        }
    }
}
