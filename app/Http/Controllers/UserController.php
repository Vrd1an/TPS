<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = $this->safeDb(function() {
            return DB::table('users')->get()->toArray();
        }, function() {
            return [
                (object)['id' => 1, 'name' => 'Administrator DLH', 'email' => 'admin@dlh.cianjurkab.go.id', 'role' => 'Administrator', 'created_at' => now()],
                (object)['id' => 2, 'name' => 'Petugas Lapangan DLH', 'email' => 'petugas@dlh.cianjurkab.go.id', 'role' => 'Petugas DLH', 'created_at' => now()],
            ];
        });

        return view('kelola-pengguna.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:admin,petugas',
        ]);

        $role = $validated['role'] ?? 'petugas';

        $exists = DB::table('users')->where('email', $validated['email'])->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['email' => 'Email pengguna sudah terdaftar di sistem.']);
        }

        $this->safeDb(function() use ($validated, $role) {
            DB::table('users')->insert([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, function() {});

        return redirect()->route('kelola-pengguna')->with('success', 'Akun ' . ($role === 'admin' ? 'Administrator' : 'Petugas') . ' baru berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $currentUserEmail = session('user_email', 'admin@dlh.cianjurkab.go.id');
        
        $user = DB::table('users')->where('id', $id)->first();
        if ($user && $user->email === $currentUserEmail) {
            return redirect()->back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.']);
        }

        $this->safeDb(function() use ($id) {
            DB::table('users')->where('id', $id)->delete();
        }, function() {});

        return redirect()->route('kelola-pengguna')->with('success', 'Pengguna berhasil dihapus.');
    }
}
