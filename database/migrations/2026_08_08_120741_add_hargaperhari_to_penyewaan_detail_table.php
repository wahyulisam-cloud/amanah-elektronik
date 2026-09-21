<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penyewaan_detail', function (Blueprint $table) {
            $table->decimal('penyewaan_detail_hargaperhari', 15, 2)
                ->after('penyewaan_detail_jumlah');
        });
    }

    public function down(): void
    {
        Schema::table('penyewaan_detail', function (Blueprint $table) {
            $table->dropColumn('penyewaan_detail_hargaperhari');
        });
    }
};