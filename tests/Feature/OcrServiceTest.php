<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\OcrService;
use Tests\TestCase;

class OcrServiceTest extends TestCase
{
    public function test_ocr_extracts_text_using_local_tesseract(): void
    {
        SiteSetting::set('ocr_driver', 'local');

        // Create temporary test image with text
        $tempImage = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';
        $im = imagecreatetruecolor(500, 100);
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg);
        $text_color = imagecolorallocate($im, 0, 0, 0);
        imagestring($im, 5, 20, 40, 'DESA SUKARAMI 2026', $text_color);
        imagepng($im, $tempImage);
        imagedestroy($im);

        $ocrService = new OcrService();
        $result = $ocrService->extractText($tempImage);

        @unlink($tempImage);

        $this->assertTrue($result['success']);
        $this->assertEquals('local', $result['driver']);
        $this->assertStringContainsString('SUKARAMI', strtoupper($result['text']));
    }

    public function test_ocr_identifies_google_vision_driver(): void
    {
        SiteSetting::set('ocr_driver', 'google');
        SiteSetting::set('google_vision_api_key', '');

        $ocrService = new OcrService();
        $result = $ocrService->extractText('/tmp/nonexistent.png');

        $this->assertEquals('google', $result['driver']);
        $this->assertFalse($result['success']);

        // Reset back to local
        SiteSetting::set('ocr_driver', 'local');
    }

    public function test_ocr_extracts_letter_with_shadow_preprocessing(): void
    {
        if (!file_exists('/tmp/sample_letter.png')) {
            $this->markTestSkipped('Sample letter not present');
        }

        SiteSetting::set('ocr_driver', 'local');
        $ocrService = new OcrService();
        $result = $ocrService->extractText('/tmp/sample_letter.png');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('SUKARAMI', strtoupper($result['text']));
        $this->assertStringContainsString('TIWI', strtoupper($result['text']));
    }

    public function test_ocr_preview_endpoint_works_for_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create();
        $file = \Illuminate\Http\UploadedFile::fake()->image('test_letter.jpg', 500, 100);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->post(route('documents.ocr-preview'), [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'text', 'driver']);
    }

    public function test_is_pdf_detection(): void
    {
        $ocrService = new OcrService();
        $this->assertTrue($ocrService->isPdf('/tmp/document.pdf'));
        $this->assertFalse($ocrService->isPdf('/tmp/document.jpg'));
    }
}
