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

        $controller = new \App\Http\Controllers\DataLatihController();
        $filePath = base_path('fasilitas ps - Copy.xlsx');

        if (file_exists($filePath)) {
            $reflection = new \ReflectionClass($controller);
            $readMethod = $reflection->getMethod('readExcelOrCsvFile');
            $readMethod->setAccessible(true);
            $parseMethod = $reflection->getMethod('parseExcelRows');
            $parseMethod->setAccessible(true);

            $rawRows = $readMethod->invoke($controller, $filePath);
            $parsedData = $parseMethod->invoke($controller, $rawRows);

            foreach ($parsedData as $data) {
                $data['created_at'] = now();
                $data['updated_at'] = now();
                DB::table('data_latih')->insert($data);
            }
        }
    }
}
