<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardize all table columns to English:
     * - documents.nomor_surat -> documents.document_number
     * - documents.kode_klasifikasi -> documents.classification_code
     * - users.jabatan -> users.position
     * - update status 'aktif' -> 'active'
     * - recreate ocr_text_tsv index using document_number
     */
    public function up(): void
    {
        // 1. Rename columns in 'documents' table
        if (Schema::hasTable('documents')) {
            // Drop generated column first if exists before renaming
            try {
                DB::statement("ALTER TABLE documents DROP COLUMN IF EXISTS ocr_text_tsv");
            } catch (\Throwable $e) {
                // Ignore if column doesn't exist
            }

            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'nomor_surat') && !Schema::hasColumn('documents', 'document_number')) {
                    $table->renameColumn('nomor_surat', 'document_number');
                }
                if (Schema::hasColumn('documents', 'kode_klasifikasi') && !Schema::hasColumn('documents', 'classification_code')) {
                    $table->renameColumn('kode_klasifikasi', 'classification_code');
                }
            });

            // Re-create the PostgreSQL tsvector full-text index with 'document_number'
            try {
                DB::statement("ALTER TABLE documents ADD COLUMN IF EXISTS ocr_text_tsv tsvector GENERATED ALWAYS AS (
                    to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(document_number, '') || ' ' || coalesce(ocr_text, '') || ' ' || coalesce(description, ''))
                ) STORED");

                DB::statement("CREATE INDEX IF NOT EXISTS documents_ocr_text_tsv_idx ON documents USING GIN(ocr_text_tsv)");
            } catch (\Throwable $e) {
                // Ignore if already created
            }

            // Update status 'aktif' to English 'active'
            try {
                DB::table('documents')->where('status', 'aktif')->update(['status' => 'active']);
            } catch (\Throwable $e) {
                // Ignore if table is empty or driver difference
            }
        }

        // 2. Rename columns in 'users' table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'jabatan') && !Schema::hasColumn('users', 'position')) {
                    $table->renameColumn('jabatan', 'position');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert 'users' table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'position') && !Schema::hasColumn('users', 'jabatan')) {
                    $table->renameColumn('position', 'jabatan');
                }
            });
        }

        // 2. Revert 'documents' table
        if (Schema::hasTable('documents')) {
            try {
                DB::statement("ALTER TABLE documents DROP COLUMN IF EXISTS ocr_text_tsv");
            } catch (\Throwable $e) {
                // Ignore
            }

            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'document_number') && !Schema::hasColumn('documents', 'nomor_surat')) {
                    $table->renameColumn('document_number', 'nomor_surat');
                }
                if (Schema::hasColumn('documents', 'classification_code') && !Schema::hasColumn('documents', 'kode_klasifikasi')) {
                    $table->renameColumn('classification_code', 'kode_klasifikasi');
                }
            });

            try {
                DB::statement("ALTER TABLE documents ADD COLUMN IF EXISTS ocr_text_tsv tsvector GENERATED ALWAYS AS (
                    to_tsvector('indonesian', coalesce(title, '') || ' ' || coalesce(nomor_surat, '') || ' ' || coalesce(ocr_text, '') || ' ' || coalesce(description, ''))
                ) STORED");

                DB::statement("CREATE INDEX IF NOT EXISTS documents_ocr_text_tsv_idx ON documents USING GIN(ocr_text_tsv)");
            } catch (\Throwable $e) {
                // Ignore
            }

            try {
                DB::table('documents')->where('status', 'active')->update(['status' => 'aktif']);
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
