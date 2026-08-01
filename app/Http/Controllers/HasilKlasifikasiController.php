<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\C45Service;

class HasilKlasifikasiController extends Controller
{
    public function index(C45Service $c45Service)
    {
        $locations = $this->safeDb(function() {
            $usulan = DB::table('usulan_lokasi')->get()->map(function($loc) {
                return [
                    'id' => 'usulan-' . $loc->id,
                    'name' => $loc->nama,
                    'status' => $loc->status,
                    'kecamatan' => $loc->kecamatan,
                    'jenis_fasilitas' => $loc->jenis_fasilitas ?? 'TPS 3R',
                    'lat' => (float)$loc->latitude,
                    'lng' => (float)$loc->longitude,
                    'kepadatan' => $loc->kepadatan,
                    'jarak_permukiman' => $loc->jarak_permukiman,
                    'jarak_air' => $loc->jarak_air,
                    'rule' => $loc->rule,
                    'rule_id' => $loc->rule_id ?? null,
                    'rule_detail' => $loc->rule_detail ?? $loc->rule,
                    'confidence' => $loc->confidence,
                    'predicted_at' => $loc->predicted_at ?? $loc->created_at,
                    'source' => 'usulan',
                ];
            })->toArray();

            $latih = DB::table('data_latih')->get()->map(function($loc) {
                return [
                    'id' => 'latih-' . $loc->id,
                    'name' => $loc->nama_fasilitas,
                    'status' => $loc->status,
                    'kecamatan' => $loc->alamat_desa,
                    'jenis_fasilitas' => $loc->jenis_fasilitas ?? 'TPS 3R',
                    'lat' => (float)($loc->latitude ?? -6.8900),
                    'lng' => (float)($loc->longitude ?? 107.2400),
                    'kepadatan' => $loc->kepadatan,
                    'jarak_permukiman' => $loc->jarak_permukiman,
                    'jarak_air' => $loc->jarak_air,
                    'rule' => 'Data Historis DLH → ' . strtoupper($loc->status),
                    'rule_id' => null,
                    'rule_detail' => 'Data Historis DLH (Ground Truth)',
                    'confidence' => '100%',
                    'predicted_at' => null,
                    'source' => 'historis',
                ];
            })->toArray();

            return array_merge($usulan, $latih);
        }, function() use ($c45Service) {
            $mockUsulan = session('mock_usulan_lokasi', []);
            $usulan = array_map(fn($loc) => [
                'id' => 'usulan-' . ($loc['id'] ?? rand(100, 999)),
                'name' => $loc['nama'] ?? 'Usulan TPS',
                'status' => $loc['status'] ?? 'layak',
                'kecamatan' => $loc['kecamatan'] ?? 'Cianjur',
                'jenis_fasilitas' => $loc['jenis_fasilitas'] ?? 'TPS 3R',
                'lat' => (float)($loc['latitude'] ?? 0),
                'lng' => (float)($loc['longitude'] ?? 0),
                'kepadatan' => $loc['kepadatan'] ?? 'Sedang',
                'jarak_permukiman' => $loc['jarak_permukiman'] ?? 'Sedang',
                'jarak_air' => $loc['jarak_air'] ?? 'Sedang',
                'rule' => $loc['rule'] ?? '',
                'rule_id' => $loc['rule_id'] ?? null,
                'rule_detail' => $loc['rule_detail'] ?? ($loc['rule'] ?? ''),
                'confidence' => $loc['confidence'] ?? '90.0%',
                'predicted_at' => $loc['predicted_at'] ?? ($loc['created_at'] ?? null),
                'source' => 'usulan',
            ], $mockUsulan);

            $dataset = $c45Service->getTrainingDataset();
            $latih = array_map(function($item) {
                return [
                    'id' => 'latih-' . $item['id'],
                    'name' => $item['nama_fasilitas'],
                    'status' => $item['status'],
                    'kecamatan' => $item['alamat_desa'],
                    'jenis_fasilitas' => $item['jenis_fasilitas'] ?? 'TPS 3R',
                    'lat' => (float)($item['latitude'] ?? -6.8900),
                    'lng' => (float)($item['longitude'] ?? 107.2400),
                    'kepadatan' => $item['kepadatan'],
                    'jarak_permukiman' => $item['jarak_permukiman'],
                    'jarak_air' => $item['jarak_air'],
                    'rule' => 'Data Historis DLH → ' . strtoupper($item['status']),
                    'rule_id' => null,
                    'rule_detail' => 'Data Historis DLH (Ground Truth)',
                    'confidence' => '100%',
                    'predicted_at' => null,
                    'source' => 'historis',
                ];
            }, $dataset);

            return array_merge($usulan, $latih);
        });

        return view('hasil-klasifikasi.index', compact('locations'));
    }
}
