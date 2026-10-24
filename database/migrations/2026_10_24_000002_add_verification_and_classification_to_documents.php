<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $prevCol = Schema::hasColumn('documents', 'document_number') ? 'document_number' : (Schema::hasColumn('documents', 'nomor_surat') ? 'nomor_surat' : null);
            if (!Schema::hasColumn('documents', 'verification_code')) {
                $col = $table->string('verification_code', 64)->nullable()->unique();
                if ($prevCol) {
                    $col->after($prevCol);
                }
            }
            if (!Schema::hasColumn('documents', 'classification_code') && !Schema::hasColumn('documents', 'kode_klasifikasi')) {
                $table->string('classification_code', 30)->nullable()->after('verification_code');
            }
        });

        SiteSetting::firstOrCreate(
            ['key' => 'enable_document_qr'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'system',
                'label' => 'Tampilkan QR Code Verifikasi pada Dokumen (Cetak/Unduh/Detail)',
                'sort_order' => 5,
            ]
        );
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('documents', 'verification_code')) $cols[] = 'verification_code';
            if (Schema::hasColumn('documents', 'classification_code')) $cols[] = 'classification_code';
            if (Schema::hasColumn('documents', 'kode_klasifikasi')) $cols[] = 'kode_klasifikasi';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        SiteSetting::where('key', 'enable_document_qr')->delete();
    }
};
