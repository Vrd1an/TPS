<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration untuk membuat tabel data statistik 32 Kecamatan di Kabupaten Cianjur
     */
    public function up(): void
    {
        Schema::dropIfExists('kecamatan');

        Schema::create('kecamatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kecamatan');
            $table->integer('jumlah_penduduk')->default(0); // Jumlah Jiwa dari BPS
            $table->decimal('luas_wilayah_km2', 10, 2)->default(0.00); // Luas Wilayah (Km2)
            $table->integer('kepadatan_angka')->default(0); // Kepadatan Jiwa/Km2
            $table->string('kepadatan_kategori')->default('Sedang'); // Rendah (< 500), Sedang (500-1500), Tinggi (> 1500)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecamatan');
    }
};
