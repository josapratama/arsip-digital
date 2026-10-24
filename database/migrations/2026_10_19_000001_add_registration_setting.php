<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::firstOrCreate(
            ['key' => 'enable_registration'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'system',
                'label' => 'Izinkan Pendaftaran Pengguna Baru',
                'sort_order' => 1,
            ]
        );
    }

    public function down(): void
    {
        SiteSetting::where('key', 'enable_registration')->delete();
    }
};
