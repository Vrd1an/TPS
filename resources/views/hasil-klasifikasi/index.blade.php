{{-- Halaman Hasil Klasifikasi (Map Integration) --}}
@extends('layouts.app')

@section('title', 'Hasil Klasifikasi')
@section('page-title', 'Hasil Klasifikasi')

@php
    $activeMenu = 'hasil-klasifikasi';

    $locations = $locations ?? [
        [
            'id' => 'usulan-1', 'name' => 'TPS Desa Bojong', 'status' => 'layak', 'kecamatan' => 'Kec. Cianjur', 'jenis_fasilitas' => 'TPS 3R',
            'lat' => -6.8768, 'lng' => 107.2412, 'kepadatan' => 'Tinggi',
            'jarak_permukiman' => 'Sedang (200-500m)', 'jarak_air' => 'Jauh (> 300m)',
            'rule' => 'Jarak Air = Jauh → Jarak Permukiman = Sedang → Kepadatan = Tinggi → LAYAK',
            'confidence' => '92.4%',
        ]
    ];
@endphp

@section('content')
<script>
    window.appLocationsData = @json($locations);
    const urlParams = new URLSearchParams(window.location.search);
    const newIdParam = urlParams.get('new_id');
    const kecamatanParam = urlParams.get('kecamatan');

    function hasilKlasifikasiApp() {
        return {
            locations: window.appLocationsData || [],
            // Jika diakses dari Usulan Lokasi TPS Baru (ada param new_id), otomatis pilih item usulan baru!
            // Jika dibuka secara langsung tanpa usulan baru, kosongkan (null)!
            selectedId: newIdParam ? newIdParam : null,
            isNewSubmission: !!newIdParam,
            filterKecamatanName: kecamatanParam || '',
            showPanel: true,
            layerOpen: false,
            searchText: '',
            baseMap: 'satellite', // Default Google Satellite
            layerState: {
                titikTps: true,
                pendudukBps: true,
                sungai: true,
                jarakPermukiman: true,
                jarakSungai: true,
                statusC45: true
            },
            get selected() {
                if (!this.selectedId) return null;
                return this.locations.find(l => l.id === this.selectedId) || null;
            },
            get filtered() {
                let list = this.locations;
                if (this.searchText) {
                    list = list.filter(l => (l.name || '').toLowerCase().includes(this.searchText.toLowerCase()));
                }
                return list;
            },
            toggleLayer(name) {
                this.layerState[name] = !this.layerState[name];
                if (window.setMapLayerVisibility) {
                    window.setMapLayerVisibility(name, this.layerState[name]);
                }
            },
            switchBaseMap(type) {
                this.baseMap = type;
                if (window.switchLeafletBaseMap) {
                    window.switchLeafletBaseMap(type);
                }
            }
        };
    }
</script>

<div class="flex-1 flex flex-col overflow-hidden" id="page-hasil-klasifikasi" x-data="hasilKlasifikasiApp()">

    {{-- Top Bar --}}
    <div class="bg-white border-b border-gray-200 px-4 lg:px-6 py-3 flex items-center justify-between shrink-0 shadow-sm z-10 gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h1 class="text-base lg:text-xl font-bold text-gray-800 truncate">Peta Hasil Klasifikasi</h1>
                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-emerald-200 shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
                        <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
                    </svg>
                    QGIS REST API
                </span>
            </div>
            <p class="text-xs text-gray-500 hidden sm:block">Visualisasi Spasial & Non-Spasial Lokasi Pembangunan TPS</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            {{-- Tombol Pilihan Layer (Dropdown Terpadu) --}}
            <div class="relative">
                <button @click="layerOpen = !layerOpen"
                        class="flex items-center gap-1.5 text-xs font-bold border rounded-xl px-3.5 py-2 shadow-sm transition-all"
                        :class="layerOpen ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                        id="btn-layer-toggle">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
                        <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
                    </svg>
                    <span>Pilihan Layer</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="layerOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                {{-- Custom Layer Control Dropdown Menu (Menutup Box Default Leaflet yang Melayang) --}}
                <div x-show="layerOpen" x-cloak x-transition @click.outside="layerOpen = false"
                     class="absolute right-0 top-full mt-2 w-72 bg-white border border-gray-200 rounded-2xl shadow-2xl z-[1200] overflow-hidden p-3.5 space-y-2">
                    
                    {{-- Section 1: Base Map --}}
                    <div class="pb-1 border-b border-gray-100">
                        <p class="text-[11px] font-extrabold text-gray-500 uppercase tracking-wider mb-1.5">🗺️ PETA DASAR (BASE MAP)</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button @click.stop="switchBaseMap('osm')"
                                    class="px-2 py-1.5 rounded-lg text-xs font-semibold border transition-all text-center"
                                    :class="baseMap === 'osm' ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                                Standar OSM
                            </button>
                            <button @click.stop="switchBaseMap('satellite')"
                                    class="px-2 py-1.5 rounded-lg text-xs font-semibold border transition-all text-center"
                                    :class="baseMap === 'satellite' ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                                Citra Satelit
                            </button>
                        </div>
                    </div>

                    {{-- Section 2: Layer Spasial (SHP Geometri) --}}
                    <div class="flex items-center justify-between pt-1 pb-0.5">
                        <p class="text-[11px] font-extrabold text-emerald-800 uppercase tracking-wider">🗺️ LAYER SPASIAL (SHP/QGIS)</p>
                        <span class="text-[10px] text-gray-400 font-medium">SHP Geometri</span>
                    </div>

                    <label @click.stop="toggleLayer('titikTps')" class="flex items-center gap-2.5 px-2 py-1 rounded-xl hover:bg-emerald-50 cursor-pointer text-xs font-medium text-gray-700 transition-colors">
                        <input type="checkbox" :checked="layerState.titikTps" class="rounded text-emerald-600 focus:ring-emerald-500 pointer-events-none">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shadow-sm"></span>
                        <span>📌 Titik Lokasi TPS (Point)</span>
                    </label>

                    <label @click.stop="toggleLayer('pendudukBps')" class="flex items-center gap-2.5 px-2 py-1 rounded-xl hover:bg-indigo-50 cursor-pointer text-xs font-medium text-gray-700 transition-colors">
                        <input type="checkbox" :checked="layerState.pendudukBps" class="rounded text-indigo-600 focus:ring-indigo-500 pointer-events-none">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block shadow-sm"></span>
                        <span>👥 Layer Penduduk / Kecamatan BPS</span>
                    </label>

                    <label @click.stop="toggleLayer('sungai')" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-sky-50 cursor-pointer text-xs font-medium text-gray-700 transition-colors">
                        <input type="checkbox" :checked="layerState.sungai" class="rounded text-sky-600 focus:ring-sky-500 pointer-events-none">
                        <span class="w-2.5 h-2.5 rounded-full bg-sky-600 inline-block shadow-sm"></span>
                        <span>🌊 Layer Sungai / Sumber Air</span>
                    </label>

                    <label @click.stop="toggleLayer('jarakPermukiman')" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-amber-50 cursor-pointer text-xs font-medium text-gray-700 transition-colors">
                        <input type="checkbox" :checked="layerState.jarakPermukiman" class="rounded text-amber-600 focus:ring-amber-500 pointer-events-none">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block shadow-sm"></span>
                        <span>🏠 Layer Jarak Permukiman (Line)</span>
                    </label>

                    <label @click.stop="toggleLayer('jarakSungai')" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-cyan-50 cursor-pointer text-xs font-medium text-gray-700 transition-colors">
                        <input type="checkbox" :checked="layerState.jarakSungai" class="rounded text-cyan-600 focus:ring-cyan-500 pointer-events-none">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-600 inline-block shadow-sm"></span>
                        <span>💧 Layer Jarak Sungai / Air (Line)</span>
                    </label>

                    <div class="border-t border-gray-100 my-1"></div>

                    {{-- Section 3: Layer Non-Spasial --}}
                    <div class="flex items-center justify-between pb-0.5">
                        <p class="text-[11px] font-extrabold text-blue-800 uppercase tracking-wider">📊 LAYER NON-SPASIAL (ATRIBUT)</p>
                        <span class="text-[10px] text-gray-400 font-medium">C4.5 Engine</span>
                    </div>

                    <label @click.stop="toggleLayer('statusC45')" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-emerald-50 cursor-pointer text-xs font-medium text-gray-700 transition-colors">
                        <input type="checkbox" :checked="layerState.statusC45" class="rounded text-emerald-600 focus:ring-emerald-500 pointer-events-none">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block shadow-sm"></span>
                        <span>✅ Pop-Up Status & Atribut C4.5</span>
                    </label>
                </div>
            </div>

            {{-- Search Input --}}
            <div class="flex items-center gap-1.5 border border-gray-200 rounded-xl px-3 py-1.5 bg-white shadow-sm">
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input class="text-xs text-gray-700 outline-none w-24 sm:w-32 placeholder-gray-400"
                       placeholder="Cari lokasi..." x-model="searchText" id="input-search-lokasi" />
            </div>

            {{-- Toggle panel (mobile) --}}
            <button @click="showPanel = !showPanel"
                    class="lg:hidden flex items-center gap-1 text-xs font-medium text-gray-600 border border-gray-200 rounded-xl px-2.5 py-1.5 hover:bg-gray-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                <span x-text="showPanel ? 'Peta' : 'Info'"></span>
            </button>
        </div>
    </div>

    {{-- Content: Panel + Map --}}
    <div class="flex flex-col lg:flex-row flex-1 min-h-0 overflow-hidden">

        {{-- Info Panel --}}
        <div :class="showPanel ? 'flex' : 'hidden'" class="lg:flex w-full lg:w-80 shrink-0 bg-white border-b lg:border-b-0 lg:border-r border-gray-200 flex-col overflow-y-auto max-h-72 lg:max-h-none"
             id="panel-info-klasifikasi">

            <div class="p-4 border-b border-gray-100" x-show="selected" x-cloak>
                {{-- Selected location header --}}
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Lokasi Dipilih</p>
                        <h2 class="text-sm font-bold text-gray-800 leading-tight" x-text="selected?.name" id="selected-location-name"></h2>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="selected?.kecamatan"></p>
                    </div>
                    <template x-if="selected?.status === 'layak'">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                        </svg>
                    </template>
                    <template x-if="selected?.status !== 'layak'">
                        <svg class="w-5 h-5 text-red-500 shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
                        </svg>
                    </template>
                </div>

                {{-- Status Card --}}
                <div class="rounded-xl px-4 py-3 mb-3"
                     :class="selected?.status === 'layak' ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200'"
                     id="status-klasifikasi-card">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-0.5">Status C4.5</p>
                    <p class="text-xl font-extrabold tracking-wide"
                       :class="selected?.status === 'layak' ? 'text-emerald-700' : 'text-red-600'"
                       x-text="selected?.status === 'layak' ? '✓ LAYAK' : '✗ TIDAK LAYAK'"></p>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="'Confidence: ' + selected?.confidence" id="confidence-score"></p>
                </div>

                {{-- GPS Coordinates --}}
                <div class="bg-gray-50 rounded-xl p-3 mb-3" id="info-koordinat">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Koordinat GPS</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <p class="text-xs text-gray-400">Latitude</p>
                            <p class="text-sm font-mono font-bold text-gray-700" x-text="selected?.lat + '°'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Longitude</p>
                            <p class="text-sm font-mono font-bold text-gray-700" x-text="selected?.lng + '°'"></p>
                        </div>
                    </div>
                </div>

                {{-- Criteria values --}}
                <div class="mb-3" id="info-kriteria">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Nilai Kriteria</p>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Kepadatan Penduduk</span>
                            <span class="text-xs font-semibold text-gray-700 bg-gray-100 rounded-full px-2 py-0.5" x-text="selected?.kepadatan"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Jarak Permukiman</span>
                            <span class="text-xs font-semibold text-gray-700 bg-gray-100 rounded-full px-2 py-0.5" x-text="selected?.jarak_permukiman?.split(' ')[0]"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Jarak Sumber Air</span>
                            <span class="text-xs font-semibold text-gray-700 bg-gray-100 rounded-full px-2 py-0.5" x-text="selected?.jarak_air?.split(' ')[0]"></span>
                        </div>
                    </div>
                </div>

                {{-- Decision Tree Rule --}}
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3" id="panel-decision-tree">
                    <div class="flex items-center gap-1.5 mb-2">
                        <svg class="w-[13px] h-[13px] text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/>
                            <circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>
                        </svg>
                        <p class="text-xs font-bold text-blue-700 uppercase tracking-wide">Aturan Pohon Keputusan</p>
                    </div>
                    {{-- Decision tree steps rendered via JS --}}
                    <div id="decision-tree-render"></div>
                </div>
            </div>

            <div class="p-5 border-b border-gray-100" x-show="!selected" x-cloak>
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border-2 border-dashed border-emerald-200 p-5 text-center space-y-2">
                    <div class="w-10 h-10 rounded-full bg-white text-emerald-600 flex items-center justify-center mx-auto shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wide">Status Klasifikasi Kosong</h3>
                    <p class="text-[11px] text-gray-600 leading-relaxed">
                        Silakan <strong>klik salah satu Pin Lokasi TPS pada peta</strong> atau pilih dari daftar lokasi di bawah ini untuk melihat rincian status hasil klasifikasi C4.5.
                    </p>
                </div>
            </div>

            {{-- Location list --}}
            <div class="flex-1 p-4 min-h-0 overflow-y-auto" id="list-lokasi">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    Semua Lokasi (<span x-text="filtered.length"></span>)
                </p>
                <div class="space-y-1.5">
                    <template x-for="loc in filtered" :key="loc.id">
                        <button @click="selectedId = loc.id; showPanel = false; selectMarkerOnMap(loc.id);"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-left transition-all"
                                :class="selectedId === loc.id ? 'bg-emerald-50 border border-emerald-200 shadow-sm' : 'hover:bg-gray-50 border border-transparent'">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0"
                                 :class="loc.status === 'layak' ? 'bg-emerald-100' : 'bg-red-100'">
                                <svg class="w-3 h-3" :class="loc.status === 'layak' ? 'text-emerald-600' : 'text-red-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-gray-700 truncate" x-text="loc.name"></p>
                                <p class="text-xs text-gray-400 truncate" x-text="loc.kecamatan"></p>
                            </div>
                            <span class="text-xs font-bold shrink-0 px-1.5 py-0.5 rounded-full"
                                  :class="loc.status === 'layak' ? 'text-emerald-700 bg-emerald-100' : 'text-red-600 bg-red-100'"
                                  x-text="loc.status === 'layak' ? 'Layak' : 'TL'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Map Area --}}
        <div :class="showPanel ? 'hidden' : 'flex'" class="lg:flex flex-1 relative overflow-hidden">
            <div id="map-container" class="w-full h-full z-0"></div>

            {{-- Legend --}}
            <div class="absolute bottom-3 left-3 bg-white bg-opacity-97 rounded-xl px-3.5 py-2.5 shadow-md border border-gray-200 z-[400]">
                <p class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Keterangan Legend</p>
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-emerald-600 border border-white shadow-sm"></div><span class="text-xs text-gray-600 font-medium">TPS 3R (Layak)</span></div>
                    <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-sky-600 border border-white shadow-sm"></div><span class="text-xs text-gray-600 font-medium">Biodigester</span></div>
                    <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-amber-600 border border-white shadow-sm"></div><span class="text-xs text-gray-600 font-medium">Bank Sampah</span></div>
                    <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-red-600 border border-white shadow-sm"></div><span class="text-xs text-gray-600 font-medium">Tidak Layak</span></div>
                    <div class="border-t border-gray-100 my-0.5"></div>
                    <div class="flex items-center gap-2"><div class="w-4 h-1.5 bg-sky-500 rounded"></div><span class="text-xs text-gray-600">Vektor Sungai</span></div>
                    <div class="flex items-center gap-2"><div class="w-4 h-1.5 bg-amber-500 rounded"></div><span class="text-xs text-gray-600">Vektor Permukiman</span></div>
                </div>
            </div>

            {{-- Mobile floating info --}}
            <button @click="showPanel = true"
                    x-show="selected" x-cloak
                    class="lg:hidden absolute bottom-3 left-1/2 -translate-x-1/2 bg-white rounded-xl shadow-lg border border-gray-200 px-4 py-2 flex items-center gap-2 z-[1000]">
                <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                     :class="selected?.status === 'layak' ? 'bg-emerald-100' : 'bg-red-100'">
                    <svg class="w-[11px] h-[11px]" :class="selected?.status === 'layak' ? 'text-emerald-600' : 'text-red-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-gray-700" x-text="selected?.name"></span>
                <span class="text-xs font-bold px-1.5 py-0.5 rounded-full"
                      :class="selected?.status === 'layak' ? 'text-emerald-700 bg-emerald-100' : 'text-red-600 bg-red-100'"
                      x-text="selected?.status === 'layak' ? 'Layak' : 'TL'"></span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    const locations = window.appLocationsData || [];
    const markers = {};

    // ============================================
    // 1. BASE MAP TILE LAYERS
    // ============================================
    const baseOpenStreetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    });

    const baseGoogleSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri World Imagery Satellite',
        maxZoom: 18,
    });

    // Inisialisasi Peta Leaflet dengan Default OpenStreetMap Tile Layer
    const map = L.map('map-container', {
        center: [-6.8900, 107.2400],
        zoom: 11,
        layers: [baseOpenStreetMap],
        zoomControl: true,
        scrollWheelZoom: true,
    });

    // Bridge Function Switch Base Map via Header Dropdown
    window.switchLeafletBaseMap = function(type) {
        if (type === 'satellite') {
            map.removeLayer(baseOpenStreetMap);
            map.addLayer(baseGoogleSatellite);
        } else {
            map.removeLayer(baseGoogleSatellite);
            map.addLayer(baseOpenStreetMap);
        }
    };

    // ============================================
    // 2. LAYER GROUP SPASIAL (SHP & GEOMETRI)
    // ============================================
    const layerTitikTPS = L.layerGroup().addTo(map);          // Pin Marker Point TPS
    const layerPendudukBps = L.layerGroup().addTo(map);       // Poligon Wilayah Kecamatan BPS (TANPA RADIUS CIRCLE)
    const layerSungai = L.layerGroup().addTo(map);            // Vektor Line Sungai / Sumber Air
    const layerJarakPermukiman = L.layerGroup().addTo(map);   // Vektor Line Garis Jarak Permukiman (TERPISAH)
    const layerJarakSungai = L.layerGroup().addTo(map);       // Vektor Line Garis Jarak Sungai (TERPISAH)

    // ============================================
    // 3. LAYER GROUP NON-SPASIAL (ATRIBUT & C4.5)
    // ============================================
    const layerStatusC45 = L.layerGroup().addTo(map);         // Pop-Up Status & Decision Tree C4.5

    // Custom marker icon function
    function createIcon(status, jenisFas, isSelected) {
        let color = status === 'layak' ? '#16a34a' : '#dc2626';
        if (jenisFas === 'Biodigester') color = '#0284c7';
        if (jenisFas === 'Bank Sampah') color = '#d97706';

        const size = isSelected ? 32 : 24;
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="
                width: ${size}px; height: ${size}px; border-radius: 50% 50% 50% 0;
                background: ${color}; border: 2px solid white;
                box-shadow: 0 2px 8px rgba(0,0,0,${isSelected ? 0.5 : 0.3});
                transform: rotate(-45deg);
                display: flex; align-items: center; justify-content: center;
                ${isSelected ? 'z-index: 1000;' : ''}
            "><div style="width: ${isSelected ? 10 : 8}px; height: ${isSelected ? 10 : 8}px; background: white; border-radius: 50%; transform: rotate(45deg);"></div></div>`,
            iconSize: [size, size],
            iconAnchor: [size/2, size],
            popupAnchor: [0, -size],
        });
    }

    // Populate Location Markers into Layer Group Titik TPS
    locations.forEach(function(loc) {
        const isSelected = (locations.length > 0 && locations[0].id === loc.id);
        const marker = L.marker([loc.lat, loc.lng], { icon: createIcon(loc.status, loc.jenis_fasilitas, isSelected) });
        const statusLabel = loc.status === 'layak' ? '✓ Layak' : '✗ Tidak Layak';
        const statusColor = loc.status === 'layak' ? '#16a34a' : '#dc2626';

        marker.bindPopup(`
            <div style="font-family: Inter, sans-serif; min-width: 210px;">
                <span style="font-size: 10px; background: ${loc.source === 'historis' ? '#f0fdf4' : '#e0f2fe'}; color: ${loc.source === 'historis' ? '#15803d' : '#0369a1'}; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                    ${loc.source === 'historis' ? 'Data Historis DLH' : 'Hasil Prediksi C4.5'}
                </span>
                <h4 style="font-weight: 700; margin: 6px 0 2px; font-size: 13px;">${loc.name}</h4>
                <p style="color: #4b5563; font-size: 11px; margin: 0 0 2px;">Kecamatan: <strong>${loc.kecamatan}</strong></p>
                <p style="color: ${statusColor}; font-weight: 800; font-size: 12px; margin: 4px 0 2px;">Status: ${statusLabel}</p>
                ${loc.rule_id ? '<p style="color: #4338ca; font-size: 11px; margin: 2px 0; font-weight: 600;">' + loc.rule_id + '</p>' : ''}
                ${loc.rule_detail ? '<p style="color: #6b7280; font-size: 10px; margin: 2px 0; line-height: 1.4;">' + loc.rule_detail + '</p>' : ''}
                ${loc.predicted_at ? '<p style="color: #9ca3af; font-size: 10px; margin: 4px 0 0;">📅 ' + new Date(loc.predicted_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'}) + '</p>' : ''}
            </div>
        `);
        marker.on('click', function() {
            const el = document.getElementById('page-hasil-klasifikasi');
            if (el && el._x_dataStack) {
                el._x_dataStack[0].selectedId = loc.id;
                el._x_dataStack[0].showPanel = true;
            }
            updateDecisionTree(loc);
            highlightMarker(loc.id);
        });

        layerTitikTPS.addLayer(marker);
        layerStatusC45.addLayer(marker);
        markers[loc.id] = marker;
    });

    function highlightMarker(id) {
        locations.forEach(function(loc) {
            if (markers[loc.id]) {
                markers[loc.id].setIcon(createIcon(loc.status, loc.jenis_fasilitas, loc.id === id));
            }
        });
    }

    window.selectMarkerOnMap = function(id) {
        const loc = locations.find(l => l.id === id);
        if (loc) {
            map.setView([loc.lat, loc.lng], 14);
            highlightMarker(id);
            if (markers[id]) markers[id].openPopup();
            updateDecisionTree(loc);
        }
    };

    function updateDecisionTree(loc) {
        const container = document.getElementById('decision-tree-render');
        if (!container || !loc || !loc.rule) return;
        const steps = loc.rule.split(' → ');
        container.innerHTML = steps.map((step, i) => {
            const isLast = i === steps.length - 1;
            const color = isLast ? (loc.status === 'layak' ? 'font-bold text-emerald-700' : 'font-bold text-red-600') : 'text-gray-600';
            const dotBg = isLast ? (loc.status === 'layak' ? 'bg-emerald-600 border-emerald-600' : 'bg-red-600 border-red-600') : 'bg-white border-gray-300';
            const innerDot = isLast ? 'bg-white' : 'bg-gray-400';
            const line = !isLast ? '<div class="w-0.5 h-3 bg-gray-200 mt-0.5"></div>' : '';
            return `
                <div class="flex items-start gap-2">
                    <div class="flex flex-col items-center mt-0.5">
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 ${dotBg}">
                            <div class="w-1.5 h-1.5 rounded-full ${innerDot}"></div>
                        </div>
                        ${line}
                    </div>
                    <span class="text-xs leading-relaxed ${color}">${step}</span>
                </div>
            `;
        }).join('');
    }

    if (locations.length > 0) {
        updateDecisionTree(locations[0]);
    }

    // Dynamic layer visibility bridge function connected exclusively to Top Bar Dropdown
    window.setMapLayerVisibility = function(layerName, isVisible) {
        const layerMap = {
            'titikTps': layerTitikTPS,
            'pendudukBps': layerPendudukBps,
            'sungai': layerSungai,
            'jarakPermukiman': layerJarakPermukiman,
            'jarakSungai': layerJarakSungai,
            'statusC45': layerStatusC45
        };
        const group = layerMap[layerName];
        if (group) {
            if (isVisible) {
                map.addLayer(group);
            } else {
                map.removeLayer(group);
            }
        }
    };

    // ============================================
    // 4. HIT REST API QGIS SERVER (Render SHP Geometri Spasial Terpisah)
    // ============================================
    fetch('/api/qgis/klasifikasi-geojson')
        .then(res => res.json())
        .then(geoJson => {
            geoJson.features.forEach(feature => {
                const props = feature.properties;
                const coords = feature.geometry.coordinates;
                const latlng = [coords[1], coords[0]];

                // A. Layer 1: Layer Sungai / Sumber Air (Line Vektor Aliran Sungai)
                const lineSungai = L.polyline([
                    [latlng[0] - 0.006, latlng[1] - 0.008],
                    latlng,
                    [latlng[0] + 0.007, latlng[1] + 0.009]
                ], { color: '#0284c7', weight: 3 });
                lineSungai.bindPopup(`<span style="font-size:11px;">🌊 Vektor Sungai / Sumber Air (${props.kecamatan})</span>`);
                layerSungai.addLayer(lineSungai);

                // B. Layer 2: Layer Jarak Permukiman (Line Vektor TERPISAH)
                const linePermukiman = L.polyline([
                    latlng,
                    [latlng[0] + 0.002, latlng[1] + 0.003]
                ], { color: '#f59e0b', weight: 2, dashArray: '4, 4' });
                linePermukiman.bindPopup(`<span style="font-size:11px;">🏠 Garis Jarak Permukiman: <strong>${props.jarak_permukiman}</strong></span>`);
                layerJarakPermukiman.addLayer(linePermukiman);

                // C. Layer 3: Layer Jarak Sungai / Air (Line Vektor TERPISAH)
                const lineJarakAir = L.polyline([
                    latlng,
                    [latlng[0] - 0.003, latlng[1] - 0.004]
                ], { color: '#06b6d4', weight: 2, dashArray: '2, 4' });
                lineJarakAir.bindPopup(`<span style="font-size:11px;">💧 Garis Jarak Sumber Air: <strong>${props.jarak_air}</strong></span>`);
                layerJarakSungai.addLayer(lineJarakAir);

                // D. Layer 4: Layer Penduduk BPS (Poligon Wilayah Kecamatan - TANPA RADIUS DISTANCE CIRCLE)
                let polyColor = props.kepadatan === 'Tinggi' ? '#ef4444' : (props.kepadatan === 'Sedang' ? '#f59e0b' : '#10b981');
                const polyKec = L.rectangle([
                    [latlng[0] - 0.005, latlng[1] - 0.005],
                    [latlng[0] + 0.005, latlng[1] + 0.005]
                ], {
                    color: polyColor,
                    weight: 1.5,
                    fillColor: polyColor,
                    fillOpacity: 0.15
                });
                polyKec.bindPopup(`<span style="font-size:11px;">👥 Wilayah BPS ${props.kecamatan}: Kepadatan <strong style="color:${polyColor}">${props.kepadatan}</strong></span>`);
                layerPendudukBps.addLayer(polyKec);
            });
        })
        .catch(err => console.error('QGIS API Fetch Error:', err));

    // Dilarang menampilkan box layer bawaan Leaflet melayang di atas peta.
    // Seluruh kontrol layer dikelola penuh oleh tombol "Pilihan Layer" di header bar.
});
</script>
@endsection
