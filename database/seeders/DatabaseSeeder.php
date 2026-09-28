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
        $usersToSeed = [
            [
                'name' => 'Admin Dinas DLH',
                'email' => 'admin@cianjurkab.go.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Administrator DLH',
                'email' => 'admin@dlh.cianjurkab.go.id',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Petugas Lapangan DLH',
                'email' => 'petugas@cianjurkab.go.id',
                'password' => Hash::make('petugas123'),
                'role' => 'petugas',
            ],
            [
                'name' => 'Petugas DLH Cianjur',
                'email' => 'petugas@dlh.cianjurkab.go.id',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
            ],
        ];

        foreach ($usersToSeed as $userData) {
            $user = DB::table('users')->where('email', $userData['email'])->first();
            if (!$user) {
                DB::table('users')->insert([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'role' => $userData['role'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('users')->where('email', $userData['email'])->update([
                    'role' => $userData['role'],
                    'password' => $userData['password'],
                ]);
            }
        }

        // Seed 21 Data Latih Historis
        $this->call(DataLatihSeeder::class);
    }
}
