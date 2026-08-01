<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SIG Kelayakan Lokasi TPS — Sistem Informasi Geografis Penentuan Kelayakan Lokasi Pembangunan TPS menggunakan Algoritma C4.5, Kabupaten Cianjur">
    <title>@yield('title', 'SIG-TPS') — Kab. Cianjur</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                },
            },
        }
    </script>

    {{-- Leaflet.js CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="" />

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    {{-- Custom Base Styles --}}
    <style>
        [x-cloak] { display: none !important; }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* Leaflet map container fix */
        .leaflet-container { font-family: 'Inter', sans-serif !important; }
    </style>

    @yield('styles')
</head>
<body class="font-sans bg-gray-50 text-gray-800 antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen w-screen overflow-hidden">

        {{-- Mobile overlay backdrop --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-[9998] lg:hidden"
             x-cloak></div>

        {{-- Sidebar --}}
        <div class="fixed lg:static inset-y-0 left-0 z-[9999] transition-transform duration-300 ease-in-out lg:translate-x-0"
             :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <x-sidebar :active="$activeMenu ?? 'dashboard'" />
        </div>

        {{-- Main content area --}}
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            {{-- Mobile top header --}}
            <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 lg:hidden shrink-0 shadow-sm">
                <button @click="sidebarOpen = true"
                        id="btn-toggle-sidebar"
                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    {{-- Hamburger icon --}}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md bg-green-700 flex items-center justify-center">
                        <span class="text-white text-xs font-bold">S</span>
                    </div>
                    <span class="font-bold text-gray-800 text-sm">@yield('page-title', 'Dashboard')</span>
                </div>
            </div>

            {{-- Page content --}}
            <div class="flex flex-1 min-h-0 overflow-hidden">
                @yield('content')
            </div>

        </div>
    </div>

    {{-- Leaflet.js --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    {{-- Page-specific scripts --}}
    @yield('scripts')

</body>
</html>
