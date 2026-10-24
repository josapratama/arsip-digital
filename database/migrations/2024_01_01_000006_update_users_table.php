<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('name');
            $table->string('avatar')->nullable()->after('position');
            $table->string('locale', 5)->default('id')->after('avatar');
            $table->string('theme', 10)->default('light')->after('locale');
            $table->boolean('is_active')->default(true)->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = array_filter(['position', 'jabatan', 'avatar', 'locale', 'theme', 'is_active'], fn($c) => Schema::hasColumn('users', $c));
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
