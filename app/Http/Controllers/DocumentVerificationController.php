<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SiteSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentVerificationController extends Controller
{
    /**
     * Public verification page for document authenticity via QR code.
     */
    public function verify(string $code): Response
    {
        $document = Document::with(['category', 'subcategory', 'uploader'])
            ->where('verification_code', $code)
            ->where('status', 'aktif')
            ->first();

        $enableQr = (bool) SiteSetting::get('enable_document_qr', true);

        if (!$document) {
            return Inertia::render('Public/VerifyDocument', [
                'valid' => false,
                'code' => $code,
                'document' => null,
                'desa' => [
                    'nama' => SiteSetting::get('desa_name', 'Desa Sukarami'),
                    'kecamatan' => SiteSetting::get('kecamatan', 'Rambang'),
                    'kabupaten' => SiteSetting::get('kabupaten', 'Muara Enim'),
                ],
                'enable_qr' => $enableQr,
            ]);
        }

        // Record verification check
        ActivityLog::record('verify', "Memverifikasi keaslian dokumen via QR Code: {$document->title}", $document);

        return Inertia::render('Public/VerifyDocument', [
            'valid' => true,
            'code' => $code,
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'nomor_surat' => $document->nomor_surat,
                'kode_klasifikasi' => $document->kode_klasifikasi,
                'category_name' => $document->category?->name,
                'subcategory_name' => $document->subcategory?->name,
                'document_date' => $document->document_date?->format('d M Y'),
                'file_type' => $document->file_type,
                'file_size_formatted' => $document->file_size_formatted,
                'uploader_name' => $document->uploader?->name,
                'created_at' => $document->created_at->format('d M Y H:i'),
                'verification_code' => $document->verification_code,
            ],
            'desa' => [
                'nama' => SiteSetting::get('desa_name', 'Desa Sukarami'),
                'kecamatan' => SiteSetting::get('kecamatan', 'Rambang'),
                'kabupaten' => SiteSetting::get('kabupaten', 'Muara Enim'),
            ],
            'enable_qr' => $enableQr,
        ]);
    }
}
