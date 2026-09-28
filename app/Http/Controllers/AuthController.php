<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim($credentials['email']));
        $password = $credentials['password'];

        // 1. Coba verifikasi lewat Database
        $user = $this->safeDb(function() use ($email) {
            return \Illuminate\Support\Facades\DB::table('users')->where('email', $email)->first();
        }, function() {
            return null;
        });

        if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            $role = $user->role ?? (str_contains($email, 'admin') ? 'admin' : 'petugas');
            session([
                'logged_in' => true,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $role,
            ]);

            $greeting = ($role === 'admin') ? "Selamat datang, {$user->name} (Administrator)!" : "Selamat datang, {$user->name} (Petugas Lapangan)!";
            return redirect('/dashboard')->with('success', $greeting);
        }

        // 2. Fallback Demo Credentials jika database belum siap / mode simulasi
        $demoUsers = [
            'admin@cianjurkab.go.id' => ['password' => 'admin123', 'name' => 'Admin Dinas DLH', 'role' => 'admin'],
            'admin@dlh.cianjurkab.go.id' => ['password' => 'password123', 'name' => 'Administrator DLH', 'role' => 'admin'],
            'petugas@cianjurkab.go.id' => ['password' => 'petugas123', 'name' => 'Petugas Lapangan DLH', 'role' => 'petugas'],
            'petugas@dlh.cianjurkab.go.id' => ['password' => 'password123', 'name' => 'Petugas DLH Cianjur', 'role' => 'petugas'],
        ];

        if (isset($demoUsers[$email]) && $demoUsers[$email]['password'] === $password) {
            $u = $demoUsers[$email];
            session([
                'logged_in' => true,
                'user_id' => ($u['role'] === 'admin') ? 1 : 2,
                'user_name' => $u['name'],
                'user_email' => $email,
                'user_role' => $u['role'],
            ]);

            $greeting = ($u['role'] === 'admin') ? "Selamat datang, {$u['name']} (Administrator)!" : "Selamat datang, {$u['name']} (Petugas Lapangan)!";
            return redirect('/dashboard')->with('success', $greeting);
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email atau password salah. Silakan periksa kembali kredensial Anda.']);
    }

    public function logout()
    {
        session()->forget(['logged_in', 'user_id', 'user_name', 'user_email', 'user_role']);
        return redirect('/login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Simulation response
        return redirect()->back()->with('success', 'Link reset password telah dikirim ke email Anda.');
    }
}
