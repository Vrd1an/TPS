<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataLatihSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 21 Data Latih Historis TPS Kabupaten Cianjur (Tabel 3.8 & 3.9 Laporan)
     */
    public function run(): void
    {
        DB::table('data_latih')->truncate();

        $dataset = [
            ['nama_fasilitas' => 'TPS 3R (KSM Bersemi)', 'alamat_desa' => 'Desa Jatisari kec. Bojongpicung', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.876823, 'longitude' => 107.241259, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Jauh', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R Babakan karet', 'alamat_desa' => 'Desa Babakan karet kec. Cianjur', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.793863, 'longitude' => 107.135953, 'kepadatan' => 'Sedang', 'jarak_permukiman' => 'Dekat', 'jarak_air' => 'Sedang', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'TPS 3R (KSM Maslahat)', 'alamat_desa' => 'Desa Limbangansari kec. cianjur', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.819964, 'longitude' => 107.117685, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Sedang', 'jarak_air' => 'Jauh', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R Gelarpawitan', 'alamat_desa' => 'Desa Gelarpawitan Kec.Cidaun', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -7.392394, 'longitude' => 107.434919, 'kepadatan' => 'Rendah', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Dekat', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'TPS 3R Gelarwangi', 'alamat_desa' => 'Desa Gelarwangi Kec.Cidaun', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -7.278968, 'longitude' => 107.469006, 'kepadatan' => 'Sedang', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Sedang', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R Mentengsari', 'alamat_desa' => 'Desa Mentengsari Kec.Cikalongkulon', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.861217, 'longitude' => 107.121430, 'kepadatan' => 'Rendah', 'jarak_permukiman' => 'Dekat', 'jarak_air' => 'Jauh', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'TPS 3R (KSM Cemerlang)', 'alamat_desa' => 'Desa Sinargalih kec. Cilaku', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.861174, 'longitude' => 107.121603, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Sedang', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R Kertajaya', 'alamat_desa' => 'Desa Kertajaya Kec.Ciranjang', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.787078, 'longitude' => 107.279518, 'kepadatan' => 'Sedang', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Jauh', 'status' => 'layak'],
            ['nama_fasilitas' => 'BANK SAMPAH BARAYA JATI', 'alamat_desa' => 'Desa Gekbrong, Kecamatan Gekbrong', 'jenis_fasilitas' => 'Bank Sampah', 'latitude' => -6.867130, 'longitude' => 107.037697, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Dekat', 'jarak_air' => 'Dekat', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'TPS 3R Mekarwangi', 'alamat_desa' => 'Desa Mekarwangi kec. Haurwangi', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.800025, 'longitude' => 107.348010, 'kepadatan' => 'Sedang', 'jarak_permukiman' => 'Sedang', 'jarak_air' => 'Sedang', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R (KSM Sangkan Hurip)', 'alamat_desa' => 'Desa Kertamukti kec. Haurwangi', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.797450, 'longitude' => 107.304788, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Jauh', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R (KSM Nurul Ikhlas)', 'alamat_desa' => 'Desa Cipeuyem kec. Haurwangi', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.798927, 'longitude' => 107.296881, 'kepadatan' => 'Rendah', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Dekat', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'TPS 3R (KSM Badak Cihea)', 'alamat_desa' => 'Desa Cihea kec. Haurwangi', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.863969, 'longitude' => 107.315181, 'kepadatan' => 'Sedang', 'jarak_permukiman' => 'Dekat', 'jarak_air' => 'Sedang', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'Bank Sampah Induk Kadupandak', 'alamat_desa' => 'Desa Kadupandak kec.Kadupandak', 'jenis_fasilitas' => 'Bank Sampah', 'latitude' => -7.261283, 'longitude' => 107.047538, 'kepadatan' => 'Rendah', 'jarak_permukiman' => 'Sedang', 'jarak_air' => 'Jauh', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R (Terang)', 'alamat_desa' => 'Desa Sukamanah kec. Karangtengah', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.832631, 'longitude' => 107.159562, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Sedang', 'jarak_air' => 'Jauh', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R (KSM Banyu Pangkalan)', 'alamat_desa' => 'Desa Mande kec. Mande', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.735810, 'longitude' => 107.242952, 'kepadatan' => 'Sedang', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Jauh', 'status' => 'layak'],
            ['nama_fasilitas' => 'Biodigester Organik Mande', 'alamat_desa' => 'Desa Mande kec. Mande', 'jenis_fasilitas' => 'Biodigester', 'latitude' => -6.732632, 'longitude' => 107.259580, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Dekat', 'jarak_air' => 'Jauh', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'Biodigester Cikidangbayabang', 'alamat_desa' => 'Desa Cikidangbayabang kec. Mande', 'jenis_fasilitas' => 'Biodigester', 'latitude' => -6.752959, 'longitude' => 107.266717, 'kepadatan' => 'Sedang', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Sedang', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R Sukanagalih', 'alamat_desa' => 'Desa Sukanagalih kec. Pacet', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.698060, 'longitude' => 107.053039, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Sedang', 'jarak_air' => 'Dekat', 'status' => 'tidak_layak'],
            ['nama_fasilitas' => 'TPS 3R (KSM Sari Mashur)', 'alamat_desa' => 'Desa Babakansari kec. Sukaluyu', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.828243, 'longitude' => 107.218135, 'kepadatan' => 'Rendah', 'jarak_permukiman' => 'Sedang', 'jarak_air' => 'Sedang', 'status' => 'layak'],
            ['nama_fasilitas' => 'TPS 3R Ciwalen', 'alamat_desa' => 'Desa Ciwalen Kec.Warungkondang', 'jenis_fasilitas' => 'TPS 3R', 'latitude' => -6.855691, 'longitude' => 107.112572, 'kepadatan' => 'Tinggi', 'jarak_permukiman' => 'Jauh', 'jarak_air' => 'Jauh', 'status' => 'layak'],
        ];

        foreach ($dataset as $data) {
            $data['created_at'] = now();
            $data['updated_at'] = now();
            DB::table('data_latih')->insert($data);
        }
    }
}
