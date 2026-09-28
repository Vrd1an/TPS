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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role')) {
                    $table->string('role')->default('petugas')->after('email');
                }
            });
        }

        if (Schema::hasTable('usulan_lokasi')) {
            Schema::table('usulan_lokasi', function (Blueprint $table) {
                if (!Schema::hasColumn('usulan_lokasi', 'petugas_id')) {
                    $table->unsignedBigInteger('petugas_id')->nullable()->after('status');
                }
                if (!Schema::hasColumn('usulan_lokasi', 'petugas_nama')) {
                    $table->string('petugas_nama')->nullable()->after('petugas_id');
                }
                if (!Schema::hasColumn('usulan_lokasi', 'classified_by')) {
                    $table->string('classified_by')->nullable()->after('model_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role')) {
                    $table->dropColumn('role');
                }
            });
        }

        if (Schema::hasTable('usulan_lokasi')) {
            Schema::table('usulan_lokasi', function (Blueprint $table) {
                $cols = ['petugas_id', 'petugas_nama', 'classified_by'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('usulan_lokasi', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
