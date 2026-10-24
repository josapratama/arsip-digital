<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::firstOrCreate(
            ['key' => 'ocr_driver'],
            [
                'value' => 'local',
                'type' => 'text',
                'group' => 'system',
                'label' => 'Mesin Pembaca Teks (OCR)',
                'sort_order' => 2,
            ]
        );

        SiteSetting::firstOrCreate(
            ['key' => 'google_vision_api_key'],
            [
                'value' => env('GOOGLE_CLOUD_VISION_API_KEY', ''),
                'type' => 'text',
                'group' => 'system',
                'label' => 'Google Cloud Vision API Key',
                'sort_order' => 3,
            ]
        );
    }

    public function down(): void
    {
        SiteSetting::whereIn('key', ['ocr_driver', 'google_vision_api_key'])->delete();
    }
};
