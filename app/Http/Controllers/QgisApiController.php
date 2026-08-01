<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\C45Service;
use Illuminate\Support\Facades\DB;

class QgisApiController extends Controller
{
    /**
     * QGIS Server API (Spatial Integration) Endpoint
     * Method: GET / POST /api/qgis/klasifikasi-geojson
     * Input: lat (Float), lng (Float), status_c45 (String)
     * Output: GeoJSON FeatureCollection object
     */
    public function getKlasifikasiGeoJson(Request $request, C45Service $c45Service)
    {
        $locations = $this->safeDb(function() {
            $usulan = DB::table('usulan_lokasi')->get()->map(function($loc) {
                return [
                    'id' => 'usulan-' . $loc->id,
                    'name' => $loc->nama,
                    'kecamatan' => $loc->kecamatan,
                    'jenis_fasilitas' => $loc->jenis_fasilitas ?? 'TPS 3R',
                    'lat' => (float)$loc->latitude,
                    'lng' => (float)$loc->longitude,
                    'status' => $loc->status,
                    'kepadatan' => $loc->kepadatan,
                    'jarak_permukiman' => $loc->jarak_permukiman,
                    'jarak_air' => $loc->jarak_air,
                    'rule' => $loc->rule,
                    'confidence' => $loc->confidence,
                ];
            })->toArray();

            $latih = DB::table('data_latih')->get()->map(function($loc) {
                return [
                    'id' => 'latih-' . $loc->id,
                    'name' => $loc->nama_fasilitas,
                    'kecamatan' => $loc->alamat_desa,
                    'jenis_fasilitas' => $loc->jenis_fasilitas ?? 'TPS 3R',
                    'lat' => (float)($loc->latitude ?? -6.8900),
                    'lng' => (float)($loc->longitude ?? 107.2400),
                    'status' => $loc->status,
                    'kepadatan' => $loc->kepadatan,
                    'jarak_permukiman' => $loc->jarak_permukiman,
                    'jarak_air' => $loc->jarak_air,
                    'rule' => 'Histori Dataset C4.5 → ' . strtoupper($loc->status),
                    'confidence' => '100%',
                ];
            })->toArray();

            return array_merge($usulan, $latih);
        }, function() use ($c45Service) {
            $mockUsulan = session('mock_usulan_lokasi', []);
            $usulan = array_map(function($loc) {
                return [
                    'id' => 'usulan-' . ($loc['id'] ?? rand(100, 999)),
                    'name' => $loc['nama'] ?? 'Usulan TPS',
                    'kecamatan' => $loc['kecamatan'] ?? 'Cianjur',
                    'jenis_fasilitas' => $loc['jenis_fasilitas'] ?? 'TPS 3R',
                    'lat' => (float)($loc['latitude'] ?? 0),
                    'lng' => (float)($loc['longitude'] ?? 0),
                    'status' => $loc['status'] ?? 'layak',
                    'kepadatan' => $loc['kepadatan'] ?? 'Sedang',
                    'jarak_permukiman' => $loc['jarak_permukiman'] ?? 'Sedang',
                    'jarak_air' => $loc['jarak_air'] ?? 'Sedang',
                    'rule' => $loc['rule'] ?? 'Klasifikasi C4.5',
                    'confidence' => $loc['confidence'] ?? '90%',
                ];
            }, $mockUsulan);

            $dataset = $c45Service->getTrainingDataset();
            $latih = array_map(function($row) {
                return [
                    'id' => 'latih-' . $row['id'],
                    'name' => $row['nama_fasilitas'],
                    'kecamatan' => $row['alamat_desa'],
                    'jenis_fasilitas' => $row['jenis_fasilitas'] ?? 'TPS 3R',
                    'lat' => (float)($row['latitude'] ?? -6.8900),
                    'lng' => (float)($row['longitude'] ?? 107.2400),
                    'status' => $row['status'],
                    'kepadatan' => $row['kepadatan'],
                    'jarak_permukiman' => $row['jarak_permukiman'],
                    'jarak_air' => $row['jarak_air'],
                    'rule' => 'Histori DLH → ' . strtoupper($row['status']),
                    'confidence' => '100%',
                ];
            }, $dataset);

            return array_merge($usulan, $latih);
        });

        // Convert locations array to GeoJSON FeatureCollection
        $features = array_map(function($loc) {
            $facilityColor = match($loc['jenis_fasilitas']) {
                'Biodigester' => '#0284c7', // Cyan / Blue
                'Bank Sampah' => '#d97706', // Amber / Orange
                default => '#16a34a',      // Green (TPS 3R)
            };

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$loc['lng'], $loc['lat']],
                ],
                'properties' => [
                    'id' => $loc['id'],
                    'name' => $loc['name'],
                    'kecamatan' => $loc['kecamatan'],
                    'jenis_fasilitas' => $loc['jenis_fasilitas'],
                    'status' => $loc['status'],
                    'kepadatan' => $loc['kepadatan'],
                    'jarak_permukiman' => $loc['jarak_permukiman'],
                    'jarak_air' => $loc['jarak_air'],
                    'rule' => $loc['rule'],
                    'confidence' => $loc['confidence'],
                    'marker_color' => $loc['status'] === 'layak' ? '#2E7D32' : '#C62828',
                    'facility_color' => $facilityColor,
                ],
            ];
        }, $locations);

        $geoJson = [
            'type' => 'FeatureCollection',
            'crs' => [
                'type' => 'name',
                'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84']
            ],
            'features' => $features,
        ];

        return response()->json($geoJson)->header('Content-Type', 'application/geo+json');
    }
}
