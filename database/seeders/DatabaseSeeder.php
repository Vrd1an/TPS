<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Administrator & Petugas DLH users
        if (!DB::table('users')->where('email', 'admin@dlh.cianjurkab.go.id')->exists()) {
            DB::table('users')->insert([
                'name' => 'Administrator DLH',
                'email' => 'admin@dlh.cianjurkab.go.id',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!DB::table('users')->where('email', 'petugas@dlh.cianjurkab.go.id')->exists()) {
            DB::table('users')->insert([
                'name' => 'Petugas Lapangan DLH',
                'email' => 'petugas@dlh.cianjurkab.go.id',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Seed 21 Data Latih Historis
        $this->call(DataLatihSeeder::class);
    }
}
