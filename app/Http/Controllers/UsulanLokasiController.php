<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\C45Service;
use Carbon\Carbon;

class UsulanLokasiController extends Controller
{
    /**
     * Tampilkan Halaman Usulan Lokasi TPS (Form Input & Daftar Usulan)
     */
    public function create(Request $request, C45Service $c45Service)
    {
        // 1. Cek / siapkan model C4.5
        if (!$c45Service->hasActiveModel()) {
            if (empty($c45Service->getTrainingDataset())) {
                try {
                    (new \Database\Seeders\DataLatihSeeder())->run();
                } catch (\Exception $e) {
                    // DB not ready fallback
                }
            }
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

        // 2. Ambil daftar usulan lokasi
        $usulanList = $this->safeDb(function() {
            return DB::table('usulan_lokasi')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'str_id' => 'usulan-' . $item->id,
                        'nama' => $item->nama,
                        'kecamatan' => $item->kecamatan,
                        'jenis_fasilitas' => $item->jenis_fasilitas ?? 'TPS 3R',
                        'latitude' => (float)$item->latitude,
                        'longitude' => (float)$item->longitude,
                        'kepadatan' => $item->kepadatan,
                        'jarak_permukiman' => $item->jarak_permukiman,
                        'jarak_air' => $item->jarak_air,
                        'status' => $item->status ?? 'menunggu_klasifikasi',
                        'rule' => $item->rule,
                        'rule_id' => $item->rule_id,
                        'rule_detail' => $item->rule_detail,
                        'confidence' => $item->confidence,
                        'petugas_nama' => $item->petugas_nama ?? 'Petugas Lapangan DLH',
                        'classified_by' => $item->classified_by ?? null,
                        'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('d M Y, H:i') : '-',
                        'predicted_at' => $item->predicted_at ? Carbon::parse($item->predicted_at)->format('d M Y, H:i') : null,
                    ];
                })->toArray();
        }, function() {
            $mockUsulan = session('mock_usulan_lokasi', []);
            return array_map(function($item) {
                return [
                    'id' => $item['id'] ?? 1,
                    'str_id' => 'usulan-' . ($item['id'] ?? 1),
                    'nama' => $item['nama'] ?? 'Usulan TPS',
                    'kecamatan' => $item['kecamatan'] ?? 'Cianjur',
                    'jenis_fasilitas' => $item['jenis_fasilitas'] ?? 'TPS 3R',
                    'latitude' => (float)($item['latitude'] ?? -6.8150),
                    'longitude' => (float)($item['longitude'] ?? 107.1380),
                    'kepadatan' => $item['kepadatan'] ?? 'Sedang',
                    'jarak_permukiman' => $item['jarak_permukiman'] ?? 'Sedang (200-500m)',
                    'jarak_air' => $item['jarak_air'] ?? 'Sedang (100-300m)',
                    'status' => $item['status'] ?? 'menunggu_klasifikasi',
                    'rule' => $item['rule'] ?? null,
                    'rule_id' => $item['rule_id'] ?? null,
                    'rule_detail' => $item['rule_detail'] ?? null,
                    'confidence' => $item['confidence'] ?? null,
                    'petugas_nama' => $item['petugas_nama'] ?? 'Petugas Lapangan DLH',
                    'classified_by' => $item['classified_by'] ?? null,
                    'created_at' => isset($item['created_at']) ? Carbon::parse($item['created_at'])->format('d M Y, H:i') : 'Baru saja',
                    'predicted_at' => isset($item['predicted_at']) ? Carbon::parse($item['predicted_at'])->format('d M Y, H:i') : null,
                ];
            }, $mockUsulan);
        });

        // Hitung statistik status
        $counts = [
            'total' => count($usulanList),
            'menunggu' => count(array_filter($usulanList, fn($u) => ($u['status'] ?? '') === 'menunggu_klasifikasi' || empty($u['status']))),
            'layak' => count(array_filter($usulanList, fn($u) => ($u['status'] ?? '') === 'layak')),
            'tidak_layak' => count(array_filter($usulanList, fn($u) => ($u['status'] ?? '') === 'tidak_layak')),
        ];

        $userRole = session('user_role', 'petugas');
        $userName = session('user_name', ($userRole === 'admin' ? 'Admin Dinas DLH' : 'Petugas Lapangan DLH'));

        return view('usulan-lokasi.index', compact('modelActive', 'modelInfo', 'usulanList', 'counts', 'userRole', 'userName'));
    }

    /**
     * Simpan Usulan Lokasi Baru (Oleh Petugas Lapangan / Admin)
     * Status Awal: "menunggu_klasifikasi" (TIDAK langsung diklasifikasikan oleh Petugas)
     */
    public function store(Request $request, C45Service $c45Service)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'nama_lokasi' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'jenis_fasilitas' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'kepadatan' => 'nullable|string',
            'jarak_permukiman' => 'required',
            'jarak_air' => 'required',
        ]);

        $jenisFasilitas = $validated['jenis_fasilitas'] ?? 'TPS 3R';
        $kecamatan = $validated['kecamatan'];

        // Auto lookup BPS Density jika belum terisi
        if (empty($validated['kepadatan'])) {
            $numDensity = C45Service::$bpsDensityMap[$kecamatan] ?? 1000;
            $validated['kepadatan'] = $c45Service->transformKepadatan($numDensity);
        }

        $petugasNama = session('user_name', 'Petugas Lapangan DLH');
        $petugasId = session('user_id', null);
        $userRole = session('user_role', 'petugas');

        // Status awal SELALU menunggu klasifikasi dari Admin
        $status = 'menunggu_klasifikasi';
        $rule = null;
        $confidence = null;
        $ruleId = null;
        $ruleDetail = null;
        $predictedAt = null;
        $modelId = null;
        $classifiedBy = null;

        // Jika user adalah Admin dan memilih langsung klasifikasi
        if ($userRole === 'admin' && $request->input('direct_classify') == true) {
            $prediction = $c45Service->predict(
                $validated['kepadatan'],
                (string)$validated['jarak_permukiman'],
                (string)$validated['jarak_air']
            );
            $status = $prediction['status'];
            $confidence = $prediction['confidence'];
            $rule = $prediction['rules_passed'][0] ?? 'Jarak Permukiman Rule';
            $ruleId = ($status === 'layak') ? 'R2' : 'R1';
            $ruleDetail = "Klasifikasi C4.5 Decision Tree BAB III";
            $predictedAt = now();
            $modelId = 1;
            $classifiedBy = $petugasNama;
        }

        $newUsulanId = 'usulan-1';
        $newNumericId = 1;

        $this->safeDb(function() use ($validated, $jenisFasilitas, $status, $rule, $confidence, $ruleId, $ruleDetail, $predictedAt, $modelId, $petugasId, $petugasNama, $classifiedBy, &$newUsulanId, &$newNumericId) {
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
                'petugas_id' => $petugasId,
                'petugas_nama' => $petugasNama,
                'rule' => $rule,
                'confidence' => $confidence,
                'rule_id' => $ruleId,
                'rule_detail' => $ruleDetail,
                'predicted_at' => $predictedAt,
                'model_id' => $modelId,
                'classified_by' => $classifiedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $newNumericId = $insertedId;
            $newUsulanId = 'usulan-' . $insertedId;
        }, function() use ($validated, $jenisFasilitas, $status, $rule, $confidence, $ruleId, $ruleDetail, $predictedAt, $modelId, $petugasId, $petugasNama, $classifiedBy, &$newUsulanId, &$newNumericId) {
            $mockUsulan = session('mock_usulan_lokasi', []);
            $numId = empty($mockUsulan) ? 1 : max(array_column($mockUsulan, 'id')) + 1;
            $newNumericId = $numId;
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
                'petugas_id' => $petugasId,
                'petugas_nama' => $petugasNama,
                'rule' => $rule,
                'confidence' => $confidence,
                'rule_id' => $ruleId,
                'rule_detail' => $ruleDetail,
                'predicted_at' => $predictedAt ? now()->toIso8601String() : null,
                'model_id' => $modelId,
                'classified_by' => $classifiedBy,
                'created_at' => now()->toIso8601String(),
            ];
            $mockUsulan[] = $newItem;
            session(['mock_usulan_lokasi' => $mockUsulan]);
        });

        $message = ($status === 'menunggu_klasifikasi')
            ? 'Data usulan lokasi TPS berhasil disimpan dengan status "Menunggu Klasifikasi". Admin DLH akan memeriksa dan melakukan proses klasifikasi C4.5.'
            : 'Usulan lokasi berhasil disimpan dan diklasifikasikan langsung sebagai ' . strtoupper($status) . '.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'id' => $newUsulanId,
                    'numeric_id' => $newNumericId,
                    'nama' => $validated['nama_lokasi'],
                    'kecamatan' => $validated['kecamatan'],
                    'status_data' => $status,
                    'confidence' => $confidence,
                    'rule' => $rule,
                ]
            ]);
        }

        return redirect()->route('usulan-lokasi', ['tab' => 'daftar'])->with('success', $message);
    }

    /**
     * Proses Klasifikasi Algoritma C4.5 pada Usulan Lokasi Terpilih (KHUSUS ADMIN)
     */
    public function klasifikasi(Request $request, $id, C45Service $c45Service)
    {
        // Pastikan C4.5 Model Aktif
        if (!$c45Service->hasActiveModel()) {
            $c45Service->trainModel();
        }

        $cleanId = is_numeric($id) ? (int)$id : (int)str_replace('usulan-', '', $id);
        $adminName = session('user_name', 'Administrator DLH');

        $result = null;

        $this->safeDb(function() use ($cleanId, $adminName, $c45Service, &$result) {
            $item = DB::table('usulan_lokasi')->where('id', $cleanId)->first();
            if (!$item) return;

            $prediction = $c45Service->predict(
                $item->kepadatan,
                (string)$item->jarak_permukiman,
                (string)$item->jarak_air
            );

            $status = $prediction['status'];
            $confidence = $prediction['confidence'];
            $rule = $prediction['rules_passed'][0] ?? 'Jarak Permukiman Rule';
            $ruleId = ($status === 'layak') ? 'R2' : 'R1';
            $ruleDetail = "Klasifikasi C4.5 Decision Tree BAB III";
            $modelId = 1;

            DB::table('usulan_lokasi')->where('id', $cleanId)->update([
                'status' => $status,
                'rule' => $rule,
                'confidence' => $confidence,
                'rule_id' => $ruleId,
                'rule_detail' => $ruleDetail,
                'predicted_at' => now(),
                'model_id' => $modelId,
                'classified_by' => $adminName,
                'updated_at' => now(),
            ]);

            $result = [
                'id' => $cleanId,
                'nama' => $item->nama,
                'status' => $status,
                'confidence' => $confidence,
                'rule' => $rule,
                'rule_id' => $ruleId,
                'rule_detail' => $ruleDetail,
                'classified_by' => $adminName,
            ];
        }, function() use ($cleanId, $adminName, $c45Service, &$result) {
            $mockUsulan = session('mock_usulan_lokasi', []);
            foreach ($mockUsulan as &$loc) {
                if (($loc['id'] ?? null) == $cleanId) {
                    $prediction = $c45Service->predict(
                        $loc['kepadatan'] ?? 'Sedang',
                        (string)($loc['jarak_permukiman'] ?? 'Sedang'),
                        (string)($loc['jarak_air'] ?? 'Sedang')
                    );

                    $status = $prediction['status'];
                    $confidence = $prediction['confidence'];
                    $rule = $prediction['rules_passed'][0] ?? 'Jarak Permukiman Rule';
                    $ruleId = ($status === 'layak') ? 'R2' : 'R1';

                    $loc['status'] = $status;
                    $loc['rule'] = $rule;
                    $loc['confidence'] = $confidence;
                    $loc['rule_id'] = $ruleId;
                    $loc['rule_detail'] = "Klasifikasi C4.5 Decision Tree BAB III";
                    $loc['predicted_at'] = now()->toIso8601String();
                    $loc['classified_by'] = $adminName;

                    $result = [
                        'id' => $cleanId,
                        'nama' => $loc['nama'] ?? 'Usulan TPS',
                        'status' => $status,
                        'confidence' => $confidence,
                        'rule' => $rule,
                        'rule_id' => $ruleId,
                        'rule_detail' => $loc['rule_detail'],
                        'classified_by' => $adminName,
                    ];
                    break;
                }
            }
            session(['mock_usulan_lokasi' => $mockUsulan]);
        });

        if (!$result) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Data usulan lokasi tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Data usulan lokasi tidak ditemukan.');
        }

        $statusLabel = ($result['status'] === 'layak') ? 'LAYAK' : 'TIDAK LAYAK';
        $message = "Usulan '{$result['nama']}' berhasil diklasifikasikan menggunakan Decision Tree C4.5: Status {$statusLabel} (Confidence: {$result['confidence']}).";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $result
            ]);
        }

        return redirect()->route('usulan-lokasi', ['tab' => 'daftar'])->with('success', $message);
    }

    /**
     * Batch Klasifikasi Semua Data Usulan yang Masih "Menunggu Klasifikasi" (KHUSUS ADMIN)
     */
    public function klasifikasiSemua(C45Service $c45Service)
    {
        if (!$c45Service->hasActiveModel()) {
            $c45Service->trainModel();
        }

        $adminName = session('user_name', 'Administrator DLH');
        $processedCount = 0;

        $this->safeDb(function() use ($adminName, $c45Service, &$processedCount) {
            $pendingList = DB::table('usulan_lokasi')
                ->where('status', 'menunggu_klasifikasi')
                ->orWhereNull('status')
                ->get();

            foreach ($pendingList as $item) {
                $prediction = $c45Service->predict(
                    $item->kepadatan,
                    (string)$item->jarak_permukiman,
                    (string)$item->jarak_air
                );

                $status = $prediction['status'];
                $confidence = $prediction['confidence'];
                $rule = $prediction['rules_passed'][0] ?? 'Jarak Permukiman Rule';
                $ruleId = ($status === 'layak') ? 'R2' : 'R1';

                DB::table('usulan_lokasi')->where('id', $item->id)->update([
                    'status' => $status,
                    'rule' => $rule,
                    'confidence' => $confidence,
                    'rule_id' => $ruleId,
                    'rule_detail' => "Klasifikasi C4.5 Decision Tree BAB III",
                    'predicted_at' => now(),
                    'model_id' => 1,
                    'classified_by' => $adminName,
                    'updated_at' => now(),
                ]);
                $processedCount++;
            }
        }, function() use ($adminName, $c45Service, &$processedCount) {
            $mockUsulan = session('mock_usulan_lokasi', []);
            foreach ($mockUsulan as &$loc) {
                if (($loc['status'] ?? 'menunggu_klasifikasi') === 'menunggu_klasifikasi') {
                    $prediction = $c45Service->predict(
                        $loc['kepadatan'] ?? 'Sedang',
                        (string)($loc['jarak_permukiman'] ?? 'Sedang'),
                        (string)($loc['jarak_air'] ?? 'Sedang')
                    );

                    $loc['status'] = $prediction['status'];
                    $loc['rule'] = $prediction['rules_passed'][0] ?? 'Jarak Permukiman Rule';
                    $loc['confidence'] = $prediction['confidence'];
                    $loc['rule_id'] = ($prediction['status'] === 'layak') ? 'R2' : 'R1';
                    $loc['rule_detail'] = "Klasifikasi C4.5 Decision Tree BAB III";
                    $loc['predicted_at'] = now()->toIso8601String();
                    $loc['classified_by'] = $adminName;
                    $processedCount++;
                }
            }
            session(['mock_usulan_lokasi' => $mockUsulan]);
        });

        $msg = ($processedCount > 0)
            ? "Berhasil melakukan klasifikasi C4.5 secara massal pada {$processedCount} usulan lokasi TPS."
            : "Tidak ada data usulan dengan status 'Menunggu Klasifikasi'.";

        return redirect()->route('usulan-lokasi', ['tab' => 'daftar'])->with('success', $msg);
    }

    /**
     * Hapus Data Usulan Lokasi (KHUSUS ADMIN)
     */
    public function destroy($id)
    {
        $cleanId = is_numeric($id) ? (int)$id : (int)str_replace('usulan-', '', $id);

        $this->safeDb(function() use ($cleanId) {
            DB::table('usulan_lokasi')->where('id', $cleanId)->delete();
        }, function() use ($cleanId) {
            $mockUsulan = session('mock_usulan_lokasi', []);
            $mockUsulan = array_values(array_filter($mockUsulan, fn($u) => ($u['id'] ?? null) != $cleanId));
            session(['mock_usulan_lokasi' => $mockUsulan]);
        });

        return redirect()->route('usulan-lokasi', ['tab' => 'daftar'])->with('success', 'Data usulan lokasi berhasil dihapus.');
    }
}
