<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\C45Service;

class UsulanLokasiController extends Controller
{
    public function create(C45Service $c45Service)
    {
        // Check if model is active, if not auto-train model seamlessly
        if (!$c45Service->hasActiveModel()) {
            // Auto seed if dataset empty
            if (empty($c45Service->getTrainingDataset())) {
                try {
                    (new \Database\Seeders\DataLatihSeeder())->run();
                } catch (\Exception $e) {
                    // Ignore if DB not ready
                }
            }
            // Auto-train C4.5 model
            $c45Service->trainModel();
        }

        $modelActive = $c45Service->hasActiveModel();
        $modelInfo = null;

        if ($modelActive) {
            $model = $c45Service->getActiveModel();
            $modelInfo = [
                'status' => 'aktif',
                'root_attribute' => $model->root_attribute ?? '',
                'total_rules' => $model->total_rules ?? 0,
                'trained_at' => $model->trained_at ?? '',
            ];
        }

        return view('usulan-lokasi.index', compact('modelActive', 'modelInfo'));
    }

    public function store(Request $request, C45Service $c45Service)
    {
        // 1. Cek apakah model C4.5 sudah aktif, jika belum auto-train
        if (!$c45Service->hasActiveModel()) {
            if (empty($c45Service->getTrainingDataset())) {
                try { (new \Database\Seeders\DataLatihSeeder())->run(); } catch (\Exception $e) {}
            }
            $c45Service->trainModel();
        }

        // 2. Validasi input
        $validated = $request->validate([
            'nama_lokasi' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'jenis_fasilitas' => 'required|string|in:TPS 3R,Biodigester,Bank Sampah',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'kepadatan' => 'required|string|in:Rendah,Sedang,Tinggi',
            'jarak_permukiman' => 'required|string',
            'jarak_air' => 'required|string',
        ]);

        $jenisFasilitas = $validated['jenis_fasilitas'] ?? 'TPS 3R';

        // 3. Klasifikasi menggunakan Decision Tree yang tersimpan
        $result = $c45Service->classifyWithModel(
            $validated['kepadatan'],
            $validated['jarak_permukiman'],
            $validated['jarak_air']
        );

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 422);
        }

        $status = $result['status'];
        $confidence = $result['confidence'];
        $rule = $result['rule'];
        $ruleId = $result['rule_id'];
        $ruleDetail = $result['rule_detail'];
        $modelId = $result['model_id'];

        // 4. Simpan HANYA ke tabel usulan_lokasi (TIDAK ke data_latih)
        $newUsulanId = 'usulan-1';
        $this->safeDb(function() use ($validated, $jenisFasilitas, $status, $rule, $confidence, $ruleId, $ruleDetail, $modelId, &$newUsulanId) {
            $insertedId = DB::table('usulan_lokasi')->insertGetId([
                'nama' => $validated['nama_lokasi'],
                'kecamatan' => $validated['kecamatan'],
                'jenis_fasilitas' => $jenisFasilitas,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'kepadatan' => $validated['kepadatan'],
                'jarak_permukiman' => $validated['jarak_permukiman'],
                'jarak_air' => $validated['jarak_air'],
                'status' => $status,
                'rule' => $rule,
                'confidence' => $confidence,
                'rule_id' => $ruleId,
                'rule_detail' => $ruleDetail,
                'predicted_at' => now(),
                'model_id' => $modelId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $newUsulanId = 'usulan-' . $insertedId;

            // TIDAK ada auto-sync ke data_latih
            // Data historis hanya dikelola melalui menu Kelola Data Latih
        }, function() use ($validated, $jenisFasilitas, $status, $rule, $confidence, $ruleId, $ruleDetail, $modelId, &$newUsulanId) {
            $mockUsulan = session('mock_usulan_lokasi', []);
            $numId = empty($mockUsulan) ? 1 : max(array_column($mockUsulan, 'id')) + 1;
            $newUsulanId = 'usulan-' . $numId;
            $newItem = [
                'id' => $numId,
                'nama' => $validated['nama_lokasi'],
                'kecamatan' => $validated['kecamatan'],
                'jenis_fasilitas' => $jenisFasilitas,
                'latitude' => (float)$validated['latitude'],
                'longitude' => (float)$validated['longitude'],
                'kepadatan' => $validated['kepadatan'],
                'jarak_permukiman' => $validated['jarak_permukiman'],
                'jarak_air' => $validated['jarak_air'],
                'status' => $status,
                'rule' => $rule,
                'confidence' => $confidence,
                'rule_id' => $ruleId,
                'rule_detail' => $ruleDetail,
                'predicted_at' => now()->toIso8601String(),
                'model_id' => $modelId,
                'created_at' => now()->toIso8601String(),
            ];
            $mockUsulan[] = $newItem;
            session(['mock_usulan_lokasi' => $mockUsulan]);

            // TIDAK ada auto-sync ke mock_data_latih
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Usulan lokasi berhasil diklasifikasikan menggunakan Decision Tree C4.5.',
            'data' => [
                'id' => $newUsulanId,
                'kecamatan' => $validated['kecamatan'],
                'status_klasifikasi' => $status,
                'rule' => $rule,
                'rule_id' => $ruleId,
                'rule_detail' => $ruleDetail,
                'confidence' => $confidence,
            ]
        ]);
    }
}
