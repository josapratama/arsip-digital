<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OcrService
{
    protected string $apiUrl = 'https://vision.googleapis.com/v1/images:annotate';

    /**
     * Extract text from an image file path based on configured driver (local / google)
     */
    public function extractText(string $filePath): array
    {
        $driver = SiteSetting::get('ocr_driver', 'local');

        if ($driver === 'google') {
            return $this->extractWithGoogleVision($filePath);
        }

        return $this->extractWithTesseract($filePath);
    }

    /**
     * Extract text locally using Tesseract OCR (Free, Offline, No billing needed)
     */
    public function extractWithTesseract(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'local',
                'error' => 'File dokumen tidak ditemukan.',
            ];
        }

        // Handle PDF documents: convert pages to images and extract text
        if ($this->isPdf($filePath)) {
            $pageImages = $this->convertPdfToImages($filePath);
            if (empty($pageImages)) {
                return [
                    'success' => false,
                    'text' => '',
                    'driver' => 'local',
                    'error' => 'Gagal membaca file PDF untuk OCR. Pastikan file PDF valid dan tidak diproteksi kata sandi.',
                ];
            }

            $extractedTexts = [];
            foreach ($pageImages as $pageImg) {
                $pageRes = $this->extractSingleImageWithTesseract($pageImg);
                if (!empty($pageRes['text'])) {
                    $extractedTexts[] = $pageRes['text'];
                }
                @unlink($pageImg);
            }

            $combined = $this->cleanText(implode("\n\n", $extractedTexts));
            if (!empty($combined)) {
                return [
                    'success' => true,
                    'text' => $combined,
                    'driver' => 'local',
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'text' => '',
                'driver' => 'local',
                'error' => 'OCR Lokal selesai, namun tidak mendeteksi teks yang jelas pada file PDF ini.',
            ];
        }

        return $this->extractSingleImageWithTesseract($filePath);
    }

    /**
     * Extract text from a single image using preprocessing & Tesseract
     */
    protected function extractSingleImageWithTesseract(string $filePath): array
    {
        $preprocessedPath = null;

        try {
            // Intelligent preprocessing: illumination normalization, shadow cancellation, contrast enhancement
            $preprocessedPath = $this->preprocessImage($filePath);

            $pathToScan = ($preprocessedPath && file_exists($preprocessedPath))
                ? $preprocessedPath
                : $filePath;

            $escapedPath = escapeshellarg($pathToScan);
            $cmd = "tesseract {$escapedPath} stdout -l ind+eng 2>/dev/null";
            $output = shell_exec($cmd);

            // If preprocessed returned too little text, try raw image as fallback
            if (($output === null || strlen(trim($output)) < 30) && $pathToScan !== $filePath) {
                $rawEscaped = escapeshellarg($filePath);
                $rawOutput = shell_exec("tesseract {$rawEscaped} stdout -l ind+eng 2>/dev/null");
                if ($rawOutput !== null && strlen(trim($rawOutput)) > strlen(trim($output ?? ''))) {
                    $output = $rawOutput;
                }
            }

            // Fallback to just 'eng' if ind pack had any issue
            if ($output === null || trim($output) === '') {
                $cmdFallback = "tesseract {$escapedPath} stdout -l eng 2>/dev/null";
                $output = shell_exec($cmdFallback);
            }

            $cleaned = $this->cleanText($output ?? '');

            if (!empty($cleaned)) {
                return [
                    'success' => true,
                    'text' => $cleaned,
                    'driver' => 'local',
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'text' => '',
                'driver' => 'local',
                'error' => 'OCR Lokal (Tesseract) selesai, namun tidak mendeteksi teks yang jelas pada foto ini. Silakan pastikan foto surat cukup terang dan tidak terlalu jauh, atau isi data secara manual.',
            ];
        } catch (Exception $e) {
            Log::error('Tesseract OCR error', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'text' => '',
                'driver' => 'local',
                'error' => 'Terjadi kesalahan saat memproses OCR Lokal: ' . $e->getMessage(),
            ];
        } finally {
            if ($preprocessedPath && file_exists($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
        }
    }

    /**
     * Preprocess image for OCR: shadow removal, illumination equalization, contrast enhancement.
     */
    protected function preprocessImage(string $filePath): ?string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'ocr_pre_') . '.png';

        // Check if ImageMagick (magick or convert) is available
        $hasMagick = shell_exec('which magick 2>/dev/null');
        $hasConvert = shell_exec('which convert 2>/dev/null');
        $imBin = !empty(trim($hasMagick ?? '')) ? 'magick' : (!empty(trim($hasConvert ?? '')) ? 'convert' : null);

        if ($imBin) {
            $escapedInput = escapeshellarg($filePath);
            $escapedOutput = escapeshellarg($tempPath);

            // Division normalization: cancel shadows and equalize background lighting
            $cmd = "{$imBin} {$escapedInput} -colorspace Gray \\( +clone -blur 0x25 \\) -compose Divide -composite -linear-stretch 1%x1% {$escapedOutput} 2>&1";
            exec($cmd, $out, $ret);

            if ($ret === 0 && file_exists($tempPath) && filesize($tempPath) > 0) {
                return $tempPath;
            }
        }

        // Fallback to PHP GD if ImageMagick is not available or failed
        if (extension_loaded('gd')) {
            try {
                $imgInfo = @getimagesize($filePath);
                if (!$imgInfo) {
                    return null;
                }

                $source = match ($imgInfo[2]) {
                    IMAGETYPE_JPEG => @imagecreatefromjpeg($filePath),
                    IMAGETYPE_PNG => @imagecreatefrompng($filePath),
                    IMAGETYPE_WEBP => @imagecreatefromwebp($filePath),
                    default => null,
                };

                if ($source) {
                    imagefilter($source, IMG_FILTER_GRAYSCALE);
                    imagefilter($source, IMG_FILTER_CONTRAST, -25);
                    imagepng($source, $tempPath);
                    imagedestroy($source);

                    if (file_exists($tempPath) && filesize($tempPath) > 0) {
                        return $tempPath;
                    }
                }
            } catch (Exception) {
                // Ignore GD fallback failure
            }
        }

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return null;
    }

    /**
     * Extract text using Google Cloud Vision API
     */
    public function extractWithGoogleVision(string $filePath): array
    {
        $apiKey = SiteSetting::get('google_vision_api_key') ?: config('services.google.vision_api_key', '');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'google',
                'error' => 'API Key Google Cloud Vision belum dikonfigurasi. Silakan periksa pengaturan sistem atau gunakan OCR Lokal (Tesseract).',
            ];
        }

        try {
            $imageContent = base64_encode(file_get_contents($filePath));

            $response = Http::timeout(30)->post("{$this->apiUrl}?key={$apiKey}", [
                'requests' => [
                    [
                        'image' => [
                            'content' => $imageContent,
                        ],
                        'features' => [
                            [
                                'type' => 'DOCUMENT_TEXT_DETECTION',
                                'maxResults' => 1,
                            ],
                        ],
                        'imageContext' => [
                            'languageHints' => ['id', 'en'],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Google Vision API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $errBody = $response->json();
                $rawMsg = $errBody['error']['message'] ?? '';
                $reason = $errBody['error']['details'][0]['reason'] ?? '';

                if ($response->status() === 403 && (str_contains($rawMsg, 'billing') || $reason === 'BILLING_DISABLED')) {
                    $userError = 'Google Cloud Vision API memerlukan aktivasi Billing (Billing Disabled). Anda dapat beralih ke OCR Lokal (Tesseract) di menu Pengaturan.';
                } elseif ($response->status() === 403 || $response->status() === 401) {
                    $userError = 'Akses Google Vision API ditolak (Error ' . $response->status() . '). Silakan periksa izin dan konfigurasi API Key.';
                } else {
                    $userError = 'Layanan Google Vision gagal merespons (Status ' . $response->status() . '). Anda dapat beralih ke OCR Lokal atau mengisi form secara manual.';
                }

                return [
                    'success' => false,
                    'text' => '',
                    'driver' => 'google',
                    'error' => $userError,
                    'reason' => $reason ?: ('HTTP_' . $response->status()),
                ];
            }

            $data = $response->json();
            $text = $data['responses'][0]['fullTextAnnotation']['text'] ?? '';

            return [
                'success' => true,
                'text' => $this->cleanText($text),
                'driver' => 'google',
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('OCR Exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'text' => '',
                'driver' => 'google',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean extracted text
     */
    private function cleanText(string $text): string
    {
        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        // Normalize newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    /**
     * Check if a file path is a PDF document
     */
    public function isPdf(string $filePath): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return true;
        }

        if (file_exists($filePath) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = @finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mime === 'application/pdf';
        }

        return false;
    }

    /**
     * Render PDF pages to PNG image files using pdftoppm
     * @return string[] Array of generated image file paths
     */
    public function convertPdfToImages(string $pdfPath, int $maxPages = 3): array
    {
        $prefix = tempnam(sys_get_temp_dir(), 'pdf_ocr_');
        @unlink($prefix); // pdftoppm will append -1.png, -2.png, etc.

        $escapedPdf = escapeshellarg($pdfPath);
        $escapedPrefix = escapeshellarg($prefix);

        $cmd = "pdftoppm -png -r 200 -f 1 -l {$maxPages} {$escapedPdf} {$escapedPrefix} 2>/dev/null";
        exec($cmd, $out, $ret);

        $pattern = $prefix . '-*.png';
        $images = glob($pattern);

        return is_array($images) ? $images : [];
    }
}
