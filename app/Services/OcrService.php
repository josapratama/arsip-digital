<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OcrService
{
    protected string $apiUrl = 'https://vision.googleapis.com/v1/images:annotate';

    private const BULAN_ID = [
        'januari' => '01', 'jan' => '01', 'january' => '01',
        'februari' => '02', 'feb' => '02', 'pebruari' => '02', 'peb' => '02', 'eebruari' => '02', 'eeb' => '02', 'febr' => '02', 'february' => '02',
        'maret' => '03', 'mar' => '03', 'mrt' => '03', 'march' => '03',
        'april' => '04', 'apr' => '04',
        'mei' => '05', 'may' => '05',
        'juni' => '06', 'jun' => '06', 'june' => '06',
        'juli' => '07', 'jul' => '07', 'july' => '07',
        'agustus' => '08', 'agu' => '08', 'ags' => '08', 'agst' => '08', 'agust' => '08', 'august' => '08',
        'september' => '09', 'sep' => '09', 'sept' => '09',
        'oktober' => '10', 'okt' => '10', 'october' => '10',
        'november' => '11', 'nov' => '11', 'nopember' => '11', 'nop' => '11',
        'desember' => '12', 'des' => '12', 'december' => '12',
    ];

    /**
     * Extract text and metadata from any supported document:
     * - Images (JPG, PNG, WebP) -> Local Tesseract or Google Cloud Vision OCR
     * - PDF Documents -> Direct Digital Text Extraction (Instant), fallback to OCR
     * - Word Documents (.docx) -> Direct Native XML Extraction
     * - Plain Text (.txt, .csv, .rtf) -> Direct UTF-8 Reading
     */
    public function extractText(string $filePath, ?string $originalName = null): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'none',
                'error' => 'File dokumen tidak ditemukan.',
            ];
        }

        // 1. Plain text documents (.txt, .csv, .rtf)
        if ($this->isText($filePath, $originalName)) {
            return $this->extractFromPlainText($filePath);
        }

        // 2. Microsoft Word documents (.docx)
        if ($this->isWord($filePath, $originalName)) {
            return $this->extractFromDocx($filePath);
        }

        // 3. PDF documents: Direct text extraction first, fallback to OCR
        if ($this->isPdf($filePath, $originalName)) {
            $directText = $this->extractDirectPdfText($filePath);
            if (!empty($directText) && strlen(trim($directText)) >= 30) {
                return [
                    'success' => true,
                    'text' => $directText,
                    'driver' => 'pdf_native',
                    'error' => null,
                    'metadata' => $this->parseMetadata($directText),
                ];
            }

            // Fallback for scanned PDF (image-only pages)
            $driver = SiteSetting::get('ocr_driver', 'local');
            if ($driver === 'google') {
                return $this->extractPdfWithGoogleVision($filePath);
            }

            return $this->extractPdfWithTesseract($filePath);
        }

        // 4. Image documents: OCR via configured driver
        $driver = SiteSetting::get('ocr_driver', 'local');
        if ($driver === 'google') {
            return $this->extractWithGoogleVision($filePath);
        }

        return $this->extractSingleImageWithTesseract($filePath);
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

        if ($this->isPdf($filePath)) {
            return $this->extractPdfWithTesseract($filePath);
        }

        return $this->extractSingleImageWithTesseract($filePath);
    }

    /**
     * Process scanned PDF using Tesseract OCR across pages
     */
    public function extractPdfWithTesseract(string $filePath): array
    {
        $maxPages = $this->getMaxPdfPages();
        $pageImages = $this->convertPdfToImages($filePath, $maxPages);

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
                'metadata' => $this->parseMetadata($combined),
            ];
        }

        return [
            'success' => false,
            'text' => '',
            'driver' => 'local',
            'error' => 'OCR Lokal selesai, namun tidak mendeteksi teks yang jelas pada file PDF ini.',
        ];
    }

    /**
     * Process scanned PDF using Google Cloud Vision across pages
     */
    public function extractPdfWithGoogleVision(string $filePath): array
    {
        $maxPages = $this->getMaxPdfPages();
        $pageImages = $this->convertPdfToImages($filePath, $maxPages);

        if (empty($pageImages)) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'google',
                'error' => 'Gagal membaca file PDF untuk Google Vision. Pastikan file PDF valid.',
            ];
        }

        $extractedTexts = [];
        $lastError = null;

        foreach ($pageImages as $pageImg) {
            $pageRes = $this->extractWithGoogleVision($pageImg);
            if (!empty($pageRes['text'])) {
                $extractedTexts[] = $pageRes['text'];
            } elseif (!empty($pageRes['error'])) {
                $lastError = $pageRes['error'];
            }
            @unlink($pageImg);
        }

        $combined = $this->cleanText(implode("\n\n", $extractedTexts));
        if (!empty($combined)) {
            return [
                'success' => true,
                'text' => $combined,
                'driver' => 'google',
                'error' => null,
                'metadata' => $this->parseMetadata($combined),
            ];
        }

        return [
            'success' => false,
            'text' => '',
            'driver' => 'google',
            'error' => $lastError ?: 'Google Cloud Vision tidak menemukan teks yang jelas pada dokumen ini.',
        ];
    }

    /**
     * Extract text directly from a digital PDF (Instant, 100% accurate, low CPU)
     */
    public function extractDirectPdfText(string $filePath, ?int $maxPages = null): ?string
    {
        $max = $maxPages ?: $this->getMaxPdfPages();

        // 1. Try pdftotext CLI (fastest & most accurate, available in poppler-utils)
        if ($this->isBinaryAvailable('pdftotext')) {
            $escaped = escapeshellarg($filePath);
            $cmd = "pdftotext -q -enc UTF-8 -f 1 -l {$max} {$escaped} - 2>/dev/null";
            $output = shell_exec($cmd);
            $cleaned = $this->cleanText($output ?? '');
            if (strlen($cleaned) >= 30) {
                return $cleaned;
            }
        }

        // 2. Pure PHP stream text decoder fallback
        return $this->extractPdfTextWithPhp($filePath, $max);
    }

    /**
     * Pure-PHP fallback extractor for PDF text streams
     */
    protected function extractPdfTextWithPhp(string $pdfPath, int $maxPages = 10): ?string
    {
        if (!file_exists($pdfPath)) {
            return null;
        }

        $content = @file_get_contents($pdfPath);
        if (!$content) {
            return null;
        }

        $text = '';
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $streamData) {
                $uncompressed = @gzuncompress($streamData);
                if ($uncompressed === false) {
                    $uncompressed = @gzinflate($streamData);
                }
                $data = ($uncompressed !== false) ? $uncompressed : $streamData;

                if (preg_match_all('/(?:BT\s*(.*?)\s*ET|(\((?:[^()\\\\]|\\\\.)*\))\s*Tj)/s', $data, $textBlocks)) {
                    foreach ($textBlocks[0] as $block) {
                        if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $tjs)) {
                            foreach ($tjs[1] as $str) {
                                $text .= stripcslashes($str) . ' ';
                            }
                        }
                        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjsArray)) {
                            foreach ($tjsArray[1] as $arr) {
                                if (preg_match_all('/\((.*?)\)/s', $arr, $parts)) {
                                    foreach ($parts[1] as $part) {
                                        $text .= stripcslashes($part);
                                    }
                                    $text .= ' ';
                                }
                            }
                        }
                    }
                }
            }
        }

        $clean = $this->cleanText($text);
        return strlen($clean) >= 30 ? $clean : null;
    }

    /**
     * Extract text directly from Microsoft Word (.docx) documents.
     */
    public function extractFromDocx(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'docx_native',
                'error' => 'File dokumen tidak ditemukan.',
            ];
        }

        if (!class_exists('ZipArchive')) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'docx_native',
                'error' => 'Ekstensi PHP ZipArchive tidak aktif pada server.',
            ];
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'docx_native',
                'error' => 'Gagal membuka file Word (.docx). File mungkin rusak atau terproteksi.',
            ];
        }

        $xmlIndex = $zip->locateName('word/document.xml');
        if ($xmlIndex === false) {
            $zip->close();
            return [
                'success' => false,
                'text' => '',
                'driver' => 'docx_native',
                'error' => 'Struktur internal file Word (.docx) tidak valid.',
            ];
        }

        $xmlContent = $zip->getFromIndex($xmlIndex);
        $zip->close();

        if (empty($xmlContent)) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'docx_native',
                'error' => 'Dokumen Word kosong.',
            ];
        }

        // Convert Word XML tags to formatted text
        $xmlContent = preg_replace('/<\/w:p>/i', "\n", $xmlContent);
        $xmlContent = preg_replace('/<w:br[^>]*\/>/i', "\n", $xmlContent);
        $xmlContent = preg_replace('/<w:tab[^>]*\/>/i', "    ", $xmlContent);
        $rawText = strip_tags($xmlContent);
        $rawText = html_entity_decode($rawText, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $cleaned = $this->cleanText($rawText);

        if (!empty($cleaned)) {
            return [
                'success' => true,
                'text' => $cleaned,
                'driver' => 'docx_native',
                'error' => null,
                'metadata' => $this->parseMetadata($cleaned),
            ];
        }

        return [
            'success' => false,
            'text' => '',
            'driver' => 'docx_native',
            'error' => 'Dokumen Word tidak berisi teks yang dapat dibaca.',
        ];
    }

    /**
     * Extract text directly from plain text (.txt, .rtf, .csv) files.
     */
    public function extractFromPlainText(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'text_native',
                'error' => 'File dokumen tidak ditemukan.',
            ];
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false) {
            return [
                'success' => false,
                'text' => '',
                'driver' => 'text_native',
                'error' => 'Gagal membaca isi file teks.',
            ];
        }

        // Normalize character encoding to UTF-8
        if (function_exists('mb_detect_encoding')) {
            $encoding = mb_detect_encoding($raw, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
            }
        }

        $cleaned = $this->cleanText($raw);

        if (!empty($cleaned)) {
            return [
                'success' => true,
                'text' => $cleaned,
                'driver' => 'text_native',
                'error' => null,
                'metadata' => $this->parseMetadata($cleaned),
            ];
        }

        return [
            'success' => false,
            'text' => '',
            'driver' => 'text_native',
            'error' => 'File teks kosong.',
        ];
    }

    /**
     * Extract text from a single image using preprocessing & Tesseract
     */
    protected function extractSingleImageWithTesseract(string $filePath): array
    {
        $preprocessedPath = null;

        try {
            // Intelligent preprocessing: auto-orient, shadow removal, contrast enhancement
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
                    'metadata' => $this->parseMetadata($cleaned),
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
     * Preprocess image for OCR:
     * - Auto-orient (correct camera rotation via EXIF)
     * - Shadow removal & background illumination normalization
     * - Contrast enhancement
     */
    protected function preprocessImage(string $filePath): ?string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'ocr_pre_') . '.png';

        // Check if ImageMagick is available
        $imBin = $this->isBinaryAvailable('magick') ? 'magick' : ($this->isBinaryAvailable('convert') ? 'convert' : null);

        if ($imBin) {
            $escapedInput = escapeshellarg($filePath);
            $escapedOutput = escapeshellarg($tempPath);

            // -auto-orient fixes rotated smartphone photos before running filters
            $cmd = "{$imBin} {$escapedInput} -auto-orient -colorspace Gray \\( +clone -blur 0x25 \\) -compose Divide -composite -linear-stretch 1%x1% {$escapedOutput} 2>&1";
            exec($cmd, $out, $ret);

            if ($ret === 0 && file_exists($tempPath) && filesize($tempPath) > 0) {
                return $tempPath;
            }
        }

        // Fallback to PHP GD
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
                    // Check EXIF orientation if JPEG
                    if (function_exists('exif_read_data') && $imgInfo[2] === IMAGETYPE_JPEG) {
                        $exif = @exif_read_data($filePath);
                        if (!empty($exif['Orientation'])) {
                            $source = match ($exif['Orientation']) {
                                3 => imagerotate($source, 180, 0),
                                6 => imagerotate($source, -90, 0),
                                8 => imagerotate($source, 90, 0),
                                default => $source,
                            };
                        }
                    }

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
            $cleaned = $this->cleanText($text);

            return [
                'success' => true,
                'text' => $cleaned,
                'driver' => 'google',
                'error' => null,
                'metadata' => $this->parseMetadata($cleaned),
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
    public function cleanText(string $text): string
    {
        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        // Normalize newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    /**
     * Automatically parse document metadata (nomor surat, tanggal, perihal/judul)
     * from extracted text using Indonesian government document patterns.
     */
    public function parseMetadata(string $text): array
    {
        $res = [
            'document_number' => '',
            'nomor_surat' => '',
            'document_date' => '',
            'title' => '',
        ];

        if (empty(trim($text)) || strlen(trim($text)) < 5) {
            return $res;
        }

        // 1. Detect Nomor Surat / Document Number
        $patterns = [
            '/(?:nomor|no\.?|nomer)\s*[:\s«_=-]\s*([A-Za-z0-9][A-Za-z0-9.\/\-_ ()]{3,60}\d{4})/i',
            '/(?:nomor|no\.?|nomer)\s*[:\s«_=-]\s*([^\n\r]{3,70})/i',
            '/\b(\d{1,4}[\/.][A-Za-z0-9.\-_]{1,20}[\/.](?:[IVX]{1,4}[\/.])?\d{4})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = $matches[1] ?? $matches[0];
                $cleaned = $this->cleanNomorSurat($raw);
                if (strlen($cleaned) >= 3) {
                    $res['document_number'] = $cleaned;
                    $res['nomor_surat'] = $cleaned;
                    break;
                }
            }
        }

        // 2. Detect Tanggal
        $monthKeys = array_keys(self::BULAN_ID);
        usort($monthKeys, fn($a, $b) => strlen($b) - strlen($a));
        $monthRegex = implode('|', $monthKeys);

        if (preg_match('/\b(\d{1,2})\s+(' . $monthRegex . ')\s+(\d{4})\b/i', $text, $dMatch)) {
            $day = str_pad($dMatch[1], 2, '0', STR_PAD_LEFT);
            $month = self::BULAN_ID[strtolower($dMatch[2])] ?? '01';
            $year = $dMatch[3];
            $res['document_date'] = "{$year}-{$month}-{$day}";
        } elseif (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $dMatch)) {
            $res['document_date'] = $dMatch[0];
        } elseif (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', $text, $dMatch)) {
            $d = str_pad($dMatch[1], 2, '0', STR_PAD_LEFT);
            $m = str_pad($dMatch[2], 2, '0', STR_PAD_LEFT);
            $y = $dMatch[3];
            $res['document_date'] = "{$y}-{$m}-{$d}";
        }

        // 3. Detect Judul / Perihal
        if (preg_match('/(?:perihal|berihal|hal|subject|tentang)\s*[:\s«_=-]\s*([^\n\r]+)(?:\r?\n\s*([^\n\r]+))?/i', $text, $pMatch)) {
            $line1 = trim($pMatch[1] ?? '');
            $line2 = trim($pMatch[2] ?? '');
            $combined = $line1;
            $isLine2DateOrMeta = preg_match('/^(?:kepada|yth|di|nomor|lampiran|lamp|sifat|menimbang|mengingat|dengan)/i', $line2)
                || preg_match('/\b\d{1,2}\s+(?:' . $monthRegex . ')\s+\d{4}\b/i', $line2);

            if ($line2 && !$isLine2DateOrMeta && strlen($line2) > 2 && strlen($line2) < 80) {
                $combined .= ' ' . $line2;
            }
            $cleanTitle = preg_replace('/\s+/', ' ', $combined);
            $cleanTitle = preg_replace('/[^\w\s,.()\-\/]/', '', $cleanTitle);
            $res['title'] = mb_substr(trim($cleanTitle), 0, 120);
        } elseif (preg_match('/\b(surat\s+(?:keputusan|keterangan|undangan|pemberitahuan|permohonan|tugas|izin|pengantar|edaran)[^\n\r]{0,80})/i', $text, $sMatch)) {
            $cleanTitle = preg_replace('/\s+/', ' ', $sMatch[1]);
            $cleanTitle = preg_replace('/[^\w\s,.()\-\/]/', '', $cleanTitle);
            $res['title'] = mb_substr(trim($cleanTitle), 0, 120);
        }

        return $res;
    }

    /**
     * Clean and normalize raw extracted nomor surat
     */
    private function cleanNomorSurat(string $raw): string
    {
        $monthKeys = array_keys(self::BULAN_ID);
        usort($monthKeys, fn($a, $b) => strlen($b) - strlen($a));
        $monthRegex = implode('|', $monthKeys);

        $s = preg_replace('/\s+(?:[A-Za-z. -]+[\s,]+)?\d{1,2}\s+(?:' . $monthRegex . ')\s+\d{4}.*$/i', '', $raw);
        $s = preg_replace('/\s+(lampiran|perihal|berihal|hal|sifat|kepada|yth).*$/i', '', $s);
        $s = preg_replace('/^[«=_:\s.-]+/', '', $s);
        $s = preg_replace('/^O(\d)/', '0$1', $s);
        $s = preg_replace('/^Ol\//i', '01/', $s);
        $s = preg_replace('/[^\w\s.\/\-()]/', '', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = preg_replace('/\s*\/\s*/', '/', $s);
        return trim($s);
    }

    /**
     * Check if a file path is a PDF document
     */
    public function isPdf(string $filePath, ?string $originalName = null): bool
    {
        $ext = strtolower(pathinfo($originalName ?: $filePath, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return true;
        }

        if (file_exists($filePath)) {
            // PDF binary header check
            $handle = @fopen($filePath, 'rb');
            if ($handle) {
                $header = fread($handle, 1024);
                fclose($handle);
                if (str_contains($header, '%PDF-')) {
                    return true;
                }
            }

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = @finfo_file($finfo, $filePath);
                finfo_close($finfo);
                return $mime === 'application/pdf';
            }
        }

        return false;
    }

    /**
     * Check if a file path is a Microsoft Word document (.docx, .doc)
     */
    public function isWord(string $filePath, ?string $originalName = null): bool
    {
        $ext = strtolower(pathinfo($originalName ?: $filePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['docx', 'doc'])) {
            return true;
        }

        if (file_exists($filePath)) {
            // Inspect zip archive internal structure for Word XML
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === true) {
                    $hasDocXml = $zip->locateName('word/document.xml') !== false;
                    $zip->close();
                    if ($hasDocXml) {
                        return true;
                    }
                }
            }

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = @finfo_file($finfo, $filePath);
                finfo_close($finfo);
                return in_array($mime, [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/msword',
                ]);
            }
        }

        return false;
    }

    /**
     * Check if a file path is a plain text document
     */
    public function isText(string $filePath, ?string $originalName = null): bool
    {
        $ext = strtolower(pathinfo($originalName ?: $filePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['txt', 'csv', 'rtf', 'md'])) {
            return true;
        }

        if (file_exists($filePath) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = @finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return str_starts_with($mime ?? '', 'text/');
        }

        return false;
    }

    /**
     * Get maximum PDF pages to process from settings
     */
    public function getMaxPdfPages(): int
    {
        $max = (int) SiteSetting::get('ocr_max_pdf_pages', 10);
        return ($max > 0) ? min($max, 50) : 10;
    }

    /**
     * Render PDF pages to PNG image files using pdftoppm
     * @return string[] Array of generated image file paths
     */
    public function convertPdfToImages(string $pdfPath, ?int $maxPages = null): array
    {
        $max = $maxPages ?: $this->getMaxPdfPages();
        $prefix = tempnam(sys_get_temp_dir(), 'pdf_ocr_');
        @unlink($prefix); // pdftoppm will append -1.png, -2.png, etc.

        $escapedPdf = escapeshellarg($pdfPath);
        $escapedPrefix = escapeshellarg($prefix);

        $cmd = "pdftoppm -png -r 200 -f 1 -l {$max} {$escapedPdf} {$escapedPrefix} 2>/dev/null";
        exec($cmd, $out, $ret);

        $pattern = $prefix . '-*.png';
        $images = glob($pattern);

        return is_array($images) ? $images : [];
    }

    /**
     * Check if a system CLI binary exists
     */
    protected function isBinaryAvailable(string $bin): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            $out = @shell_exec("where {$bin} 2>nul");
            return !empty(trim($out ?? ''));
        }

        $out = @shell_exec("which {$bin} 2>/dev/null");
        return !empty(trim($out ?? ''));
    }
}
