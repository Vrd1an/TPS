{{-- Halaman Dashboard Utama --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $activeMenu = 'dashboard';

    $summaryCards = $summaryCards ?? [
        ['title' => 'Total Data Latih', 'value' => '0 TPS', 'subtitle' => 'Kabupaten Cianjur', 'icon' => 'database', 'color' => 'teal', 'trend' => 'Sinkronisasi Aktif', 'href' => url('/data-latih')],
        ['title' => 'Usulan Baru', 'value' => '0', 'subtitle' => 'Menunggu Verifikasi', 'icon' => 'map-pin', 'color' => 'amber', 'trend' => 'Realtime DB', 'href' => url('/usulan-lokasi')],
        ['title' => 'Akurasi Model', 'value' => '0.0%', 'subtitle' => 'Algoritma C4.5', 'icon' => 'bar-chart', 'color' => 'green', 'trend' => 'Tren Stabil', 'href' => url('/confusion-matrix')],
    ];

    $recentActivity = $recentActivity ?? [];

    // Data untuk peta — lokasi TPS
    $mapLocations = $mapLocations ?? [];

    // Data chart distribusi per kecamatan
    $chartData = $chartData ?? [];
@endphp

@section('content')
<div class="flex-1 bg-gray-50 overflow-y-auto" id="page-dashboard">

    {{-- Top Bar --}}
    <x-topbar title="Dashboard Utama" subtitle="SIG Kelayakan Lokasi TPS — Kab. Cianjur">
        <x-slot:actions>
            <span class="text-xs text-gray-400 hidden md:inline">{{ now()->isoFormat('dddd, D MMMM Y') }}</span>
            <div class="flex items-center gap-1.5 bg-green-50 border border-green-200 rounded-full px-2.5 py-1.5">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-xs font-semibold text-green-700">Aktif</span>
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

            {{-- Card Model C4.5 --}}
            @php
                $modelInfo = $modelInfo ?? ['status' => 'belum_dibentuk', 'root_attribute' => '-', 'total_rules' => 0, 'total_data_latih' => 0, 'trained_at' => '-'];
                $modelStatusLabel = match($modelInfo['status']) {
                    'aktif' => 'Aktif',
                    'training' => 'Sedang Training',
                    default => 'Belum Dibentuk',
                };
                $modelStatusColor = match($modelInfo['status']) {
                    'aktif' => 'bg-emerald-500',
                    'training' => 'bg-amber-500 animate-pulse',
                    default => 'bg-gray-400',
                };
                $modelBorderColor = match($modelInfo['status']) {
                    'aktif' => 'border-emerald-200 bg-emerald-50/30',
                    'training' => 'border-amber-200 bg-amber-50/30',
                    default => 'border-gray-200',
                };
            @endphp
            <a href="{{ url('/decision-tree') }}"
               class="bg-white rounded-2xl shadow-sm border {{ $modelBorderColor }} p-4 hover:shadow-md transition-all duration-200 block group"
               id="card-model-c45">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/>
                                <path d="M13 6h3a2 2 0 0 1 2 2v7"/><path d="M6 9v12"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Model C4.5</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $modelStatusColor }}"></span>
                        <span class="text-xs font-bold {{ $modelInfo['status'] === 'aktif' ? 'text-emerald-700' : ($modelInfo['status'] === 'training' ? 'text-amber-700' : 'text-gray-500') }}">{{ $modelStatusLabel }}</span>
                    </div>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Data Historis</span>
                        <span class="font-bold text-gray-800">{{ $modelInfo['total_data_latih'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Jumlah Rule</span>
                        <span class="font-bold text-gray-800">{{ $modelInfo['total_rules'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Root Node</span>
                        <span class="font-bold text-indigo-700 truncate max-w-[120px]">{{ $modelInfo['root_attribute'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Training</span>
                        <span class="font-bold text-gray-600 truncate max-w-[120px]">{{ $modelInfo['trained_at'] }}</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- Map + Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Map --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5" id="section-peta">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm lg:text-base font-bold text-gray-800">Peta Persebaran TPS</h2>
                        <p class="text-xs text-gray-400">Kabupaten Cianjur — Jawa Barat</p>
                    </div>
                    <a href="{{ url('/hasil-klasifikasi') }}"
                       class="text-xs font-semibold text-teal-600 hover:text-teal-800 border border-teal-200 rounded-lg px-3 py-1.5 hover:bg-teal-50 transition-colors"
                       id="btn-lihat-detail-peta">
                        Lihat Detail →
                    </a>
                </div>
                <x-map-container mapId="map-dashboard" height="h-56 lg:h-72" :showLegend="true" />
            </div>

            {{-- Activity Feed --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col" id="section-aktivitas">
                <h2 class="text-sm lg:text-base font-bold text-gray-800 mb-0.5">Aktivitas Terkini</h2>
                <p class="text-xs text-gray-400 mb-4">Klasifikasi & Usulan Baru</p>

                <div class="space-y-3 flex-1" id="activity-feed">
                    @forelse ($recentActivity as $item)
                        <div class="flex items-start gap-3 pb-3 border-b border-gray-50 last:border-0">
                            @if ($item['status'] === 'Layak')
                                <svg class="w-4 h-4 text-green-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                                </svg>
                            @elseif ($item['status'] === 'Tidak Layak')
                                <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-700 truncate">{{ $item['location'] }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @php
                                        $statusClass = match($item['status']) {
                                            'Layak' => 'bg-green-100 text-green-700',
                                            'Tidak Layak' => 'bg-red-100 text-red-600',
                                            default => 'bg-amber-100 text-amber-700',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $statusClass }}">{{ $item['status'] }}</span>
                                    <span class="text-xs text-gray-400">{{ $item['time'] }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10 text-center text-gray-400">
                            <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <p class="text-xs">Belum ada aktivitas klasifikasi usulan baru.</p>
                        </div>
                    @endforelse
                </div>

                <a href="{{ url('/usulan-lokasi') }}"
                   class="mt-4 w-full text-xs font-semibold text-center text-green-700 hover:text-green-900 bg-green-50 hover:bg-green-100 rounded-lg py-2 transition-colors block"
                   id="btn-tambah-usulan">
                    + Tambah Usulan Lokasi
                </a>
            </div>
        </div>

        {{-- Bar Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5" id="section-chart-distribusi">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-sm lg:text-base font-bold text-gray-800">Distribusi Kelayakan per Kecamatan</h2>
                    <p class="text-xs text-gray-400">Berdasarkan hasil klasifikasi algoritma C4.5</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-green-600"></div>
                        <span class="text-xs text-gray-500">Layak</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-sm bg-red-300"></div>
                        <span class="text-xs text-gray-500">Tidak Layak</span>
                    </div>
                </div>
            </div>
            <div class="relative h-[160px] w-full">
                <canvas id="chart-distribusi"></canvas>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // LEAFLET MAP — Peta Persebaran TPS Dashboard
    // ============================================
    const mapDashboard = L.map('map-dashboard', {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView([-6.8900, 107.2400], 12);

    // Tile layer — OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18,
    }).addTo(mapDashboard);

    // Marker data dari Controller/Blade
    const locations = @json($mapLocations);

    // Custom icon factory
    function createIcon(status) {
        const color = status === 'layak' ? '#2E7D32' : status === 'tidak_layak' ? '#C62828' : '#F57F17';
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="
                width: 24px; height: 24px; border-radius: 50% 50% 50% 0;
                background: ${color}; border: 2px solid white;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                transform: rotate(-45deg);
                display: flex; align-items: center; justify-content: center;
            "><div style="width: 8px; height: 8px; background: white; border-radius: 50%; transform: rotate(45deg);"></div></div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 24],
            popupAnchor: [0, -24],
        });
    }

    locations.forEach(function(loc) {
        const marker = L.marker([loc.lat, loc.lng], { icon: createIcon(loc.status) }).addTo(mapDashboard);
        const statusLabel = loc.status === 'layak' ? '✓ Layak' : loc.status === 'tidak_layak' ? '✗ Tidak Layak' : '⏳ Proses';
        const statusColor = loc.status === 'layak' ? '#2E7D32' : loc.status === 'tidak_layak' ? '#C62828' : '#F57F17';
        marker.bindPopup(`
            <div style="font-family: Inter, sans-serif; min-width: 150px;">
                <p style="font-weight: 700; margin: 0 0 4px;">${loc.name}</p>
                <p style="color: ${statusColor}; font-weight: 600; font-size: 12px; margin: 0;">${statusLabel}</p>
                <p style="color: #9ca3af; font-size: 11px; margin: 4px 0 0; font-family: monospace;">${loc.lat}, ${loc.lng}</p>
            </div>
        `);
    });

    // ============================================
    // QGIS GeoJSON Server Integration (Layer Spasial & Non-Spasial)
    // Hit API endpoint /api/qgis/klasifikasi-geojson
    // ============================================
    const dashSpatialTps3r = L.layerGroup().addTo(mapDashboard);
    const dashSpatialBiodigester = L.layerGroup().addTo(mapDashboard);
    const dashSpatialBankSampah = L.layerGroup().addTo(mapDashboard);
    const dashSpatialBuffer = L.layerGroup().addTo(mapDashboard);

    const dashNonSpatialKepadatan = L.layerGroup();
    const dashNonSpatialKelayakan = L.layerGroup();

    fetch('/api/qgis/klasifikasi-geojson')
        .then(res => res.json())
        .then(geoJson => {
            geoJson.features.forEach(feature => {
                const props = feature.properties;
                const coords = feature.geometry.coordinates;
                const latlng = [coords[1], coords[0]];
                const jenisFas = props.jenis_fasilitas || 'TPS 3R';
                const status = props.status;

                let fasColor = '#16a34a'; // Green (TPS 3R)
                if (jenisFas === 'Biodigester') fasColor = '#0284c7'; // Blue
                if (jenisFas === 'Bank Sampah') fasColor = '#d97706'; // Orange

                // 1. Marker Spasial
                const marker = L.circleMarker(latlng, {
                    radius: 7,
                    fillColor: fasColor,
                    color: '#ffffff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.85
                });
                marker.bindPopup(`
                    <div style="font-family: Inter, sans-serif; min-width: 170px;">
                        <span style="font-size: 10px; background: #e0f2fe; color: #0369a1; padding: 2px 5px; border-radius: 4px; font-weight: 700;">QGIS Server TPS</span>
                        <p style="font-weight: 700; margin: 4px 0 2px; font-size: 12px;">${props.name}</p>
                        <p style="color: #4b5563; font-size: 11px; margin: 0;">Jenis: <strong style="color:${fasColor}">${jenisFas}</strong></p>
                        <p style="color: ${props.marker_color}; font-weight: 600; font-size: 11px; margin: 2px 0 0;">${status === 'layak' ? '✓ Layak' : '✗ Tidak Layak'}</p>
                    </div>
                `);

                if (jenisFas === 'Biodigester') {
                    dashSpatialBiodigester.addLayer(marker);
                } else if (jenisFas === 'Bank Sampah') {
                    dashSpatialBankSampah.addLayer(marker);
                } else {
                    dashSpatialTps3r.addLayer(marker);
                }

                // 2. Buffer Zone Spasial
                const bufCircle = L.circle(latlng, {
                    radius: 300,
                    color: fasColor,
                    weight: 1,
                    dashArray: '3, 3',
                    fillColor: fasColor,
                    fillOpacity: 0.08
                });
                dashSpatialBuffer.addLayer(bufCircle);

                // 3. Layer Non-Spasial: Overlay Atribut Kepadatan
                let kepColor = props.kepadatan === 'Tinggi' ? '#ef4444' : (props.kepadatan === 'Sedang' ? '#f59e0b' : '#10b981');
                const kepMarker = L.circleMarker(latlng, {
                    radius: 11,
                    fillColor: kepColor,
                    color: kepColor,
                    weight: 1,
                    fillOpacity: 0.25
                });
                dashNonSpatialKepadatan.addLayer(kepMarker);

                // 4. Layer Non-Spasial: Overlay Status Kelayakan C4.5
                const kelMarker = L.circleMarker(latlng, {
                    radius: 13,
                    fillColor: props.marker_color,
                    color: props.marker_color,
                    weight: 1.5,
                    fillOpacity: 0.2
                });
                dashNonSpatialKelayakan.addLayer(kelMarker);
            });

            // Layer Control
            const baseMaps = { "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png') };
            const overlayMaps = {
                "--- 🗺️ LAYER SPASIAL QGIS ---": L.layerGroup(),
                "♻️ TPS 3R": dashSpatialTps3r,
                "⚡ Biodigester": dashSpatialBiodigester,
                "🏦 Bank Sampah": dashSpatialBankSampah,
                "⭕ Buffer Zone Spasial": dashSpatialBuffer,
                "--- 📊 LAYER NON-SPASIAL ---": L.layerGroup(),
                "🔥 Kepadatan Penduduk": dashNonSpatialKepadatan,
                "✅ Kelayakan C4.5": dashNonSpatialKelayakan,
            };

            L.control.layers(baseMaps, overlayMaps, { collapsed: true, position: 'topright' }).addTo(mapDashboard);
        })
        .catch(err => console.error('QGIS Dashboard API Fetch Error:', err));


    // ============================================
    // CHART.JS — Distribusi Kelayakan per Kecamatan
    // ============================================
    const chartData = @json($chartData);

    const ctx = document.getElementById('chart-distribusi').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.map(d => d.kec),
            datasets: [
                {
                    label: 'Layak',
                    data: chartData.map(d => d.layak),
                    backgroundColor: '#2E7D32',
                    borderRadius: 4,
                    barPercentage: 0.6,
                },
                {
                    label: 'Tidak Layak',
                    data: chartData.map(d => d.tidak),
                    backgroundColor: '#ef9a9a',
                    borderRadius: 4,
                    barPercentage: 0.6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#374151',
                    bodyColor: '#6b7280',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    cornerRadius: 8,
                    titleFont: { weight: '600' },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 }, color: '#9ca3af' },
                    border: { display: false },
                },
                y: {
                    grid: { color: '#f0f0f0', drawBorder: false },
                    ticks: { font: { size: 10 }, color: '#9ca3af' },
                    border: { display: false },
                },
            },
        },
    });

});
</script>
@endsection
