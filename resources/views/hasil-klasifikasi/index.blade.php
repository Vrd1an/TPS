{{-- Halaman Peta Hasil Klasifikasi (SIG Klasifikasi TPS C4.5) --}}
@extends('layouts.app')

@section('title', 'Peta Hasil Klasifikasi')
@section('page-title', 'Hasil Klasifikasi')

@php
    $activeMenu = 'hasil-klasifikasi';
    $locations = $locations ?? [];
@endphp

@section('content')
<script>
    window.appLocationsData = @json($locations);

    function hasilKlasifikasiApp() {
        return {
            locations: window.appLocationsData,
            selectedId: null,
            statusFilter: 'all',
            searchKeyword: '',
            showPanel: false,

            get filtered() {
                let list = this.locations || [];
                if (this.statusFilter !== 'all') {
                    list = list.filter(l => l.status === this.statusFilter);
                }
                if (this.searchKeyword.trim() !== '') {
                    const kw = this.searchKeyword.toLowerCase();
                    list = list.filter(l =>
                        (l.name && l.name.toLowerCase().includes(kw)) ||
                        (l.kecamatan && l.kecamatan.toLowerCase().includes(kw)) ||
                        (l.jenis_fasilitas && l.jenis_fasilitas.toLowerCase().includes(kw))
                    );
                }
                return list;
            },

            get selected() {
                return this.locations.find(l => l.id === this.selectedId) || null;
            },

            init() {
                const urlParams = new URLSearchParams(window.location.search);
                const newId = urlParams.get('new_id');
                const kec = urlParams.get('kecamatan');

                if (newId) {
                    const target = this.locations.find(l => l.id == newId || l.id == ('usulan-' + newId));
                    if (target) {
                        this.selectedId = target.id;
                        this.$nextTick(() => {
                            if (window.selectMarkerOnMap) {
                                window.selectMarkerOnMap(target.id);
                            }
                        });
                    }
                } else if (this.locations.length > 0) {
                    this.selectedId = this.locations[0].id;
                }
            }
        };
    }
</script>

<div class="flex-1 flex flex-col overflow-hidden" id="page-hasil-klasifikasi" x-data="hasilKlasifikasiApp()">

    {{-- Top Bar Navigation --}}
    <x-topbar title="Peta Hasil Klasifikasi TPS" subtitle="Visualisasi Spasial Lokasi Pembangunan TPS di Kabupaten Cianjur">
        <x-slot:actions>
            {{-- Filter Status Tabs --}}
            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
                <button type="button" @click="statusFilter = 'all'"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                        :class="statusFilter === 'all' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-800'">
                    Semua (<span x-text="locations.length"></span>)
                </button>
                <button type="button" @click="statusFilter = 'layak'"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                        :class="statusFilter === 'layak' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-500 hover:text-emerald-700'">
                    ✓ Layak
                </button>
                <button type="button" @click="statusFilter = 'tidak_layak'"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                        :class="statusFilter === 'tidak_layak' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-500 hover:text-rose-700'">
                    ✗ Tidak Layak
                </button>
                <button type="button" @click="statusFilter = 'menunggu_klasifikasi'"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                        :class="statusFilter === 'menunggu_klasifikasi' ? 'bg-amber-500 text-white shadow-sm' : 'text-gray-500 hover:text-amber-700'">
                    ⏳ Menunggu
                </button>
            </div>

            {{-- Layer Dropdown --}}
            <div class="relative" x-data="{ openLayers: false }">
                <button @click="openLayers = !openLayers"
                        class="flex items-center gap-1.5 bg-white border border-gray-200 px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-sm">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                    </svg>
                    <span>Pilihan Layer</span>
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div x-show="openLayers" @click.outside="openLayers = false" x-cloak
                     class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 p-3 z-50 space-y-2 text-xs">
                    <p class="font-bold text-gray-700 uppercase tracking-wider text-[10px] pb-1 border-b">Tipe Peta Dasar</p>
                    <div class="space-y-1">
                        <button type="button" @click="switchLeafletBaseMap('satellite'); openLayers = false;"
                                class="w-full text-left px-2 py-1.5 rounded-lg font-semibold hover:bg-emerald-50 text-emerald-700 flex items-center justify-between">
                            <span>🛰️ Citra Satelit Google</span>
                        </button>
                        <button type="button" @click="switchLeafletBaseMap('osm'); openLayers = false;"
                                class="w-full text-left px-2 py-1.5 rounded-lg font-semibold hover:bg-emerald-50 text-gray-700 flex items-center justify-between">
                            <span>🗺️ OpenStreetMap</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Search Box --}}
            <div class="relative w-44 sm:w-56">
                <input type="text" x-model="searchKeyword"
                       placeholder="Cari TPS atau desa..."
                       class="w-full bg-white border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:border-emerald-500 shadow-sm" />
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
            </div>
        </x-slot:actions>
    </x-topbar>

    {{-- Main Container: Sidebar + Map --}}
    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden relative">

        {{-- Left Sidebar: Detail Panel & Location List --}}
        <div class="w-full lg:w-84 lg:max-w-xs shrink-0 bg-white border-b lg:border-b-0 lg:border-r border-gray-200 flex flex-col overflow-y-auto max-h-72 lg:max-h-none z-10"
             id="panel-info-klasifikasi">

            {{-- Detail of Selected Location --}}
            <div class="p-4 border-b border-gray-100 bg-gray-50/50" x-show="selected" x-cloak>
                <div class="flex items-start justify-between gap-2 mb-2.5">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Lokasi Terpilih</span>
                        <h2 class="text-sm font-extrabold text-gray-800 leading-snug" x-text="selected?.name"></h2>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="selected?.kecamatan"></p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                          :class="selected?.source === 'historis' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
                          x-text="selected?.source === 'historis' ? 'Data Historis' : 'Usulan Baru'"></span>
                </div>

                {{-- Status Badge Card --}}
                <div class="rounded-xl p-3 mb-3 border shadow-sm"
                     :class="selected?.status === 'layak' ? 'bg-emerald-50 border-emerald-200' : (selected?.status === 'tidak_layak' ? 'bg-rose-50 border-rose-200' : 'bg-amber-50 border-amber-200')">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status Keputusan C4.5</span>
                        <span class="text-xs font-mono font-extrabold"
                              :class="selected?.status === 'layak' ? 'text-emerald-700' : (selected?.status === 'tidak_layak' ? 'text-rose-700' : 'text-amber-700')"
                              x-text="'Confidence: ' + (selected?.confidence || '100%')"></span>
                    </div>
                    <p class="text-lg font-black mt-0.5 tracking-wide"
                       :class="selected?.status === 'layak' ? 'text-emerald-700' : (selected?.status === 'tidak_layak' ? 'text-rose-700' : 'text-amber-700')"
                       x-text="selected?.status === 'layak' ? '✓ STATUS: LAYAK' : (selected?.status === 'tidak_layak' ? '✗ STATUS: TIDAK LAYAK' : '⏳ MENUNGGU KLASIFIKASI')"></p>
                </div>

                {{-- Spatial & C4.5 Criteria --}}
                <div class="bg-white rounded-xl p-3 border border-gray-200 mb-3 space-y-2 text-xs">
                    <p class="font-bold text-gray-700 uppercase tracking-wider text-[10px] border-b pb-1">Parameter Spasial SNI 03-3241-1994</p>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">🏠 Jarak Permukiman:</span>
                        <span class="font-bold text-gray-800" x-text="selected?.jarak_permukiman"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">💧 Jarak Sumber Air:</span>
                        <span class="font-bold text-gray-800" x-text="selected?.jarak_air"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">👥 Kepadatan Penduduk:</span>
                        <span class="font-bold text-gray-800" x-text="selected?.kepadatan"></span>
                    </div>
                    <div class="flex justify-between items-center pt-1 border-t text-[11px] font-mono text-gray-400">
                        <span>Koordinat:</span>
                        <span x-text="selected?.lat?.toFixed(5) + ', ' + selected?.lng?.toFixed(5)"></span>
                    </div>
                </div>

                {{-- Decision Tree Path --}}
                <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3 text-xs" x-show="selected?.rule">
                    <p class="font-bold text-blue-900 uppercase tracking-wider text-[10px] mb-1.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M13 6h3a2 2 0 0 1 2 2v7"/>
                        </svg>
                        Jalur Aturan C4.5
                    </p>
                    <div id="decision-tree-render" class="space-y-1 font-mono text-[11px] text-gray-700"></div>
                </div>
            </div>

            {{-- Location List --}}
            <div class="flex-1 p-3 min-h-0 overflow-y-auto space-y-1.5" id="list-lokasi">
                <div class="flex items-center justify-between px-1 mb-1">
                    <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">
                        Daftar Lokasi (<span x-text="filtered.length"></span>)
                    </span>
                </div>

                <template x-for="loc in filtered" :key="loc.id">
                    <button @click="selectedId = loc.id; selectMarkerOnMap(loc.id);"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-left transition-all border"
                            :class="selectedId === loc.id ? 'bg-emerald-50/80 border-emerald-300 shadow-sm' : 'bg-white hover:bg-gray-50 border-gray-100'">
                        
                        <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 shadow-sm font-bold text-[10px]"
                             :class="loc.status === 'layak' ? 'bg-emerald-600 text-white' : (loc.status === 'tidak_layak' ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white')">
                            <span x-text="loc.status === 'layak' ? '✓' : (loc.status === 'tidak_layak' ? '✗' : '⏳')"></span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-gray-800 truncate" x-text="loc.name"></p>
                            <p class="text-[10px] text-gray-400 truncate" x-text="loc.kecamatan"></p>
                        </div>

                        <span class="text-[10px] font-extrabold shrink-0 px-2 py-0.5 rounded-full"
                              :class="loc.status === 'layak' ? 'text-emerald-700 bg-emerald-100' : (loc.status === 'tidak_layak' ? 'text-rose-700 bg-rose-100' : 'text-amber-700 bg-amber-100')"
                              x-text="loc.status === 'layak' ? 'Layak' : (loc.status === 'tidak_layak' ? 'Tidak Layak' : 'Menunggu')"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Map View Area --}}
        <div class="flex-1 relative overflow-hidden">
            <div id="map-container" class="w-full h-full z-0"></div>

            {{-- Floating Legend --}}
            <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-md rounded-2xl p-3.5 shadow-xl border border-gray-200 z-[400] text-xs space-y-2">
                <p class="font-black text-gray-800 uppercase tracking-wider text-[10px]">Legenda Peta Spasial C4.5</p>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-600 border border-white shadow-sm shrink-0"></span>
                        <span class="text-gray-700 font-semibold text-[11px]">Lokasi Layak (C4.5)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-rose-600 border border-white shadow-sm shrink-0"></span>
                        <span class="text-gray-700 font-semibold text-[11px]">Lokasi Tidak Layak (C4.5)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full bg-amber-500 border border-white shadow-sm shrink-0"></span>
                        <span class="text-gray-700 font-semibold text-[11px]">Usulan Menunggu Klasifikasi</span>
                    </div>
                    <div class="border-t border-gray-200 pt-1.5 space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-1 bg-amber-500 rounded"></span>
                            <span class="text-gray-600 text-[10px]">Radius Buffer Permukiman (200m)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-1 bg-cyan-500 rounded"></span>
                            <span class="text-gray-600 text-[10px]">Radius Buffer Sempadan Air (100m)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const locations = window.appLocationsData || [];
    const markers = {};
    let activeHighlightCirclePermukiman = null;
    let activeHighlightCircleAir = null;
    let activeMeasurementLinePermukiman = null;
    let activeMeasurementLineAir = null;

    // 1. Inisialisasi Peta Leaflet Berpusat di Kabupaten Cianjur
    const map = L.map('map-container', {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView([-6.8150, 107.1380], 11);

    const baseGoogleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google Satellite Imagery',
        maxZoom: 20,
    }).addTo(map);

    const baseOpenStreetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    });

    window.switchLeafletBaseMap = function(type) {
        if (type === 'satellite') {
            map.removeLayer(baseOpenStreetMap);
            map.addLayer(baseGoogleSatellite);
        } else {
            map.removeLayer(baseGoogleSatellite);
            map.addLayer(baseOpenStreetMap);
        }
    };

    // Custom Clean Marker Factory
    function createIcon(status, isSelected) {
        let bgColor = '#f59e0b'; // Amber (Menunggu)
        let ringColor = 'rgba(245, 158, 11, 0.4)';
        let symbol = '⏳';

        if (status === 'layak') {
            bgColor = '#059669'; // Emerald (Layak)
            ringColor = 'rgba(5, 150, 105, 0.4)';
            symbol = '✓';
        } else if (status === 'tidak_layak') {
            bgColor = '#e11d48'; // Rose (Tidak Layak)
            ringColor = 'rgba(225, 29, 72, 0.4)';
            symbol = '✗';
        }

        const size = isSelected ? 34 : 26;
        const shadow = isSelected ? `0 0 0 6px ${ringColor}, 0 4px 12px rgba(0,0,0,0.4)` : '0 2px 8px rgba(0,0,0,0.3)';

        return L.divIcon({
            className: 'custom-tps-marker',
            html: `<div style="
                width: ${size}px; height: ${size}px;
                background: ${bgColor};
                border: 2px solid #ffffff;
                border-radius: 50%;
                box-shadow: ${shadow};
                display: flex; align-items: center; justify-content: center;
                color: #ffffff; font-weight: 800; font-size: ${isSelected ? 14 : 11}px;
                transition: all 0.2s ease-in-out;
            ">${symbol}</div>`,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            popupAnchor: [0, -size / 2],
        });
    }

    // Gambar buffer radius dan garis jarak secara eksklusif untuk titik yang sedang aktif / diklik
    function renderActiveLocationSpatialBuffers(lat, lng, jarakPermukimanText, jarakAirText) {
        // Bersihkan layer sebelumnya
        if (activeHighlightCirclePermukiman) map.removeLayer(activeHighlightCirclePermukiman);
        if (activeHighlightCircleAir) map.removeLayer(activeHighlightCircleAir);
        if (activeMeasurementLinePermukiman) map.removeLayer(activeMeasurementLinePermukiman);
        if (activeMeasurementLineAir) map.removeLayer(activeMeasurementLineAir);

        // Radius Permukiman 200m (SNI)
        activeHighlightCirclePermukiman = L.circle([lat, lng], {
            radius: 200,
            color: '#f59e0b',
            weight: 2,
            fillColor: '#fbbf24',
            fillOpacity: 0.15,
            dashArray: '5, 5'
        }).addTo(map);
        activeHighlightCirclePermukiman.bindTooltip("🏠 Radius Buffer Permukiman: 200m (SNI 03-3241-1994)", { direction: 'top' });

        // Radius Sempadan Air 100m (SNI)
        activeHighlightCircleAir = L.circle([lat, lng], {
            radius: 100,
            color: '#06b6d4',
            weight: 2,
            fillColor: '#22d3ee',
            fillOpacity: 0.18,
            dashArray: '4, 4'
        }).addTo(map);
        activeHighlightCircleAir.bindTooltip("💧 Radius Sempadan Air: 100m (SNI 03-3241-1994)", { direction: 'bottom' });

        // Garis Ukur Spasial ke Permukiman Terdekat
        activeMeasurementLinePermukiman = L.polyline([
            [lat, lng],
            [lat + 0.0018, lng + 0.0022]
        ], {
            color: '#f59e0b',
            weight: 2.5,
            dashArray: '4, 4'
        }).addTo(map);
        activeMeasurementLinePermukiman.bindTooltip(`🏠 Jarak Permukiman: ${jarakPermukimanText || 'Sedang'}`, { permanent: false });

        // Garis Ukur Spasial ke Sumber Air Terdekat
        activeMeasurementLineAir = L.polyline([
            [lat, lng],
            [lat - 0.0015, lng - 0.0020]
        ], {
            color: '#06b6d4',
            weight: 2.5,
            dashArray: '3, 4'
        }).addTo(map);
        activeMeasurementLineAir.bindTooltip(`💧 Jarak Sumber Air: ${jarakAirText || 'Jauh'}`, { permanent: false });
    }

    // Populate Location Markers on Real Cianjur Points
    locations.forEach(function(loc) {
        if (!loc.lat || !loc.lng) return;

        const marker = L.marker([loc.lat, loc.lng], {
            icon: createIcon(loc.status, false)
        }).addTo(map);

        const statusLabel = loc.status === 'layak' ? '✓ LAYAK' : (loc.status === 'tidak_layak' ? '✗ TIDAK LAYAK' : '⏳ MENUNGGU KLASIFIKASI');
        const statusColor = loc.status === 'layak' ? '#059669' : (loc.status === 'tidak_layak' ? '#e11d48' : '#d97706');

        marker.bindPopup(`
            <div style="font-family: Inter, sans-serif; min-width: 220px;">
                <span style="font-size: 10px; background: ${loc.source === 'historis' ? '#eff6ff' : '#faf5ff'}; color: ${loc.source === 'historis' ? '#1d4ed8' : '#7e22ce'}; padding: 2px 7px; border-radius: 6px; font-weight: 800;">
                    ${loc.source === 'historis' ? 'Data Historis DLH' : 'Usulan TPS Lapangan'}
                </span>
                <h4 style="font-weight: 800; margin: 6px 0 2px; font-size: 13px; color: #1f2937;">${loc.name}</h4>
                <p style="color: #4b5563; font-size: 11px; margin: 0 0 4px;">Kecamatan: <strong>${loc.kecamatan}</strong></p>
                <div style="background: #f9fafb; padding: 6px 8px; border-radius: 8px; margin: 6px 0; font-size: 11px; border: 1px solid #e5e7eb;">
                    <p style="margin: 0; color: #374151;">🏠 Permukiman: <strong>${loc.jarak_permukiman}</strong></p>
                    <p style="margin: 2px 0 0; color: #374151;">💧 Sumber Air: <strong>${loc.jarak_air}</strong></p>
                    <p style="margin: 2px 0 0; color: #374151;">👥 Kepadatan: <strong>${loc.kepadatan}</strong></p>
                </div>
                <p style="color: ${statusColor}; font-weight: 900; font-size: 12px; margin: 4px 0 0;">Status: ${statusLabel}</p>
                ${loc.confidence ? '<p style="color: #6b7280; font-size: 10px; margin: 2px 0 0;">Confidence Score: ' + loc.confidence + '</p>' : ''}
            </div>
        `);

        marker.on('click', function() {
            const el = document.getElementById('page-hasil-klasifikasi');
            if (el && el._x_dataStack) {
                el._x_dataStack[0].selectedId = loc.id;
            }
            highlightMarker(loc.id);
            updateDecisionTree(loc);
            renderActiveLocationSpatialBuffers(loc.lat, loc.lng, loc.jarak_permukiman, loc.jarak_air);
        });

        markers[loc.id] = marker;
    });

    function highlightMarker(id) {
        locations.forEach(function(loc) {
            if (markers[loc.id]) {
                markers[loc.id].setIcon(createIcon(loc.status, loc.id === id));
            }
        });
    }

    window.selectMarkerOnMap = function(id) {
        const loc = locations.find(l => l.id === id);
        if (loc && markers[id]) {
            map.flyTo([loc.lat, loc.lng], 14, { duration: 1.2 });
            highlightMarker(id);
            markers[id].openPopup();
            updateDecisionTree(loc);
            renderActiveLocationSpatialBuffers(loc.lat, loc.lng, loc.jarak_permukiman, loc.jarak_air);
        }
    };

    function updateDecisionTree(loc) {
        const container = document.getElementById('decision-tree-render');
        if (!container || !loc || !loc.rule) return;
        const steps = loc.rule.split(' → ');
        container.innerHTML = steps.map((step, i) => {
            const isLast = i === steps.length - 1;
            const color = isLast ? (loc.status === 'layak' ? 'font-bold text-emerald-700' : 'font-bold text-rose-600') : 'text-gray-600';
            const dotBg = isLast ? (loc.status === 'layak' ? 'bg-emerald-600 border-emerald-600' : 'bg-rose-600 border-rose-600') : 'bg-white border-gray-300';
            const innerDot = isLast ? 'bg-white' : 'bg-gray-400';
            const line = !isLast ? '<div class="w-0.5 h-2.5 bg-gray-300 mt-0.5"></div>' : '';
            return `
                <div class="flex items-start gap-2">
                    <div class="flex flex-col items-center mt-0.5">
                        <div class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center shrink-0 ${dotBg}">
                            <div class="w-1 h-1 rounded-full ${innerDot}"></div>
                        </div>
                        ${line}
                    </div>
                    <span class="text-[11px] leading-relaxed ${color}">${step}</span>
                </div>
            `;
        }).join('');
    }

    // Select first location on startup if available
    if (locations.length > 0) {
        const first = locations[0];
        highlightMarker(first.id);
        updateDecisionTree(first);
        renderActiveLocationSpatialBuffers(first.lat, first.lng, first.jarak_permukiman, first.jarak_air);
    }
});
</script>
@endsection
