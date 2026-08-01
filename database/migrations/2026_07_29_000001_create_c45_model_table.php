<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration untuk menyimpan model Decision Tree C4.5
     * Model dibangun dari data historis dan digunakan untuk klasifikasi usulan lokasi baru.
     */
    public function up(): void
    {
        Schema::dropIfExists('c45_model');

        Schema::create('c45_model', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('belum_dibentuk'); // belum_dibentuk, training, aktif
            $table->longText('tree_json')->nullable();           // Serialized decision tree structure
            $table->longText('rules_json')->nullable();          // Serialized IF-THEN rules
            $table->string('root_attribute')->nullable();        // Root node attribute name
            $table->integer('total_nodes')->default(0);          // Jumlah total node
            $table->integer('total_rules')->default(0);          // Jumlah rule IF-THEN
            $table->integer('total_data_latih')->default(0);     // Jumlah data historis saat training
            $table->float('entropy_total')->default(0);          // Entropy keseluruhan dataset
            $table->longText('training_log')->nullable();        // Log detail training (entropy, gain per step)
            $table->timestamp('trained_at')->nullable();         // Waktu selesai training
            $table->timestamps();
        });

        // Tambahkan kolom baru pada tabel usulan_lokasi untuk menyimpan detail prediksi
        if (Schema::hasTable('usulan_lokasi')) {
            Schema::table('usulan_lokasi', function (Blueprint $table) {
                if (!Schema::hasColumn('usulan_lokasi', 'rule_id')) {
                    $table->string('rule_id')->nullable()->after('confidence');
                }
                if (!Schema::hasColumn('usulan_lokasi', 'rule_detail')) {
                    $table->text('rule_detail')->nullable()->after('rule_id');
                }
                if (!Schema::hasColumn('usulan_lokasi', 'predicted_at')) {
                    $table->timestamp('predicted_at')->nullable()->after('rule_detail');
                }
                if (!Schema::hasColumn('usulan_lokasi', 'model_id')) {
                    $table->unsignedBigInteger('model_id')->nullable()->after('predicted_at');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('c45_model');

        if (Schema::hasTable('usulan_lokasi')) {
            Schema::table('usulan_lokasi', function (Blueprint $table) {
                $columns = ['rule_id', 'rule_detail', 'predicted_at', 'model_id'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('usulan_lokasi', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
