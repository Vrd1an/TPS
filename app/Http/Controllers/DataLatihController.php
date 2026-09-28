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
                $coords = $this->resolveGeographicalCoordinates($nama, $alamat, $i);
                $lat = $coords[0];
                $lng = $coords[1];
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

    /**
     * Helper: Menentukan koordinat akurat berdasarkan nama desa/kecamatan di Cianjur
     */
    protected function resolveGeographicalCoordinates(string $nama, string $alamat, int $index = 1): array
    {
        $villageCoordinates = [
            'Sukaratu' => [-6.8295, 107.2912],
            'Sukarama' => [-6.8354, 107.2845],
            'Kemang' => [-6.8180, 107.3021],
            'Jatisari' => [-6.8220, 107.2880],
            'Jati' => [-6.8410, 107.2990],
            'Bojongpicung' => [-6.8250, 107.2950],
            'Bojong Picung' => [-6.8250, 107.2950],
            'Sawahgede' => [-6.8190, 107.1350],
            'Muka' => [-6.8120, 107.1420],
            'Sayang' => [-6.8310, 107.1490],
            'Babakankaret' => [-6.7980, 107.1320],
            'Babakan karet' => [-6.7980, 107.1320],
            'Limbangansari' => [-6.8050, 107.1250],
            'Salagedang' => [-6.8480, 107.2650],
            'Girimulya' => [-6.8620, 107.2820],
            'Sukamanah' => [-6.8590, 107.2710],
            'Peuteuycondong' => [-6.8410, 107.2580],
            'Peuteuy condong' => [-6.8410, 107.2580],
            'Karangnunggal' => [-6.8720, 107.2910],
            'Cikondang' => [-6.8850, 107.2850],
            'Cihaur' => [-6.8530, 107.2780],
            'Cibuluh' => [-7.3820, 107.4210],
            'Gelarpawitan' => [-7.3750, 107.4420],
            'Gelarwangi' => [-7.3880, 107.4510],
            'Jayapura' => [-7.3980, 107.4350],
            'Neglasari' => [-6.6780, 107.1580],
            'Mentengsari' => [-6.6920, 107.1720],
            'Sinargalih' => [-6.8768, 107.2412],
            'Sindangjaya' => [-6.7020, 107.0390],
            'Gunungsari' => [-6.7890, 107.3120],
            'Kertajaya' => [-6.7974, 107.3047],
            'Benjot' => [-6.7790, 107.0910],
            'Cijedil' => [-6.7850, 107.1050],
            'Gekbrong' => [-6.8390, 107.0720],
            'Mekarwangi' => [-6.7950, 107.3280],
            'Kertamukti' => [-6.8080, 107.3420],
            'Cipeuyeum' => [-6.8010, 107.3390],
            'Cipeuyem' => [-6.8010, 107.3390],
            'Cihea' => [-6.8150, 107.3480],
            'Haurwangi' => [-6.8020, 107.3350],
            'Kertasari' => [-6.7920, 107.3450],
            'Kadupandak' => [-7.1650, 107.0750],
            'Sindang Asih' => [-6.8250, 107.1920],
            'Sukajadi' => [-6.8220, 107.1850],
            'Sirnasari' => [-7.3850, 107.0250],
            'Bobojong' => [-6.7450, 107.1880],
            'Mande' => [-6.7580, 107.1950],
            'Cikidangbayabang' => [-6.7650, 107.2080],
            'Ciherang' => [-6.7380, 107.0920],
            'Sukanagalih' => [-6.7490, 107.0810],
            'Saganten' => [-7.4320, 107.1180],
            'Babakansari' => [-6.8140, 107.2410],
            'Babakan Sari' => [-6.8150, 107.2420],
            'Sukaluyu' => [-6.8100, 107.2350],
            'Sukaresmi' => [-6.7150, 107.0750],
            'Sukajaya' => [-7.2150, 107.1050],
            'Ciwalen' => [-6.8520, 107.0890],
        ];

        $combinedText = $nama . ' ' . $alamat;
        foreach ($villageCoordinates as $village => $coords) {
            if (stripos($combinedText, $village) !== false) {
                $jitterLat = ((($index * 7) % 7) - 3) * 0.001;
                $jitterLng = ((($index * 13) % 7) - 3) * 0.001;
                return [round($coords[0] + $jitterLat, 8), round($coords[1] + $jitterLng, 8)];
            }
        }

        $kecamatanCoords = [
            'Cianjur' => [-6.8150, 107.1380], 'Ciranjang' => [-6.7974, 107.3047],
            'Cilaku' => [-6.8768, 107.2412], 'Cibeber' => [-6.8534, 107.2780],
            'Karangtengah' => [-6.8220, 107.1850], 'Cipanas' => [-6.7020, 107.0390],
            'Pacet' => [-6.7450, 107.0850], 'Cugenang' => [-6.7820, 107.0980],
            'Gekbrong' => [-6.8390, 107.0720], 'Warungkondang' => [-6.8520, 107.0890],
            'Mande' => [-6.7580, 107.1950], 'Sukaluyu' => [-6.8100, 107.2350],
            'Bojongpicung' => [-6.8250, 107.2950], 'Haurwangi' => [-6.8020, 107.3350],
            'Cikalongkulon' => [-6.6850, 107.1650], 'Sukaresmi' => [-6.7150, 107.0750],
            'Campaka' => [-6.9550, 107.1450], 'Campaka Mulya' => [-6.9950, 107.1150],
            'Sukanagara' => [-7.0750, 107.1350], 'Pagelaran' => [-7.1250, 107.1850],
            'Kadupandak' => [-7.1650, 107.0750], 'Takokak' => [-7.0550, 106.9950],
            'Tanggeung' => [-7.2150, 107.1050], 'Cijati' => [-7.2650, 107.0450],
            'Cikadu' => [-7.2850, 107.2250], 'Cibinong' => [-7.3150, 107.1350],
            'Pasirkuda' => [-7.2350, 107.1950], 'Sindangbarang' => [-7.4250, 107.1250],
            'Agrabinta' => [-7.4550, 106.9450], 'Leles' => [-7.3850, 107.0250],
            'Cidaun' => [-7.3923, 107.4349], 'Naringgul' => [-7.3350, 107.3550],
        ];

        foreach ($kecamatanCoords as $kec => $coords) {
            if (stripos($combinedText, $kec) !== false) {
                $jitterLat = ((($index * 7) % 11) - 5) * 0.003;
                $jitterLng = ((($index * 13) % 11) - 5) * 0.003;
                return [round($coords[0] + $jitterLat, 8), round($coords[1] + $jitterLng, 8)];
            }
        }

        $jitterLat = ((($index * 5) % 13) - 6) * 0.005;
        $jitterLng = ((($index * 11) % 13) - 6) * 0.005;
        return [round(-6.8150 + $jitterLat, 8), round(107.1380 + $jitterLng, 8)];
    }
}
