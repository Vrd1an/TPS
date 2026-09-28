{{-- Halaman Dashboard Utama (Adaptif Admin & Petugas) --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $activeMenu = 'dashboard';
    $userRole = session('user_role', 'petugas');
    $userName = session('user_name', ($userRole === 'admin' ? 'Admin Dinas DLH' : 'Petugas Lapangan DLH'));

    $summaryCards = $summaryCards ?? [];
    $recentActivity = $recentActivity ?? [];
    $mapLocations = $mapLocations ?? [];
    $chartData = $chartData ?? [];
    $totalMenunggu = $totalMenunggu ?? 0;
@endphp

@section('content')
<div class="flex-1 bg-gray-50 overflow-y-auto" id="page-dashboard">

    {{-- Top Bar --}}
    <x-topbar title="Dashboard Utama" subtitle="Sistem Klasifikasi Spasial Algoritma C4.5 — DLH Kab. Cianjur">
        <x-slot:actions>
            <span class="text-xs text-gray-500 hidden md:inline font-medium">{{ now()->isoFormat('dddd, D MMMM Y') }}</span>
            <div class="flex items-center gap-1.5 {{ $userRole === 'admin' ? 'bg-purple-50 border border-purple-200 text-purple-700' : 'bg-blue-50 border border-blue-200 text-blue-700' }} rounded-full px-3 py-1.5 font-bold text-xs">
                <span class="w-2 h-2 rounded-full {{ $userRole === 'admin' ? 'bg-purple-500' : 'bg-blue-500' }} animate-pulse"></span>
                <span>{{ $userRole === 'admin' ? 'Administrator DLH' : 'Petugas Lapangan' }}</span>
            </div>
        </x-slot:actions>
    </x-topbar>

    <div class="px-4 lg:px-8 py-5 space-y-5">
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- Role-based Alert / Welcome Banner --}}
        @if($userRole === 'admin')
            @if($totalMenunggu > 0)
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-2xl p-4 sm:p-5 text-white shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-extrabold">Terdapat {{ $totalMenunggu }} Usulan Lokasi Menunggu Klasifikasi C4.5</h2>
                            <p class="text-xs text-amber-100 mt-0.5">Petugas lapangan telah mengirimkan data usulan baru. Silakan periksa parameter dan lakukan proses klasifikasi.</p>
                        </div>
                    </div>
                    <a href="{{ url('/usulan-lokasi?tab=daftar') }}"
                       class="bg-white text-amber-900 hover:bg-amber-50 px-4 py-2.5 rounded-xl font-extrabold text-xs shadow transition-all shrink-0 text-center">
                        ⚡ Periksa & Klasifikasikan Sekarang →
                    </a>
                </div>
            @endif
        @else
            {{-- Panduan Alur Pengumpulan Data untuk Petugas Lapangan --}}
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold">Panduan Alur Pengumpulan Data Petugas Lapangan</h2>
                            <p class="text-xs text-blue-100">Ikuti tahapan pengukuran dan penginputan data usulan lokasi TPS:</p>
                        </div>
                    </div>
                    <a href="{{ url('/usulan-lokasi') }}"
                       class="bg-white text-blue-800 hover:bg-blue-50 px-4 py-2 rounded-xl text-xs font-bold shadow transition-all text-center shrink-0">
                        + Input Usulan Baru
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 pt-1">
                    <div class="bg-white/10 backdrop-blur rounded-xl p-2.5 text-xs">
                        <p class="font-extrabold text-blue-200 mb-0.5">1. Tentukan Titik</p>
                        <p class="text-[11px] text-blue-100">Pilih titik koordinat TPS secara akurat pada peta satelit/OSM.</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-2.5 text-xs">
                        <p class="font-extrabold text-blue-200 mb-0.5">2. Ukur Permukiman</p>
                        <p class="text-[11px] text-blue-100">Ukur jarak ke permukiman terdekat (Radius SNI: 200m).</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-2.5 text-xs">
                        <p class="font-extrabold text-blue-200 mb-0.5">3. Ukur Sumber Air</p>
                        <p class="text-[11px] text-blue-100">Ukur jarak ke sungai/sumber air (Radius SNI: 100m).</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-2.5 text-xs">
                        <p class="font-extrabold text-blue-200 mb-0.5">4. Simpan Usulan</p>
                        <p class="text-[11px] text-blue-100">Data tersimpan dengan status Menunggu Klasifikasi Admin DLH.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="summary-cards">
            @foreach ($summaryCards as $card)
                <x-metric-card
                    :title="$card['title']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :icon="$card['icon']"
                    :color="$card['color']"
                    :trend="$card['trend']"
                    :href="$card['href']"
                />
            @endforeach
        </div>

        {{-- Map + Activity Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Map --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5" id="section-peta">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm lg:text-base font-bold text-gray-800">Peta Persebaran Titik Usulan & TPS</h2>
                        <p class="text-xs text-gray-400">Wilayah Kabupaten Cianjur — Jawa Barat</p>
                    </div>
                    <a href="{{ url('/hasil-klasifikasi') }}"
                       class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 transition-colors"
                       id="btn-lihat-detail-peta">
                        Buka Peta Lengkap →
                    </a>
                </div>
                <x-map-container mapId="map-dashboard" height="h-64 lg:h-80" :showLegend="true" />
            </div>

            {{-- Activity Feed --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col" id="section-aktivitas">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-sm lg:text-base font-bold text-gray-800">Usulan Terkini</h2>
                        <p class="text-xs text-gray-400">Data Masuk dari Lapangan</p>
                    </div>
                    <a href="{{ url('/usulan-lokasi?tab=daftar') }}" class="text-xs font-bold text-emerald-600 hover:underline">
                        Lihat Semua →
                    </a>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-80 pr-1" id="activity-feed">
                    @forelse ($recentActivity as $item)
                        <div class="flex items-start gap-3 pb-3 border-b border-gray-100 last:border-0">
                            @if ($item['raw_status'] === 'layak')
                                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="m9 12 2 2 4-4"/></svg>
                                </div>
                            @elseif ($item['raw_status'] === 'tidak_layak')
                                <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                                </div>
                            @else
                                <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-800 truncate">{{ $item['location'] }}</p>
                                <p class="text-[11px] text-gray-500">{{ $item['kecamatan'] }} • <span class="text-gray-400">{{ $item['petugas'] }}</span></p>
                                <div class="flex items-center gap-2 mt-1">
                                    @php
                                        $statusBadge = match($item['raw_status']) {
                                            'layak' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'tidak_layak' => 'bg-rose-100 text-rose-800 border border-rose-200',
                                            default => 'bg-amber-100 text-amber-800 border border-amber-200',
                                        };
                                    @endphp
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $statusBadge }}">
                                        {{ $item['status'] }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">{{ $item['time'] }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10 text-center text-gray-400">
                            <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <p class="text-xs">Belum ada aktivitas usulan lokasi baru.</p>
                        </div>
                    @endforelse
                </div>

                <a href="{{ url('/usulan-lokasi') }}"
                   class="mt-3 w-full text-xs font-bold text-center text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 rounded-xl py-2.5 transition-colors block"
                   id="btn-tambah-usulan">
                    + Input Usulan Baru
                </a>
            </div>
        </div>

        {{-- Bar Chart: Distribusi Kelayakan per Kecamatan --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5" id="section-chart-distribusi">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-sm lg:text-base font-bold text-gray-800">Distribusi Status Usulan per Kecamatan</h2>
                    <p class="text-xs text-gray-400">Berdasarkan hasil klasifikasi C4.5 dan usulan yang masuk</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-emerald-600"></div>
                        <span class="text-gray-600 font-medium">Layak</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-rose-400"></div>
                        <span class="text-gray-600 font-medium">Tidak Layak</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-amber-400"></div>
                        <span class="text-gray-600 font-medium">Menunggu</span>
                    </div>
                </div>
            </div>
            <div class="relative h-[180px] w-full">
                <canvas id="chart-distribusi"></canvas>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // LEAFLET MAP — Peta Persebaran TPS Dashboard
    const mapDashboard = L.map('map-dashboard', {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView([-6.8150, 107.1380], 11);

    L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google Maps Satellite Digital',
        maxZoom: 20,
    }).addTo(mapDashboard);

    const locations = @json($mapLocations);

    function createIcon(status) {
        let bgColor = '#f59e0b';
        let symbol = '⏳';
        if (status === 'layak') {
            bgColor = '#059669';
            symbol = '✓';
        } else if (status === 'tidak_layak') {
            bgColor = '#e11d48';
            symbol = '✗';
        }

        return L.divIcon({
            className: 'custom-dashboard-marker',
            html: `<div style="
                width: 22px; height: 22px;
                background: ${bgColor};
                border: 2px solid #ffffff;
                border-radius: 50%;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                display: flex; align-items: center; justify-content: center;
                color: #ffffff; font-weight: 800; font-size: 10px;
            ">${symbol}</div>`,
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            popupAnchor: [0, -11],
        });
    }

    locations.forEach(function(loc) {
        if (!loc.lat || !loc.lng) return;
        const marker = L.marker([loc.lat, loc.lng], { icon: createIcon(loc.status) }).addTo(mapDashboard);
        const statusLabel = (loc.status === 'layak') ? '✓ Layak' : ((loc.status === 'tidak_layak') ? '✗ Tidak Layak' : '⏳ Menunggu Klasifikasi');
        const statusColor = (loc.status === 'layak') ? '#059669' : ((loc.status === 'tidak_layak') ? '#e11d48' : '#d97706');

        marker.bindPopup(`
            <div style="font-family: Inter, sans-serif; min-width: 160px;">
                <p style="font-weight: 700; margin: 0 0 3px; font-size: 12px;">${loc.name}</p>
                <p style="color: #6b7280; font-size: 11px; margin: 0 0 4px;">${loc.kecamatan || ''}</p>
                <p style="color: ${statusColor}; font-weight: 700; font-size: 11px; margin: 0;">${statusLabel}</p>
            </div>
        `);
    });

    // CHART.JS — Distribusi Kelayakan & Menunggu per Kecamatan
    const chartData = @json($chartData);
    const ctx = document.getElementById('chart-distribusi');
    if (ctx && chartData.length > 0) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.kec),
                datasets: [
                    {
                        label: 'Layak',
                        data: chartData.map(d => d.layak || 0),
                        backgroundColor: '#059669',
                        borderRadius: 6,
                    },
                    {
                        label: 'Tidak Layak',
                        data: chartData.map(d => d.tidak || 0),
                        backgroundColor: '#fb7185',
                        borderRadius: 6,
                    },
                    {
                        label: 'Menunggu',
                        data: chartData.map(d => d.menunggu || 0),
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } }
                    }
                }
            }
        });
    }
});
</script>
@endsection
