<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role = 'admin'): Response
    {
        $userRole = session('user_role', 'petugas');

        if ($userRole !== $role && $userRole !== 'admin') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Fitur ini hanya dapat diakses oleh ' . ($role === 'admin' ? 'Administrator' : 'Petugas') . '.'
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Halaman ini hanya dapat diakses oleh Administrator DLH.');
        }

        return $next($request);
    }
}
