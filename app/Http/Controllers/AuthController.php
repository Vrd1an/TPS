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

        // Simulating login for demo purposes
        if ($credentials['email'] === 'admin@cianjurkab.go.id' && $credentials['password'] === 'admin123') {
            session(['logged_in' => true, 'user_name' => 'Admin Dinas']);
            return redirect('/dashboard')->with('success', 'Selamat datang kembali, Admin Dinas!');
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email atau password salah. Silakan coba kembali.']);
    }

    public function logout()
    {
        session()->forget(['logged_in', 'user_name']);
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
