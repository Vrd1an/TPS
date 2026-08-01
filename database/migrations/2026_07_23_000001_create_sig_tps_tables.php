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
        Schema::dropIfExists('evaluasi_model');
        Schema::dropIfExists('usulan_lokasi');
        Schema::dropIfExists('data_latih');

        Schema::create('data_latih', function (Blueprint $table) {
            $table->id();
            $table->string('nama_fasilitas');
            $table->string('alamat_desa');
            $table->string('jenis_fasilitas')->default('TPS 3R'); // TPS 3R, Biodigester, Bank Sampah
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('kepadatan'); // Rendah, Sedang, Tinggi
            $table->string('jarak_permukiman'); // Dekat, Sedang, Jauh
            $table->string('jarak_air'); // Dekat, Sedang, Jauh
            $table->string('status'); // layak, tidak_layak
            $table->timestamps();
        });

        Schema::create('usulan_lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kecamatan');
            $table->string('jenis_fasilitas')->default('TPS 3R'); // TPS 3R, Biodigester, Bank Sampah
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('kepadatan');
            $table->string('jarak_permukiman');
            $table->string('jarak_air');
            $table->string('status');
            $table->text('rule')->nullable();
            $table->string('confidence')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluasi_model', function (Blueprint $table) {
            $table->id();
            $table->integer('iterasi');
            $table->float('accuracy');
            $table->float('precision');
            $table->float('recall');
            $table->float('f1_score');
            $table->integer('tp');
            $table->integer('tn');
            $table->integer('fp');
            $table->integer('fn');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluasi_model');
        Schema::dropIfExists('usulan_lokasi');
        Schema::dropIfExists('data_latih');
    }
};
