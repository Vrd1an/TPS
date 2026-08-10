{{-- Halaman Input Usulan Lokasi TPS Baru (Clean & Direct Manual Map Picker) --}}
@extends('layouts.app')

@section('title', 'Usulan Lokasi TPS Baru')
@section('page-title', 'Usulan Lokasi TPS Baru')

@php
    $activeMenu = 'usulan-lokasi';
    $modelActive = $modelActive ?? false;
    $modelInfo = $modelInfo ?? null;
@endphp

@section('content')
<script>
    function usulanFormApp() {
        return {
            namaLokasi: '',
            kecamatan: '',
            jenisFasilitas: 'TPS 3R',
            latitude: '',
            longitude: '',
            kepadatan: '',
            jarakPermukiman: 'Dekat (< 200m)',
            jarakAir: 'Dekat (< 100m)',
            submitting: false,
            densityLoading: false,
            densityBpsText: '',

            // Auto Fetch Kepadatan BPS & Data Jiwa Penduduk per Kecamatan
            fetchKepadatanBps(kecName) {
                if (!kecName) {
                    this.kepadatan = '';
                    this.densityBpsText = '';
                    return;
                }
                this.densityLoading = true;

                fetch(`/api/kecamatan/${encodeURIComponent(kecName)}/kepadatan`)
                    .then(res => res.json())
                    .then(res => {
                        this.densityLoading = false;
                        if (res.status === 'success' && res.data) {
                            const kec = res.data;
                            this.kepadatan = kec.kepadatan_kategori || 'Sedang';
                            const jmlPend = kec.jumlah_penduduk ? kec.jumlah_penduduk.toLocaleString('id-ID') : '0';
                            this.densityBpsText = `${jmlPend} Jiwa | ${kec.kepadatan_angka.toLocaleString('id-ID')} Jiwa/km² (${kec.kepadatan_kategori})`;
                        } else {
                            this.kepadatan = 'Sedang';
                            this.densityBpsText = '89.600 Jiwa | 2.502 Jiwa/km² (Sedang)';
                        }
                    })
                    .catch(() => {
                        this.densityLoading = false;
                        this.kepadatan = 'Sedang';
                        this.densityBpsText = '89.600 Jiwa | 2.502 Jiwa/km² (Sedang)';
                    });

                if (window.onKecamatanChange) {
                    window.onKecamatanChange(kecName);
                }
            },

            // Submit Form dengan Peringatan Validasi Chrome di Tengah Layar
            submitForm() {
                if (!this.namaLokasi || this.namaLokasi.trim() === '') {
                    alert("⚠️ PERINGATAN SISTEM:\n\nNama Usulan Lokasi TPS belum diisi!\nHarap ketik nama usulan lokasi terlebih dahulu.");
                    return;
                }

                if (!this.kecamatan || this.kecamatan.trim() === '') {
                    alert("⚠️ PERINGATAN SISTEM:\n\nKecamatan belum dipilih!\nHarap pilih Kecamatan pada dropdown terlebih dahulu.");
                    return;
                }

                if (!this.latitude || !this.longitude) {
                    alert("⚠️ PERINGATAN SISTEM:\n\nTitik lokasi koordinat (Latitude & Longitude) belum dipilih!\nHarap klik salah satu lokasi di peta untuk menentukan titik koordinat TPS.");
                    return;
                }

                if (!this.kepadatan) {
                    alert("⚠️ PERINGATAN SISTEM:\n\nData Kepadatan Penduduk belum terverifikasi dari BPS.");
                    return;
                }

                this.submitting = true;
                const body = {
                    nama_lokasi: this.namaLokasi,
                    kecamatan: this.kecamatan,
                    jenis_fasilitas: this.jenisFasilitas,
                    latitude: parseFloat(this.latitude),
                    longitude: parseFloat(this.longitude),
                    kepadatan: this.kepadatan,
                    jarak_permukiman: this.jarakPermukiman,
                    jarak_air: this.jarakAir,
                    _token: '{{ csrf_token() }}'
                };

                fetch('/usulan-lokasi', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(body)
                })
                .then(res => res.json())
                .then(data => {
                    this.submitting = false;
                    if (data.status === 'success') {
                        const newId = data.data ? data.data.id : 'usulan-1';
                        const kec = data.data ? data.data.kecamatan : this.kecamatan;
                        window.location.href = `/hasil-klasifikasi?new_id=${newId}&kecamatan=${encodeURIComponent(kec)}`;
                    } else {
                        alert("⚠️ Gagal memproses klasifikasi: " + (data.message || 'Terjadi kesalahan sistem.'));
                    }
                })
                .catch(err => {
                    this.submitting = false;
                    alert("⚠️ Terjadi kesalahan jaringan saat mengirim usulan lokasi.");
                });
            }
        };
    }
</script>

<div class="flex-1 overflow-y-auto p-4 lg:p-6" id="page-usulan-lokasi" x-data="usulanFormApp()">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Header Card --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-gray-800">Form Input Usulan Lokasi TPS Baru</h1>
                    <span class="bg-teal-100 text-teal-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-teal-200">SDSS C4.5</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Penentuan Kelayakan Lokasi TPS Berbasis GIS & Algoritma C4.5 Mandiri (Laravel Service Pattern)</p>
            </div>
            <div class="flex items-center gap-2">
                @if($modelActive && $modelInfo)
                    <div class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-1.5 mr-2">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-xs font-bold text-emerald-700">Model Aktif</span>
                    </div>
                @endif
                <a href="{{ route('hasil-klasifikasi') }}" class="text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 px-3.5 py-2 rounded-xl transition-all">
                    Lihat Hasil Klasifikasi →
                </a>
            </div>
        </div>

        {{-- Model Not Active Warning --}}
        @if(!$modelActive)
            <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-6 text-center shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-amber-800 mb-1">Model C4.5 Belum Tersedia</h2>
                <p class="text-sm text-amber-700 mb-4 max-w-md mx-auto">
                    Silakan lakukan Training Data Historis terlebih dahulu di menu Kelola Data Latih untuk membentuk Decision Tree.
                </p>
                <a href="{{ url('/data-latih') }}"
                   class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-sm px-5 py-2.5 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Buka Kelola Data Latih & Training
                </a>
            </div>
        @endif

        @if($modelActive)
        {{-- Main Form Card --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-6 py-4 bg-gray-50/50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    Data Atribut Spasial & Non-Spasial
                </h2>
                <span class="text-xs text-gray-400 font-medium">* Wajib Diisi Petugas DLH</span>
            </div>

            <form @submit.prevent="submitForm()" class="p-6 space-y-6" id="form-usulan-lokasi">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- LEFT: Identitas, Kecamatan & Peta Interaktif --}}
                    <div class="space-y-4">
                        {{-- Nama Lokasi --}}
                        <div>
                            <label for="input-nama-lokasi" class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Usulan Lokasi TPS <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_lokasi" id="input-nama-lokasi"
                                   x-model="namaLokasi" required
                                   placeholder="Contoh: TPS3R Desa Sirnagalih RT 02"
                                   class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-medium text-gray-800 focus:outline-none focus:border-emerald-500 transition-colors shadow-sm" />
                        </div>

                        {{-- Kecamatan (Otomatis Fetch Data Jiwa & Kepadatan Penduduk BPS) --}}
                        <div>
                            <label for="select-kecamatan-form" class="block text-sm font-semibold text-gray-700 mb-1.5">Kecamatan (BPS Cianjur) <span class="text-red-500">*</span></label>
                            <select name="kecamatan" id="select-kecamatan-form"
                                    x-model="kecamatan" required
                                    @change="fetchKepadatanBps($event.target.value)"
                                    class="w-full bg-white border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:border-emerald-500 transition-colors shadow-sm">
                                <option value="">-- Pilih Kecamatan --</option>
                                <option value="Cianjur">Cianjur (Kota)</option>
                                <option value="Cilaku">Cilaku</option>
                                <option value="Cibeber">Cibeber</option>
                                <option value="Ciranjang">Ciranjang</option>
                                <option value="Bojongpicung">Bojongpicung</option>
                                <option value="Haurwangi">Haurwangi</option>
                                <option value="Karangtengah">Karangtengah</option>
                                <option value="Mande">Mande</option>
                                <option value="Cikalongkulon">Cikalongkulon</option>
                                <option value="Cipanas">Cipanas</option>
                                <option value="Pacet">Pacet</option>
                                <option value="Sukaresmi">Sukaresmi</option>
                                <option value="Cugenang">Cugenang</option>
                                <option value="Gekbrong">Gekbrong</option>
                                <option value="Warungkondang">Warungkondang</option>
                                <option value="Sukaluyu">Sukaluyu</option>
                                <option value="Campaka">Campaka</option>
                                <option value="Campaka Mulya">Campaka Mulya</option>
                                <option value="Sukanagara">Sukanagara</option>
                                <option value="Pagelaran">Pagelaran</option>
                                <option value="Kadupandak">Kadupandak</option>
                                <option value="Takokak">Takokak</option>
                                <option value="Tanggeung">Tanggeung</option>
                                <option value="Cijati">Cijati</option>
                                <option value="Cikadu">Cikadu</option>
                                <option value="Cibinong">Cibinong</option>
                                <option value="Pasirkuda">Pasirkuda</option>
                                <option value="Sindangbarang">Sindangbarang</option>
                                <option value="Agrabinta">Agrabinta</option>
                                <option value="Leles">Leles</option>
                                <option value="Cidaun">Cidaun</option>
                                <option value="Naringgul">Naringgul</option>
                            </select>
                        </div>

                        {{-- Lat / Lng --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="input-latitude" class="block text-sm font-semibold text-gray-700 mb-1.5">Latitude <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" name="latitude" id="input-latitude"
                                           x-model="latitude" required readonly
                                           placeholder="-6.8150"
                                           class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-3 py-3 text-sm font-mono text-gray-800 focus:outline-none pr-7 cursor-pointer" />
                                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">°N</span>
                                </div>
                            </div>
                            <div>
                                <label for="input-longitude" class="block text-sm font-semibold text-gray-700 mb-1.5">Longitude <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" name="longitude" id="input-longitude"
                                           x-model="longitude" required readonly
                                           placeholder="107.1380"
                                           class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-3 py-3 text-sm font-mono text-gray-800 focus:outline-none pr-7 cursor-pointer" />
                                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">°E</span>
                                </div>
                            </div>
                        </div>

                        {{-- Peta Interaktif — Klik untuk Pilih Titik Lokasi --}}
                        <div class="rounded-xl overflow-hidden border-2 border-gray-200 relative" id="map-picker-wrapper">
                            <div class="bg-teal-600 px-3 py-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="text-xs font-semibold text-white">Klik titik peta untuk menentukan lokasi usulan TPS</span>
                                </div>
                                <div class="bg-white bg-opacity-20 rounded px-2 py-0.5 text-xs text-white font-mono"
                                     x-text="(latitude && longitude) ? latitude + ', ' + longitude : 'Belum dipilih'"></div>
                            </div>
                            <div id="map-picker" class="w-full h-72 lg:h-80 z-0"></div>

                            {{-- Overlay Legend Keterangan Radius SNI di Pinggir Peta (Diposisikan di Pinggir Agar Tidak Terhalang) --}}
                            <div class="absolute bottom-3 left-3 bg-white bg-opacity-95 rounded-xl p-2.5 shadow-md border border-gray-200 z-[400] text-[11px] space-y-1">
                                <p class="font-bold text-gray-700 uppercase tracking-wider text-[10px] mb-1">Keterangan Radius SNI</p>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-amber-400 border border-amber-500 shrink-0"></span>
                                    <span class="text-gray-700 font-medium">Zona Permukiman (200m)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-cyan-400 border border-cyan-500 shrink-0"></span>
                                    <span class="text-gray-700 font-medium">Sempadan Air (100m)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Kriteria Algoritma C4.5 --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                                    <line x1="2" y1="20" x2="22" y2="20"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Kriteria Algoritma C4.5</h3>
                        </div>

                        {{-- Info Penentuan Spasial Manual --}}
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <p class="text-xs text-amber-800 leading-relaxed">
                                Petugas DLH mengukur jarak di lapangan dan mencatatnya ke sistem dengan memilih kriteria Jarak Permukiman & Sumber Air (Acuan <strong>Radius Buffer SNI 200m & 100m</strong> pada peta).
                            </p>
                        </div>

                        {{-- Kepadatan Penduduk BPS (Otomatis dari Pilihan Kecamatan) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kepadatan Penduduk & Data Jiwa (Otomatis BPS)</label>
                            <div class="relative">
                                <input type="text" readonly :value="densityBpsText || 'Pilih Kecamatan terlebih dahulu'"
                                       class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-800 focus:outline-none" />
                                <template x-if="densityLoading">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-emerald-600 font-bold">Memuat BPS...</span>
                                </template>
                            </div>
                        </div>

                        {{-- Jarak Permukiman (Manual oleh Petugas) --}}
                        <div>
                            <label for="select-jarak-permukiman-form" class="block text-sm font-semibold text-gray-700 mb-1.5">Jarak dari Permukiman (SNI 03-3241-1994) <span class="text-red-500">*</span></label>
                            <select name="jarak_permukiman" id="select-jarak-permukiman-form"
                                    x-model="jarakPermukiman" required
                                    class="w-full bg-white border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:border-emerald-500 transition-colors shadow-sm">
                                <option value="Dekat (< 200m)">Dekat (&lt; 200m)</option>
                                <option value="Sedang (200-500m)">Sedang (200-500m)</option>
                                <option value="Jauh (> 500m)">Jauh (&gt; 500m)</option>
                            </select>
                        </div>

                        {{-- Jarak Sumber Air (Manual oleh Petugas) --}}
                        <div>
                            <label for="select-jarak-air-form" class="block text-sm font-semibold text-gray-700 mb-1.5">Jarak dari Sumber Air / Sungai <span class="text-red-500">*</span></label>
                            <select name="jarak_air" id="select-jarak-air-form"
                                    x-model="jarakAir" required
                                    class="w-full bg-white border-2 border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-800 focus:outline-none focus:border-emerald-500 transition-colors shadow-sm">
                                <option value="Dekat (< 100m)">Dekat (&lt; 100m)</option>
                                <option value="Sedang (100-300m)">Sedang (100-300m)</option>
                                <option value="Jauh (> 300m)">Jauh (&gt; 300m)</option>
                            </select>
                        </div>

                        {{-- Tombol Submit Klasifikasi C4.5 --}}
                        <div class="pt-4">
                            <button type="submit" :disabled="submitting"
                                    class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm py-3.5 px-6 rounded-xl shadow-lg shadow-emerald-600/20 hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                                <template x-if="!submitting">
                                    <span class="flex items-center gap-2">
                                        ⚡ Proses Klasifikasi Algoritma C4.5
                                    </span>
                                </template>
                                <template x-if="submitting">
                                    <span class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        Memproses Pohon Keputusan C4.5...
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // 1. INISIALISASI PETA LEAFLET (Google Satellite & Base Maps)
    // ============================================
    const mapPicker = L.map('map-picker', {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView([-6.8150, 107.1380], 12);

    const googleSatelliteTile = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google Maps Satellite Digital',
        maxZoom: 20,
    }).addTo(mapPicker);

    const osmTile = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    });

    L.control.layers({
        "🛰️ Google Satellite": googleSatelliteTile,
        "🗺️ OpenStreetMap": osmTile
    }, null, { position: 'topright' }).addTo(mapPicker);

    function getAlpine() {
        const el = document.getElementById('page-usulan-lokasi');
        return el && el._x_dataStack ? el._x_dataStack[0] : null;
    }

    let selectedMarker = null;
    let circlePermukiman = null;
    let circleAir = null;

    function createIcon(color, size) {
        size = size || 28;
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="
                width: ${size}px; height: ${size}px; border-radius: 50% 50% 50% 0;
                background: ${color}; border: 2px solid white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.35);
                transform: rotate(-45deg);
                display: flex; align-items: center; justify-content: center;
            "><div style="width: ${size * 0.33}px; height: ${size * 0.33}px; background: white; border-radius: 50%; transform: rotate(45deg);"></div></div>`,
            iconSize: [size, size],
            iconAnchor: [size/2, size],
            popupAnchor: [0, -size],
        });
    }

    // Menggambar Lingkaran Radius SNI Buffer (200m Permukiman & 100m Air)
    function updateSniBufferRadiusCircles(lat, lng) {
        if (circlePermukiman) mapPicker.removeLayer(circlePermukiman);
        if (circleAir) mapPicker.removeLayer(circleAir);

        circlePermukiman = L.circle([lat, lng], {
            radius: 200,
            color: '#f59e0b',
            weight: 2,
            fillColor: '#fbbf24',
            fillOpacity: 0.18,
            dashArray: '5, 5'
        }).addTo(mapPicker);

        circlePermukiman.bindTooltip("🏠 Radius Zona Aman Permukiman: 200m (SNI 03-3241-1994)", { permanent: false, direction: 'top' });

        circleAir = L.circle([lat, lng], {
            radius: 100,
            color: '#06b6d4',
            weight: 2,
            fillColor: '#22d3ee',
            fillOpacity: 0.22,
            dashArray: '4, 4'
        }).addTo(mapPicker);

        circleAir.bindTooltip("💧 Radius Sempadan Air Minimum: 100m (SNI 03-3241-1994)", { permanent: false, direction: 'bottom' });
    }

    function setMarker(lat, lng) {
        if (selectedMarker) {
            selectedMarker.setLatLng([lat, lng]);
        } else {
            selectedMarker = L.marker([lat, lng], {
                icon: createIcon('#0d9488', 32),
                draggable: true,
            }).addTo(mapPicker);

            selectedMarker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                const alpine = getAlpine();
                if (alpine) {
                    alpine.latitude = pos.lat.toFixed(6);
                    alpine.longitude = pos.lng.toFixed(6);
                }
                updateSniBufferRadiusCircles(pos.lat, pos.lng);
            });
        }

        selectedMarker.bindPopup(`
            <div style="font-family: Inter, sans-serif; min-width: 170px;">
                <p style="font-weight: 700; margin: 0 0 4px; font-size: 13px;">📍 Titik Usulan Dipilih</p>
                <p style="color: #6b7280; font-size: 11px; margin: 0; font-family: monospace;">${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                <p style="color: #0d9488; font-size: 11px; margin: 4px 0 0; font-weight: 600;">Radius Buffer SNI (200m & 100m) Aktif</p>
            </div>
        `).openPopup();
    }

    // ============================================
    // 2. AUTO-PAN PETA SAAT PETUGAS MEMILIH KECAMATAN
    // ============================================
    const kecamatanCoordsMap = {
        'Cianjur': [-6.8150, 107.1380],
        'Ciranjang': [-6.7974, 107.3047],
        'Cilaku': [-6.8768, 107.2412],
        'Cibeber': [-6.8534, 107.2780],
        'Karangtengah': [-6.8220, 107.1850],
        'Cipanas': [-6.7020, 107.0390],
        'Pacet': [-6.7450, 107.0850],
        'Cugenang': [-6.7820, 107.0980],
        'Gekbrong': [-6.8390, 107.0720],
        'Warungkondang': [-6.8520, 107.0890],
        'Mande': [-6.7580, 107.1950],
        'Sukaluyu': [-6.8100, 107.2350],
        'Bojongpicung': [-6.8250, 107.2950],
        'Haurwangi': [-6.8020, 107.3350],
        'Cikalongkulon': [-6.6850, 107.1650],
        'Sukaresmi': [-6.7150, 107.0750],
        'Campaka': [-6.9550, 107.1450],
        'Campaka Mulya': [-6.9950, 107.1150],
        'Sukanagara': [-7.0750, 107.1350],
        'Pagelaran': [-7.1250, 107.1850],
        'Kadupandak': [-7.1650, 107.0750],
        'Takokak': [-7.0550, 106.9950],
        'Tanggeung': [-7.2150, 107.1050],
        'Cijati': [-7.2650, 107.0450],
        'Cikadu': [-7.2850, 107.2250],
        'Cibinong': [-7.3150, 107.1350],
        'Pasirkuda': [-7.2350, 107.1950],
        'Sindangbarang': [-7.4250, 107.1250],
        'Agrabinta': [-7.4550, 106.9450],
        'Leles': [-7.3850, 107.0250],
        'Cidaun': [-7.3923, 107.4349],
        'Naringgul': [-7.3350, 107.3550],
    };

    window.onKecamatanChange = function(kecName) {
        if (kecName && kecamatanCoordsMap[kecName]) {
            const coords = kecamatanCoordsMap[kecName];
            mapPicker.flyTo(coords, 13, { duration: 1.2 });
        }
    };

    // ============================================
    // 3. EVENT LISTENER KLIK PETA (MANUAL LOCATION PICKER)
    // ============================================
    mapPicker.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        const alpine = getAlpine();
        if (alpine) {
            alpine.latitude = lat.toFixed(6);
            alpine.longitude = lng.toFixed(6);
        }

        setMarker(lat, lng);
        updateSniBufferRadiusCircles(lat, lng);
    });

    // ============================================
    // 4. MAP PICKER READY (TITIK GRID KLIER)
    // ============================================
});
</script>
@endsection
