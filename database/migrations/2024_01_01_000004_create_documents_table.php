<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('nomor_surat')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_original_name');
            $table->string('file_type'); // image/jpeg, image/png, application/pdf
            $table->unsignedBigInteger('file_size'); // bytes
            $table->text('ocr_text')->nullable();
            $table->text('description')->nullable();
            $table->date('document_date')->nullable();
            $table->string('status')->default('aktif'); // aktif, recycle_bin
            $table->boolean('ocr_processed')->default(false);
            $table->timestamp('ocr_processed_at')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // Add full-text search index using PostgreSQL tsvector
        DB::statement("ALTER TABLE documents ADD COLUMN ocr_text_tsv tsvector GENERATED ALWAYS AS (
            to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(nomor_surat, '') || ' ' || coalesce(ocr_text, '') || ' ' || coalesce(description, ''))
        ) STORED");

        DB::statement("CREATE INDEX documents_ocr_text_tsv_idx ON documents USING GIN(ocr_text_tsv)");
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
