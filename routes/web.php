<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OcrPreviewController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// ── Public: Landing Page & Document Verification ──────────────────────────
Route::get('/beranda', [LandingController::class, 'index'])->name('landing');
Route::redirect('/', '/beranda');
Route::get('/verify/{code}', [\App\Http\Controllers\DocumentVerificationController::class, 'verify'])->name('documents.verify');

// ── Authenticated routes ───────────────────────────────────────────────────────
Route::middleware(['auth', 'set.locale'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Documents & OCR
    Route::get('/ocr-scanner', [OcrPreviewController::class, 'scanner'])->name('ocr.scanner');
    Route::post('/documents/batch-ocr', [DocumentController::class, 'batchOcr'])->name('documents.batch-ocr');
    Route::get('/documents/export', [DocumentController::class, 'export'])->name('documents.export');
    Route::get('/documents/batch', [\App\Http\Controllers\DocumentBatchController::class, 'create'])->name('documents.batch.create');
    Route::post('/documents/batch', [\App\Http\Controllers\DocumentBatchController::class, 'store'])->name('documents.batch.store');
    Route::resource('documents', DocumentController::class)->except(['show']);
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
    Route::post('/documents/{document}/ocr', [DocumentController::class, 'ocr'])->name('documents.ocr');
    Route::post('/documents/{document}/process-ocr', [DocumentController::class, 'processOcr'])->name('documents.process-ocr');
    Route::post('/documents/ocr-preview', [OcrPreviewController::class, 'process'])->name('documents.ocr-preview');

    // Recycle Bin
    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/{document}/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
    Route::delete('/recycle-bin/{document}', [RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');

    // Activity Log & Admin Management — admin only
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

        // Admin: Category Management
        Route::get('/admin/categories', [CategoryController::class, 'index'])->name('admin.categories.index');
        Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
        Route::put('/admin/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

        // Admin: Site Settings
        Route::get('/admin/site-settings', [SiteSettingController::class, 'index'])->name('admin.site-settings.index');
        Route::post('/admin/site-settings', [SiteSettingController::class, 'update'])->name('admin.site-settings.update');
        Route::post('/admin/site-settings/image', [SiteSettingController::class, 'uploadImage'])->name('admin.site-settings.image');
        Route::post('/admin/site-settings/json', [SiteSettingController::class, 'updateJson'])->name('admin.site-settings.json');

        // Admin: User Management
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/preference', [ProfileController::class, 'updatePreference'])->name('profile.preference');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

});

require __DIR__ . '/auth.php';
