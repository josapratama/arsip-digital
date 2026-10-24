<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::firstOrCreate(
            ['key' => 'ocr_max_pdf_pages'],
            [
                'value' => '10',
                'type' => 'text',
                'group' => 'system',
                'label' => 'Maksimal Halaman PDF untuk OCR',
                'sort_order' => 4,
            ]
        );
    }

    public function down(): void
    {
        SiteSetting::where('key', 'ocr_max_pdf_pages')->delete();
    }
};
