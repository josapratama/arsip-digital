<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\OcrService;
use Tests\TestCase;
use ZipArchive;

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

    public function test_ocr_extracts_docx_file_natively(): void
    {
        $tempDocx = tempnam(sys_get_temp_dir(), 'test_docx_') . '.docx';
        $zip = new ZipArchive();
        if ($zip->open($tempDocx, ZipArchive::CREATE) === true) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>SURAT UNDANGAN MUSYAWARAH DESA</w:t></w:r></w:p><w:p><w:r><w:t>Nomor: 005/12/SKR/2026</w:t></w:r></w:p><w:p><w:r><w:t>Tanggal: 20 Oktober 2026</w:t></w:r></w:p></w:body></w:document>';
            $zip->addFromString('word/document.xml', $xml);
            $zip->close();
        }

        $ocrService = new OcrService();
        $result = $ocrService->extractText($tempDocx, 'surat.docx');

        @unlink($tempDocx);

        $this->assertTrue($result['success']);
        $this->assertEquals('docx_native', $result['driver']);
        $this->assertStringContainsString('SURAT UNDANGAN', strtoupper($result['text']));
        $this->assertEquals('005/12/SKR/2026', $result['metadata']['nomor_surat']);
        $this->assertEquals('2026-10-20', $result['metadata']['document_date']);
    }

    public function test_ocr_extracts_plain_text_file_natively(): void
    {
        $tempTxt = tempnam(sys_get_temp_dir(), 'test_txt_') . '.txt';
        file_put_contents($tempTxt, "PEMERINTAH DESA SUKARAMI\nNomor: 140/01/SKR/2026\nPerihal: Pemberitahuan Kerja Bakti");

        $ocrService = new OcrService();
        $result = $ocrService->extractText($tempTxt, 'catatan.txt');

        @unlink($tempTxt);

        $this->assertTrue($result['success']);
        $this->assertEquals('text_native', $result['driver']);
        $this->assertStringContainsString('SUKARAMI', $result['text']);
        $this->assertEquals('140/01/SKR/2026', $result['metadata']['nomor_surat']);
    }

    public function test_ocr_metadata_extraction(): void
    {
        $ocrService = new OcrService();
        $text = "PEMERINTAH KABUPATEN MUARA ENIM\nNomor: 400.1.2.2/ 326 /SKR-RB / 2026\nPerihal: Undangan Rapat Musyawarah Desa Tahun 2026\n\nSukarami, 14 Oktober 2026";
        $meta = $ocrService->parseMetadata($text);

        $this->assertEquals('400.1.2.2/326/SKR-RB/2026', $meta['nomor_surat']);
        $this->assertEquals('2026-10-14', $meta['document_date']);
        $this->assertStringContainsString('Undangan Rapat Musyawarah', $meta['title']);
    }

    public function test_file_type_detections(): void
    {
        $ocrService = new OcrService();
        $this->assertTrue($ocrService->isPdf('/tmp/document.pdf'));
        $this->assertFalse($ocrService->isPdf('/tmp/document.jpg'));

        $this->assertTrue($ocrService->isWord('/tmp/surat.docx'));
        $this->assertFalse($ocrService->isWord('/tmp/foto.png'));

        $this->assertTrue($ocrService->isText('/tmp/catatan.txt'));
        $this->assertFalse($ocrService->isText('/tmp/surat.pdf'));
    }

    public function test_ocr_max_pdf_pages_setting(): void
    {
        SiteSetting::set('ocr_max_pdf_pages', '15');
        $ocrService = new OcrService();
        $this->assertEquals(15, $ocrService->getMaxPdfPages());

        SiteSetting::set('ocr_max_pdf_pages', '100'); // clamped to 50
        $this->assertEquals(50, $ocrService->getMaxPdfPages());

        // Reset
        SiteSetting::set('ocr_max_pdf_pages', '10');
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
}
