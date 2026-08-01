<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — SIG Kelayakan Lokasi TPS C4.5</title>
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

    <div class="w-full max-w-md bg-slate-800/80 backdrop-blur-xl border border-slate-700 rounded-3xl p-6 sm:p-10 shadow-2xl relative z-10 space-y-6">
        
        {{-- Header Identity --}}
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-teal-600 flex items-center justify-center shadow-lg mx-auto">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-white">Reset Password</h1>
                <p class="text-xs text-slate-400">Masukkan email terdaftar untuk menerima link reset password</p>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="bg-green-500/20 border border-green-500/30 text-green-300 px-4 py-3 rounded-xl text-xs flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="m9 11 3 3 6-6"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Reset Form --}}
        <form action="{{ url('/forgot-password') }}" method="POST" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <label for="input-email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Email Dinas</label>
                <input type="email" name="email" id="input-email" required
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-green-500 transition-colors placeholder-slate-600"
                       placeholder="nama@cianjurkab.go.id" />
            </div>

            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white rounded-xl py-3.5 text-sm font-bold shadow-lg hover:shadow-xl transition-all mt-2">
                Kirim Link Reset
            </button>
        </form>

        <div class="text-center pt-2">
            <a href="{{ url('/login') }}" class="text-xs text-slate-400 hover:text-white font-medium flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Kembali ke halaman login
            </a>
        </div>
    </div>
</body>
</html>
