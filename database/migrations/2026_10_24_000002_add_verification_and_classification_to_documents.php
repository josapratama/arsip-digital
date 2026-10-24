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
            $table->string('verification_code', 64)->nullable()->unique()->after('nomor_surat');
            $table->string('kode_klasifikasi', 30)->nullable()->after('verification_code');
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
            $table->dropColumn(['verification_code', 'kode_klasifikasi']);
        });

        SiteSetting::where('key', 'enable_document_qr')->delete();
    }
};
