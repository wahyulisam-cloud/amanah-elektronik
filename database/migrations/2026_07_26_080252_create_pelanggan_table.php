<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {

    $table->id('pelanggan_id');

    $table->string('pelanggan_nama',150);

    $table->string('pelanggan_alamat',200);

    $table->char('pelanggan_notelp',13);

    $table->string('pelanggan_email',100);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
