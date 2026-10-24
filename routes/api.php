<?php

use App\Http\Controllers\OcrPreviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/ocr-preview', [OcrPreviewController::class, 'process']);
});
