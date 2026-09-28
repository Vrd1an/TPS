<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class RoleAndClassificationWorkflowTest extends TestCase
{
    /**
     * Test Login as Admin and Petugas
     */
    public function test_login_authenticates_admin_and_petugas(): void
    {
        // Admin Login
        $responseAdmin = $this->post('/login', [
            'email' => 'admin@cianjurkab.go.id',
            'password' => 'admin123',
        ]);
        $responseAdmin->assertRedirect('/dashboard');
        $responseAdmin->assertSessionHas('user_role', 'admin');

        // Petugas Login
        $responsePetugas = $this->post('/login', [
            'email' => 'petugas@cianjurkab.go.id',
            'password' => 'petugas123',
        ]);
        $responsePetugas->assertRedirect('/dashboard');
        $responsePetugas->assertSessionHas('user_role', 'petugas');
    }

    /**
     * Test Petugas cannot access Admin-only routes
     */
    public function test_petugas_cannot_access_admin_routes(): void
    {
        $session = [
            'logged_in' => true,
            'user_id' => 2,
            'user_name' => 'Petugas Lapangan DLH',
            'user_email' => 'petugas@cianjurkab.go.id',
            'user_role' => 'petugas',
        ];

        // Should redirect away from admin routes
        $this->withSession($session)->get('/data-latih')->assertRedirect('/dashboard');
        $this->withSession($session)->get('/decision-tree')->assertRedirect('/dashboard');
        $this->withSession($session)->get('/confusion-matrix')->assertRedirect('/dashboard');
        $this->withSession($session)->get('/kelola-pengguna')->assertRedirect('/dashboard');

        // Petugas cannot trigger classification endpoint
        $this->withSession($session)->post('/usulan-lokasi/1/klasifikasi')->assertRedirect('/dashboard');
    }

    /**
     * Test New Flow: Petugas inputs proposal -> status 'menunggu_klasifikasi' (NO C4.5)
     */
    public function test_petugas_submits_proposal_with_pending_status(): void
    {
        $session = [
            'logged_in' => true,
            'user_id' => 2,
            'user_name' => 'Petugas Lapangan DLH',
            'user_email' => 'petugas@cianjurkab.go.id',
            'user_role' => 'petugas',
        ];

        $payload = [
            'nama_lokasi' => 'TPS Uji Lapangan Petugas',
            'kecamatan' => 'Cianjur',
            'jenis_fasilitas' => 'TPS 3R',
            'latitude' => -6.8150,
            'longitude' => 107.1380,
            'kepadatan' => 'Tinggi',
            'jarak_permukiman' => 'Sedang (200-500m)',
            'jarak_air' => 'Jauh (> 300m)',
        ];

        $response = $this->withSession($session)->postJson('/usulan-lokasi', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'status_data' => 'menunggu_klasifikasi'
            ]
        ]);

        $this->assertDatabaseHas('usulan_lokasi', [
            'nama' => 'TPS Uji Lapangan Petugas',
            'status' => 'menunggu_klasifikasi',
        ]);
    }

    /**
     * Test Admin classifies the pending proposal with C4.5
     */
    public function test_admin_classifies_proposal_with_c45(): void
    {
        $id = DB::table('usulan_lokasi')->insertGetId([
            'nama' => 'TPS Uji Klasifikasi Admin',
            'kecamatan' => 'Cianjur',
            'jenis_fasilitas' => 'TPS 3R',
            'latitude' => -6.8150,
            'longitude' => 107.1380,
            'kepadatan' => 'Tinggi',
            'jarak_permukiman' => 'Sedang (200-500m)',
            'jarak_air' => 'Jauh (> 300m)',
            'status' => 'menunggu_klasifikasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminSession = [
            'logged_in' => true,
            'user_id' => 1,
            'user_name' => 'Admin Dinas DLH',
            'user_email' => 'admin@cianjurkab.go.id',
            'user_role' => 'admin',
        ];

        $response = $this->withSession($adminSession)->postJson("/usulan-lokasi/{$id}/klasifikasi");

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'status' => 'layak'
            ]
        ]);

        $this->assertDatabaseHas('usulan_lokasi', [
            'id' => $id,
            'status' => 'layak',
        ]);
    }
}
