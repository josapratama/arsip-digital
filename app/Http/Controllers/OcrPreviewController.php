<?php

namespace App\Http\Controllers;

use App\Services\OcrService;
use Illuminate\Http\Request;

class OcrPreviewController extends Controller
{
    public function __construct(private OcrService $ocrService) {}

    public function process(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
        ]);

        $file = $request->file('file');
        $tempPath = $file->getRealPath();

        $result = $this->ocrService->extractText($tempPath);

        return response()->json($result);
    }
}
