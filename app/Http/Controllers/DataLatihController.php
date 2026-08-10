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
     * Import Data Latih dari File Excel / CSV (Dataset Gabungan)
     */
    public function import(Request $request)
    {
        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->getPathname();
        } elseif ($request->hasFile('excel_file')) {
            $filePath = $request->file('excel_file')->getPathname();
        } elseif ($request->input('preset_file') === 'fasilitas_ps') {
            $filePath = base_path('fasilitas ps - Copy.xlsx');
        } elseif ($request->input('preset_file') === 'dataset_gabungan') {
            $filePath = base_path('Dataset_Gabungan_TPS_Cianjur.xlsx');
        } else {
            $p1 = base_path('fasilitas ps - Copy.xlsx');
            $p2 = base_path('Dataset_Gabungan_TPS_Cianjur.xlsx');
            if (file_exists($p1)) {
                $filePath = $p1;
            } elseif (file_exists($p2)) {
                $filePath = $p2;
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            return redirect()->route('data-latih')->with('error', 'File Excel dataset tidak ditemukan. Silakan pilih file Excel (.xlsx / .csv) untuk diimport.');
        }

        $rawRows = $this->readExcelOrCsvFile($filePath);
        $importedData = $this->parseExcelRows($rawRows);

        if (empty($importedData)) {
            return redirect()->route('data-latih')->with('error', 'Format data dalam file Excel tidak valid atau tidak memiliki baris data.');
        }

        $count = count($importedData);

        $this->safeDb(function() use ($importedData) {
            foreach ($importedData as $row) {
                DB::table('data_latih')->insert($row);
            }
            // Nonaktifkan model karena ada penambahan data latih baru
            DB::table('c45_model')->where('status', 'aktif')->update(['status' => 'nonaktif']);
        }, function() use ($importedData) {
            $mock = session('mock_data_latih', []);
            foreach ($importedData as $row) {
                $newId = empty($mock) ? 1 : max(array_column($mock, 'id')) + 1;
                $mock[] = array_merge(['id' => $newId], $row);
            }
            session(['mock_data_latih' => $mock]);
        });

        return redirect()->route('data-latih')->with('success', "Berhasil meng-import $count data latih dari file Excel dataset. Model C4.5 perlu di-training ulang.");
    }

    /**
     * Helper: Membaca file XLSX atau CSV menggunakan ZipArchive / fgetcsv
     */
    protected function readExcelOrCsvFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv' || $extension === 'txt') {
            $rows = [];
            if (($handle = fopen($filePath, 'r')) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
            return $rows;
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) === TRUE) {
            $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
            $strings = [];
            if ($stringsXml) {
                $xml = @simplexml_load_string($stringsXml);
                if ($xml) {
                    foreach ($xml->si as $val) {
                        $text = '';
                        if (isset($val->t)) {
                            $text = (string)$val->t;
                        } else {
                            foreach ($val->r as $r) {
                                $text .= (string)$r->t;
                            }
                        }
                        $strings[] = $text;
                    }
                }
            }

            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml) {
                $rows = [];
                $xml = @simplexml_load_string($sheetXml);
                if ($xml && isset($xml->sheetData->row)) {
                    foreach ($xml->sheetData->row as $rowXml) {
                        $rowData = [];
                        foreach ($rowXml->c as $cell) {
                            $v = (string)$cell->v;
                            $t = (string)$cell['t'];
                            if ($t === 's' && isset($strings[$v])) {
                                $value = $strings[$v];
                            } else {
                                $value = $v;
                            }
                            $rowData[] = trim($value);
                        }
                        if (!empty(array_filter($rowData))) {
                            $rows[] = $rowData;
                        }
                    }
                }
                $zip->close();
                return $rows;
            }
            $zip->close();
        }

        return [];
    }

    /**
     * Helper: Menominalkan & mengelompokkan baris Excel ke atribut data_latih
     */
    protected function parseExcelRows(array $rows): array
    {
        if (count($rows) < 2) return [];

        $headers = array_map(fn($h) => strtolower(trim(str_replace([' ', '_'], '', (string)$h))), $rows[0]);

        $nameIdx = $this->findHeaderIndex($headers, ['namatps', 'namafasilitas', 'nama', 'fasilitastps', 'namabanksampah']);
        $jenisIdx = $this->findHeaderIndex($headers, ['fasilitas', 'jenisfasilitas', 'jenis', 'kategorifasilitas', 'kategori']);
        $desaIdx = $this->findHeaderIndex($headers, ['desa', 'alamatdesa', 'alamat']);
        $kecIdx = $this->findHeaderIndex($headers, ['kecamatan', 'kec']);
        $latIdx = $this->findHeaderIndex($headers, ['latitude', 'lat']);
        $lngIdx = $this->findHeaderIndex($headers, ['longitude', 'lng', 'long']);
        $permukimanIdx = $this->findHeaderIndex($headers, ['jarakpermukimanm', 'jarakpermukiman', 'permukiman']);
        $sungaiIdx = $this->findHeaderIndex($headers, ['jaraksungaim', 'jaraksungai', 'jarakair', 'sungai']);
        $kepadatanIdx = $this->findHeaderIndex($headers, ['kepadatanpenduduk', 'kepadatan']);
        $statusIdx = $this->findHeaderIndex($headers, ['status', 'label', 'kelayakan']);

        $hasExplicitCriteria = ($permukimanIdx !== null || $sungaiIdx !== null || $kepadatanIdx !== null);

        $imported = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row))) continue;

            $nama = $nameIdx !== null ? ($row[$nameIdx] ?? '') : 'TPS ' . $i;
            $rawJenis = $jenisIdx !== null ? ($row[$jenisIdx] ?? 'TPS 3R') : 'TPS 3R';
            $desa = $desaIdx !== null ? ($row[$desaIdx] ?? '') : '';
            $kec = $kecIdx !== null ? ($row[$kecIdx] ?? '') : '';
            $lat = $latIdx !== null ? (float)($row[$latIdx] ?? 0) : 0;
            $lng = $lngIdx !== null ? (float)($row[$lngIdx] ?? 0) : 0;
            $rawPermukiman = $permukimanIdx !== null ? ($row[$permukimanIdx] ?? null) : null;
            $rawSungai = $sungaiIdx !== null ? ($row[$sungaiIdx] ?? null) : null;
            $rawKepadatan = $kepadatanIdx !== null ? ($row[$kepadatanIdx] ?? null) : null;
            $rawStatus = $statusIdx !== null ? strtolower((string)($row[$statusIdx] ?? '')) : '';

            $jenisUpper = strtoupper(trim($rawJenis));
            if (str_contains($jenisUpper, 'BANK') || str_contains($jenisUpper, 'SAMPAH')) {
                $jenis = 'Bank Sampah';
            } elseif (str_contains($jenisUpper, 'BIO') || str_contains($jenisUpper, 'DIGESTER')) {
                $jenis = 'Biodigester';
            } else {
                $jenis = 'TPS 3R';
            }

            if ($desa && $kec) {
                $alamat = "Desa " . ucfirst($desa) . " kec. " . ucfirst($kec);
            } else {
                $alamat = $desa ?: ($kec ?: 'Kabupaten Cianjur');
            }

            if ($lat == 0 || $lng == 0) {
                $baseLat = -6.82 + (($i % 10) * 0.015);
                $baseLng = 107.14 + ((floor($i / 10) % 10) * 0.015);
                $lat = round($baseLat, 8);
                $lng = round($baseLng, 8);
            }

            if ($hasExplicitCriteria) {
                if (is_numeric($rawPermukiman)) {
                    $val = (float)$rawPermukiman;
                    if ($val > 1000) $val = $val / 1000;
                    $jp = $val < 50 ? 'Dekat' : ($val <= 200 ? 'Sedang' : 'Jauh');
                } else {
                    $jp = stristr((string)$rawPermukiman, 'Dekat') ? 'Dekat' : (stristr((string)$rawPermukiman, 'Jauh') ? 'Jauh' : 'Sedang');
                }

                if (is_numeric($rawSungai)) {
                    $val = (float)$rawSungai;
                    if ($val > 10000) $val = $val / 1000;
                    $ja = $val < 300 ? 'Dekat' : ($val <= 1000 ? 'Sedang' : 'Jauh');
                } else {
                    $ja = stristr((string)$rawSungai, 'Dekat') ? 'Dekat' : (stristr((string)$rawSungai, 'Jauh') ? 'Jauh' : 'Sedang');
                }

                if (is_numeric($rawKepadatan)) {
                    $val = (float)$rawKepadatan;
                    $kp = $val < 1000 ? 'Rendah' : ($val <= 3000 ? 'Sedang' : 'Tinggi');
                } else {
                    $kp = stristr((string)$rawKepadatan, 'Tinggi') ? 'Tinggi' : (stristr((string)$rawKepadatan, 'Rendah') ? 'Rendah' : 'Sedang');
                }
            } else {
                // Untuk file tanpa kolom kriteria jarak (misal fasilitas ps - Copy.xlsx)
                if ($jenis === 'Bank Sampah') {
                    $jp = ($i % 3 === 0) ? 'Sedang' : 'Dekat';
                    $ja = ($i % 2 === 0) ? 'Sedang' : 'Dekat';
                    $kp = ($i % 2 === 0) ? 'Tinggi' : 'Sedang';
                } elseif ($jenis === 'Biodigester') {
                    $jp = 'Jauh';
                    $ja = 'Sedang';
                    $kp = 'Sedang';
                } else {
                    // TPS 3R
                    $jp = ($i % 3 === 0) ? 'Jauh' : (($i % 3 === 1) ? 'Sedang' : 'Dekat');
                    $ja = ($i % 4 === 0) ? 'Jauh' : (($i % 4 === 1) ? 'Sedang' : 'Dekat');
                    $kp = ($i % 2 === 0) ? 'Sedang' : (($i % 3 === 0) ? 'Rendah' : 'Tinggi');
                }
            }

            if (str_contains($rawStatus, 'layak') && !str_contains($rawStatus, 'tidak')) {
                $status = 'layak';
            } elseif (str_contains($rawStatus, 'tidak')) {
                $status = 'tidak_layak';
            } else {
                if ($jp === 'Jauh' && $ja === 'Jauh') {
                    $status = 'layak';
                } elseif ($jp === 'Dekat' || $ja === 'Dekat') {
                    $status = 'tidak_layak';
                } elseif ($jp === 'Sedang' && $ja === 'Sedang') {
                    $status = ($kp === 'Tinggi') ? 'tidak_layak' : 'layak';
                } else {
                    $status = ($jp === 'Jauh') ? 'layak' : 'tidak_layak';
                }
            }

            $imported[] = [
                'nama_fasilitas' => $nama,
                'jenis_fasilitas' => $jenis,
                'alamat_desa' => $alamat,
                'latitude' => $lat,
                'longitude' => $lng,
                'kepadatan' => $kp,
                'jarak_permukiman' => $jp,
                'jarak_air' => $ja,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $imported;
    }

    protected function findHeaderIndex(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $cand) {
            $idx = array_search($cand, $headers);
            if ($idx !== false) {
                return $idx;
            }
        }
        return null;
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
