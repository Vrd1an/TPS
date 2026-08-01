<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\C45Service;
use Database\Seeders\DataLatihSeeder;

class DataLatihController extends Controller
{
    public function index(C45Service $c45Service)
    {
        $dataset = $this->safeDb(function() {
            return DB::table('data_latih')->get()->map(fn($r) => (array)$r)->toArray();
        }, function() use ($c45Service) {
            return $c45Service->getTrainingDataset();
        });

        // Get model status
        $modelInfo = $this->safeDb(function() {
            $model = DB::table('c45_model')
                ->where('status', 'aktif')
                ->orderBy('trained_at', 'desc')
                ->first();

            if ($model) {
                return [
                    'status' => 'aktif',
                    'root_attribute' => $model->root_attribute,
                    'total_rules' => $model->total_rules,
                    'total_nodes' => $model->total_nodes,
                    'total_data_latih' => $model->total_data_latih,
                    'entropy_total' => $model->entropy_total,
                    'trained_at' => $model->trained_at,
                ];
            }

            return ['status' => 'belum_dibentuk'];
        }, function() {
            return ['status' => 'belum_dibentuk'];
        });

        return view('data-latih.index', compact('dataset', 'modelInfo'));
    }

    /**
     * Inisialisasi Data Latih (UC-03: Database Seeder Execution)
     */
    public function seed()
    {
        $this->safeDb(function() {
            $seeder = new DataLatihSeeder();
            $seeder->run();
        }, function() {
            session()->forget(['mock_data_latih', 'mock_usulan_lokasi']);
        });

        return redirect()->route('data-latih')->with('success', 'Berhasil menginisialisasi dataset historis TPS ke basis data.');
    }

    /**
     * Kosongkan Data Latih & Usulan
     */
    public function clear()
    {
        $this->safeDb(function() {
            DB::table('data_latih')->truncate();
            DB::table('usulan_lokasi')->truncate();
            DB::table('evaluasi_model')->truncate();
            // Nonaktifkan model karena data latih dikosongkan
            DB::table('c45_model')->update(['status' => 'nonaktif']);
        }, function() {
            session()->forget(['mock_data_latih', 'mock_usulan_lokasi']);
        });

        return redirect()->route('data-latih')->with('success', 'Seluruh data latih dan usulan lokasi berhasil dikosongkan. Model C4.5 dinonaktifkan.');
    }

    /**
     * Proses Training C4.5 — Membangun Decision Tree dari Data Historis
     */
    public function train(C45Service $c45Service)
    {
        try {
            // Set status training
            $this->safeDb(function() {
                DB::table('c45_model')
                    ->where('status', 'aktif')
                    ->update(['status' => 'nonaktif']);
            }, function() {});

            // Jalankan training
            $result = $c45Service->trainModel();

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => [
                        'model_id' => $result['model_id'],
                        'root_attribute' => $result['statistics']['root_attribute'],
                        'total_rules' => $result['statistics']['total_rules'],
                        'total_nodes' => $result['statistics']['total_nodes'],
                        'total_data_latih' => $result['statistics']['total_data_latih'],
                        'entropy_total' => $result['statistics']['entropy_total'],
                        'gain_summary' => $result['statistics']['gain_summary'],
                        'trained_at' => $result['statistics']['trained_at'],
                        'rules' => $result['rules'],
                    ],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Training gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get Model Status
     */
    public function getModelStatus(C45Service $c45Service)
    {
        $model = $c45Service->getActiveModel();

        if ($model) {
            return response()->json([
                'status' => 'aktif',
                'model_id' => $model->id,
                'root_attribute' => $model->root_attribute,
                'total_rules' => $model->total_rules,
                'total_nodes' => $model->total_nodes,
                'total_data_latih' => $model->total_data_latih,
                'entropy_total' => $model->entropy_total,
                'trained_at' => $model->trained_at,
            ]);
        }

        return response()->json([
            'status' => 'belum_dibentuk',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_fasilitas' => 'required|string|max:255',
            'alamat_desa' => 'required|string|max:255',
            'jenis_fasilitas' => 'required|string|in:TPS 3R,Biodigester,Bank Sampah',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'kepadatan' => 'required|string|in:Rendah,Sedang,Tinggi',
            'jarak_permukiman' => 'required|string|in:Dekat,Sedang,Jauh',
            'jarak_air' => 'required|string|in:Dekat,Sedang,Jauh',
            'status' => 'required|string|in:layak,tidak_layak',
        ]);

        $this->safeDb(function() use ($validated) {
            DB::table('data_latih')->insert(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            // Nonaktifkan model karena data berubah — perlu re-training
            DB::table('c45_model')->where('status', 'aktif')->update(['status' => 'nonaktif']);
        }, function() use ($validated) {
            $mock = session('mock_data_latih', []);
            $newId = empty($mock) ? 1 : max(array_column($mock, 'id')) + 1;
            $mock[] = array_merge(['id' => $newId], $validated);
            session(['mock_data_latih' => $mock]);
        });

        return redirect()->route('data-latih')->with('success', 'Data latih berhasil ditambahkan. Model C4.5 perlu di-training ulang.');
    }

    public function destroy($id)
    {
        $this->safeDb(function() use ($id) {
            DB::table('data_latih')->where('id', $id)->delete();
            // Nonaktifkan model karena data berubah
            DB::table('c45_model')->where('status', 'aktif')->update(['status' => 'nonaktif']);
        }, function() use ($id) {
            $mock = session('mock_data_latih', []);
            $mock = array_values(array_filter($mock, fn($item) => ($item['id'] ?? 0) != $id));
            session(['mock_data_latih' => $mock]);
        });

        return redirect()->route('data-latih')->with('success', 'Data latih berhasil dihapus. Model C4.5 perlu di-training ulang.');
    }
}
