<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class QgisApiService
 * 
 * Bertindak sebagai Application Service Layer yang menjembatani sistem Laravel
 * dengan QGIS Server eksternal melalui protokol HTTP REST / OGC WFS API.
 */
class QgisApiService
{
    /**
     * URL Endpoint QGIS Server CGI / FastCGI
     */
    protected string $qgisServerUrl;

    public function __construct()
    {
        // Ambil URL server QGIS dari konfigurasi .env atau default FastCGI local
        $this->qgisServerUrl = config('services.qgis.url', 'http://localhost/cgi-bin/qgis_mapserv.fcgi');
    }

    /**
     * Mengirimkan data spasial & non-spasial (Hit API) ke QGIS Server
     * dan menerima balasan format OGC GeoJSON Feature.
     * 
     * @param float $latitude Koordinat Lintang
     * @param float $longitude Koordinat Bujur
     * @param string $statusC45 Hasil klasifikasi C4.5 ("layak" / "tidak_layak")
     * @param array $attributes Atribut non-spasial tambahan (nama, jenis, kepadatan, dll)
     * @return array Structure GeoJSON Feature
     */
    public function sendLocationToQgis(float $latitude, float $longitude, string $statusC45, array $attributes = []): array
    {
        try {
            // 1. Eksekusi HTTP Request POST / GET ke QGIS Server Endpoint
            $response = Http::timeout(5)->post($this->qgisServerUrl, [
                'MAP' => storage_path('qgis/tps_cianjur.qgs'), // Path file project QGIS (.qgs/.qgz)
                'SERVICE' => 'WFS',                            // OGC Web Feature Service Protocol
                'VERSION' => '1.1.0',
                'REQUEST' => 'GetFeature',
                'OUTPUTFORMAT' => 'GeoJSON',                    // Format balasan spesifik GeoJSON
                'lat' => $latitude,
                'lng' => $longitude,
                'status_c45' => $statusC45,
                'jenis_fasilitas' => $attributes['jenis_fasilitas'] ?? 'TPS 3R',
            ]);

            // 2. Jika QGIS Server merespons sukses HTTP 200 OK
            if ($response->successful() && $response->json()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Log error jika koneksi QGIS Server eksternal bermasalah
            Log::warning('QGIS Server HTTP API Request Timeout/Error: ' . $e->getMessage());
        }

        // 3. Fallback Response standar GeoJSON OGC jika QGIS Server eksternal luring (Offline Standalone Mode)
        return $this->formatStandardGeoJsonFeature($latitude, $longitude, $statusC45, $attributes);
    }

    /**
     * Memformat data ke dalam standar OGC GeoJSON FeatureCollection (CRS84 / EPSG:4326)
     */
    public function formatStandardGeoJsonFeature(float $latitude, float $longitude, string $statusC45, array $attributes = []): array
    {
        $jenisFasilitas = $attributes['jenis_fasilitas'] ?? 'TPS 3R';

        // Tentukan warna marker spasial berdasarkan jenis fasilitas dan status C4.5
        $markerColor = $statusC45 === 'layak' ? '#16a34a' : '#dc2626'; // Hijau jika Layak, Merah jika Tidak Layak
        $facilityColor = match ($jenisFasilitas) {
            'Biodigester' => '#0284c7', // Biru untuk Biodigester
            'Bank Sampah' => '#d97706', // Oranye untuk Bank Sampah
            default => '#16a34a',       // Hijau untuk TPS 3R
        };

        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$longitude, $latitude], // Format standar OGC GeoJSON: [Lng, Lat]
            ],
            'properties' => array_merge([
                'id' => $attributes['id'] ?? uniqid('tps_'),
                'name' => $attributes['nama'] ?? 'Usulan TPS Baru',
                'kecamatan' => $attributes['kecamatan'] ?? 'Kabupaten Cianjur',
                'jenis_fasilitas' => $jenisFasilitas,
                'status' => $statusC45,
                'kepadatan' => $attributes['kepadatan'] ?? 'Sedang',
                'jarak_permukiman' => $attributes['jarak_permukiman'] ?? 'Sedang',
                'jarak_air' => $attributes['jarak_air'] ?? 'Sedang',
                'rule' => $attributes['rule'] ?? 'Aturan C4.5',
                'confidence' => $attributes['confidence'] ?? '90%',
                'marker_color' => $markerColor,
                'facility_color' => $facilityColor,
            ], $attributes),
        ];
    }
}
