<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class KecamatanSeeder
 * 
 * Mengisikan data agregat BPS 32 Kecamatan di Kabupaten Cianjur
 * beserta nilai kepadatan penduduk per Km2 dan diskretisasi C4.5.
 */
class KecamatanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kecamatan')->truncate();

        // Data 32 Kecamatan Kabupaten Cianjur (Sumber Data Agregat BPS)
        $dataKecamatan = [
            ['nama_kecamatan' => 'Agrabinta', 'jumlah_penduduk' => 40120, 'luas_wilayah_km2' => 192.50],
            ['nama_kecamatan' => 'Bojongpicung', 'jumlah_penduduk' => 82450, 'luas_wilayah_km2' => 88.20],
            ['nama_kecamatan' => 'Campaka', 'jumlah_penduduk' => 59800, 'luas_wilayah_km2' => 143.10],
            ['nama_kecamatan' => 'Campaka Mulya', 'jumlah_penduduk' => 24100, 'luas_wilayah_km2' => 71.40],
            ['nama_kecamatan' => 'Cianjur', 'jumlah_penduduk' => 173200, 'luas_wilayah_km2' => 26.15], // Sangat Tinggi
            ['nama_kecamatan' => 'Cibeber', 'jumlah_penduduk' => 93400, 'luas_wilayah_km2' => 124.60],
            ['nama_kecamatan' => 'Cibinong', 'jumlah_penduduk' => 64300, 'luas_wilayah_km2' => 236.40],
            ['nama_kecamatan' => 'Cidaun', 'jumlah_penduduk' => 69800, 'luas_wilayah_km2' => 295.10],
            ['nama_kecamatan' => 'Cijati', 'jumlah_penduduk' => 35600, 'luas_wilayah_km2' => 98.30],
            ['nama_kecamatan' => 'Cikadu', 'jumlah_penduduk' => 38900, 'luas_wilayah_km2' => 189.20],
            ['nama_kecamatan' => 'Cikalongkulon', 'jumlah_penduduk' => 108900, 'luas_wilayah_km2' => 142.80],
            ['nama_kecamatan' => 'Cilaku', 'jumlah_penduduk' => 119500, 'luas_wilayah_km2' => 54.30], // Tinggi
            ['nama_kecamatan' => 'Cipanas', 'jumlah_penduduk' => 112400, 'luas_wilayah_km2' => 67.20], // Tinggi
            ['nama_kecamatan' => 'Ciranjang', 'jumlah_penduduk' => 89600, 'luas_wilayah_km2' => 35.80], // Tinggi
            ['nama_kecamatan' => 'Cugenang', 'jumlah_penduduk' => 114800, 'luas_wilayah_km2' => 76.40], // Tinggi
            ['nama_kecamatan' => 'Gekbrong', 'jumlah_penduduk' => 58900, 'luas_wilayah_km2' => 50.80], // Sedang
            ['nama_kecamatan' => 'Haurwangi', 'jumlah_penduduk' => 61200, 'luas_wilayah_km2' => 46.20], // Sedang/Tinggi
            ['nama_kecamatan' => 'Kadupandak', 'jumlah_penduduk' => 55800, 'luas_wilayah_km2' => 102.50],
            ['nama_kecamatan' => 'Karangtengah', 'jumlah_penduduk' => 162400, 'luas_wilayah_km2' => 48.50], // Tinggi
            ['nama_kecamatan' => 'Leles', 'jumlah_penduduk' => 32400, 'luas_wilayah_km2' => 118.60],
            ['nama_kecamatan' => 'Mande', 'jumlah_penduduk' => 78900, 'luas_wilayah_km2' => 98.40],
            ['nama_kecamatan' => 'Naringgul', 'jumlah_penduduk' => 47200, 'luas_wilayah_km2' => 281.30], // Rendah
            ['nama_kecamatan' => 'Pacet', 'jumlah_penduduk' => 109800, 'luas_wilayah_km2' => 41.60], // Tinggi
            ['nama_kecamatan' => 'Pagelaran', 'jumlah_penduduk' => 74500, 'luas_wilayah_km2' => 128.90],
            ['nama_kecamatan' => 'Pasirkuda', 'jumlah_penduduk' => 38200, 'luas_wilayah_km2' => 112.40],
            ['nama_kecamatan' => 'Sindangbarang', 'jumlah_penduduk' => 57400, 'luas_wilayah_km2' => 171.20],
            ['nama_kecamatan' => 'Sukaluyu', 'jumlah_penduduk' => 88900, 'luas_wilayah_km2' => 47.90], // Tinggi
            ['nama_kecamatan' => 'Sukanagara', 'jumlah_penduduk' => 56800, 'luas_wilayah_km2' => 174.50],
            ['nama_kecamatan' => 'Sukaresmi', 'jumlah_penduduk' => 92100, 'luas_wilayah_km2' => 91.30], // Sedang
            ['nama_kecamatan' => 'Takokak', 'jumlah_penduduk' => 52300, 'luas_wilayah_km2' => 142.10],
            ['nama_kecamatan' => 'Tanggeung', 'jumlah_penduduk' => 49600, 'luas_wilayah_km2' => 115.80],
            ['nama_kecamatan' => 'Warungkondang', 'jumlah_penduduk' => 77800, 'luas_wilayah_km2' => 45.10], // Tinggi
        ];

        foreach ($dataKecamatan as $kec) {
            // Hitung Kepadatan Jiwa / Km2
            $kepadatanAngka = (int)round($kec['jumlah_penduduk'] / max(1, $kec['luas_wilayah_km2']));

            // Diskretisasi Kategori C4.5
            if ($kepadatanAngka < 500) {
                $kategori = 'Rendah';
            } elseif ($kepadatanAngka <= 1500) {
                $kategori = 'Sedang';
            } else {
                $kategori = 'Tinggi';
            }

            DB::table('kecamatan')->insert([
                'nama_kecamatan' => $kec['nama_kecamatan'],
                'jumlah_penduduk' => $kec['jumlah_penduduk'],
                'luas_wilayah_km2' => $kec['luas_wilayah_km2'],
                'kepadatan_angka' => $kepadatanAngka,
                'kepadatan_kategori' => $kategori,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
