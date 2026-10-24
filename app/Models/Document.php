<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'nomor_surat',
        'verification_code',
        'kode_klasifikasi',
        'category_id',
        'subcategory_id',
        'file_path',
        'file_original_name',
        'file_type',
        'file_size',
        'ocr_text',
        'description',
        'document_date',
        'status',
        'ocr_processed',
        'ocr_processed_at',
        'uploaded_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            if (empty($document->verification_code)) {
                $document->verification_code = 'DS-SKR-' . strtoupper(\Illuminate\Support\Str::random(10));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'ocr_processed' => 'boolean',
            'ocr_processed_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    // Resilient full-text search scope using PostgreSQL websearch_to_tsquery + fallback
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $clean = trim($keyword);
        if ($clean === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($clean) {
            $sub->whereRaw("ocr_text_tsv @@ websearch_to_tsquery('indonesian', ?)", [$clean])
                ->orWhere('title', 'ilike', "%{$clean}%")
                ->orWhere('nomor_surat', 'ilike', "%{$clean}%")
                ->orWhere('description', 'ilike', "%{$clean}%");
        })->orderByRaw(
            "ts_rank(ocr_text_tsv, websearch_to_tsquery('indonesian', ?)) DESC, created_at DESC",
            [$clean]
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeInRecycleBin(Builder $query): Builder
    {
        return $query->where('status', 'recycle_bin');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type ?? '', 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return ($this->file_type === 'application/pdf')
            || str_ends_with(strtolower($this->file_original_name ?? ''), '.pdf');
    }

    public function getIsWordAttribute(): bool
    {
        $mime = $this->file_type ?? '';
        $ext = strtolower(pathinfo($this->file_original_name ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['docx', 'doc'])
            || str_contains($mime, 'wordprocessingml')
            || str_contains($mime, 'msword');
    }

    public function getIsTextAttribute(): bool
    {
        $mime = $this->file_type ?? '';
        $ext = strtolower(pathinfo($this->file_original_name ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['txt', 'csv', 'rtf', 'md'])
            || str_starts_with($mime, 'text/');
    }

    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_original_name ?? '', PATHINFO_EXTENSION));
    }

    public function getCanExtractTextAttribute(): bool
    {
        return $this->is_image || $this->is_pdf || $this->is_word || $this->is_text;
    }
}
