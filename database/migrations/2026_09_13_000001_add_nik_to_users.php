<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->select('username')->groupBy('username')->havingRaw('COUNT(*) > 1')->exists()
            || DB::table('users')->whereNull('username')->orWhereRaw("TRIM(username) = ''")->exists()) {
            throw new RuntimeException('Migrasi NIK dibatalkan: perbaiki username kosong atau duplikat tanpa menggabungkan akun.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('nik')->nullable()->unique();
        });

        DB::table('users')->update(['nik' => DB::raw('username')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn('nik');
        });
    }
};
