<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIG Kelayakan Lokasi TPS C4.5</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center relative overflow-hidden px-4">
    
    {{-- Animated BG elements --}}
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-green-500 rounded-full blur-[120px] opacity-25 animate-pulse"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-teal-500 rounded-full blur-[120px] opacity-25 animate-pulse"></div>

    <div class="w-full max-w-md bg-slate-800/80 backdrop-blur-xl border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl relative z-10 space-y-5"
         x-data="{
             role: 'admin',
             email: '{{ old('email', 'admin@cianjurkab.go.id') }}',
             password: '{{ old('password', 'admin123') }}',
             setRole(r) {
                 this.role = r;
                 if (r === 'admin') {
                     this.email = 'admin@cianjurkab.go.id';
                     this.password = 'admin123';
                 } else {
                     this.email = 'petugas@cianjurkab.go.id';
                     this.password = 'petugas123';
                 }
             }
         }">
        
        {{-- App Logo / Identity --}}
        <div class="text-center space-y-1.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg mx-auto">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 19 2c1 2 2 4.5 1 8-1.5 5-5.7 8-9 10Z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-white">SIG Kelayakan TPS</h1>
                <p class="text-xs text-slate-400">Sistem Klasifikasi Spasial Algoritma C4.5 Kab. Cianjur</p>
            </div>
        </div>

        {{-- Role Switcher Tabs --}}
        <div class="bg-slate-900/90 p-1 rounded-2xl border border-slate-700 flex gap-1">
            <button type="button" @click="setRole('admin')"
                    class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                    :class="role === 'admin' ? 'bg-emerald-600 text-white shadow' : 'text-slate-400 hover:text-white'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Administrator DLH
            </button>
            <button type="button" @click="setRole('petugas')"
                    class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                    :class="role === 'petugas' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                Petugas Lapangan
            </button>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="bg-green-500/20 border border-green-500/30 text-green-300 px-4 py-2.5 rounded-xl text-xs flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="m9 11 3 3 6-6"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-500/20 border border-red-500/30 text-red-300 px-4 py-2.5 rounded-xl text-xs flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Login Form --}}
        <form action="{{ url('/login') }}" method="POST" class="space-y-3.5">
            @csrf

            <div class="space-y-1">
                <label for="input-email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                    Email Pengguna (<span x-text="role === 'admin' ? 'Administrator' : 'Petugas Lapangan'"></span>)
                </label>
                <input type="email" name="email" id="input-email" required x-model="email"
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors placeholder-slate-600"
                       placeholder="nama@cianjurkab.go.id" />
            </div>

            <div class="space-y-1">
                <div class="flex justify-between items-center">
                    <label for="input-password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
                    <a href="{{ url('/forgot-password') }}" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium">Lupa Password?</a>
                </div>
                <input type="password" name="password" id="input-password" required x-model="password"
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors placeholder-slate-600"
                       placeholder="••••••••" />
            </div>

            <button type="submit" 
                    class="w-full text-white rounded-xl py-3 text-sm font-bold shadow-lg hover:shadow-xl transition-all mt-1 flex items-center justify-center gap-2"
                    :class="role === 'admin' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700' : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700'">
                <span>Masuk sebagai</span>
                <span x-text="role === 'admin' ? 'Administrator DLH' : 'Petugas Lapangan'"></span>
                <span>→</span>
            </button>
        </form>

        <div class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3 text-[11px] space-y-1 text-slate-400">
            <p class="font-bold text-slate-300">💡 Informasi Akun Login:</p>
            <div class="flex justify-between items-center">
                <span>🛡️ <strong>Admin</strong> (Full Akses & Klasifikasi C4.5):</span>
                <span class="font-mono text-emerald-400">admin123</span>
            </div>
            <div class="flex justify-between items-center">
                <span>👷 <strong>Petugas</strong> (Input Usulan & Pengukuran):</span>
                <span class="font-mono text-blue-400">petugas123</span>
            </div>
        </div>
    </div>
</body>
</html>
