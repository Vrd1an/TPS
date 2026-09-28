{{-- Halaman Usulan Lokasi & Klasifikasi TPS (Role Admin & Petugas) --}}
@extends('layouts.app')

@section('title', 'Usulan Lokasi & Klasifikasi TPS')
@section('page-title', 'Usulan Lokasi TPS')

@php
    $activeMenu = 'usulan-lokasi';
    $modelActive = $modelActive ?? false;
    $modelInfo = $modelInfo ?? null;
    $usulanList = $usulanList ?? [];
    $counts = $counts ?? ['total' => 0, 'menunggu' => 0, 'layak' => 0, 'tidak_layak' => 0];
    $userRole = session('user_role', 'petugas');
    $userName = session('user_name', ($userRole === 'admin' ? 'Admin Dinas DLH' : 'Petugas Lapangan DLH'));
    $initialTab = request('tab', ($counts['menunggu'] > 0 && $userRole === 'admin') ? 'daftar' : 'input');
@endphp

@section('content')
<script>
    window.rawUsulanList = @json($usulanList);

    function usulanApp() {
        return {
            activeTab: '{{ $initialTab }}', // 'input' or 'daftar'
            userRole: '{{ $userRole }}',
            statusFilter: 'all',
            searchKeyword: '',
            
            // Form Data
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
            directClassify: false,

            // Measurement Tool State
            measureMode: false,
            measurePoints: [],
            measuredDistanceText: '',

            // Classification Modal State (Admin)
            showModalKlasifikasi: false,
            classifying: false,
            currentKlasifikasiData: null,

            // Detail Modal
            showModalDetail: false,
            detailItem: null,

            get filteredList() {
                let list = window.rawUsulanList || [];
                if (this.statusFilter !== 'all') {
                    list = list.filter(item => {
                        if (this.statusFilter === 'menunggu') {
                            return item.status === 'menunggu_klasifikasi' || !item.status;
                        }
                        return item.status === this.statusFilter;
                    });
                }
                if (this.searchKeyword.trim() !== '') {
                    const kw = this.searchKeyword.toLowerCase();
                    list = list.filter(item => 
                        (item.nama && item.nama.toLowerCase().includes(kw)) ||
                        (item.kecamatan && item.kecamatan.toLowerCase().includes(kw)) ||
                        (item.petugas_nama && item.petugas_nama.toLowerCase().includes(kw))
                    );
                }
                return list;
            },

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

            // Submit Form Input Usulan Lokasi
            submitForm(isDirectClassify = false) {
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
                    this.kepadatan = 'Sedang';
                }

                this.submitting = true;
                this.directClassify = isDirectClassify;

                const body = {
                    nama_lokasi: this.namaLokasi,
                    kecamatan: this.kecamatan,
                    jenis_fasilitas: this.jenisFasilitas,
                    latitude: parseFloat(this.latitude),
                    longitude: parseFloat(this.longitude),
                    kepadatan: this.kepadatan,
                    jarak_permukiman: this.jarakPermukiman,
                    jarak_air: this.jarakAir,
                    direct_classify: isDirectClassify,
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
                        if (isDirectClassify) {
                            alert("✅ SUKSES:\n\n" + data.message);
                            window.location.href = `/hasil-klasifikasi?new_id=${data.data.id}&kecamatan=${encodeURIComponent(this.kecamatan)}`;
                        } else {
                            alert("✅ USULAN BERHASIL DISIMPAN:\n\nData usulan lokasi TPS berhasil disimpan dengan status 'Menunggu Klasifikasi'.\nData kini masuk ke daftar usulan untuk diperiksa dan diklasifikasikan oleh Admin DLH.");
                            window.location.href = '/usulan-lokasi?tab=daftar';
                        }
                    } else {
                        alert("⚠️ Gagal menyimpan usulan: " + (data.message || 'Terjadi kesalahan sistem.'));
                    }
                })
                .catch(err => {
                    this.submitting = false;
                    alert("⚠️ Terjadi kesalahan jaringan saat mengirim data usulan.");
                });
            },

            // Admin: Trigger Klasifikasi C4.5 pada Usulan Tertentu
            prosesKlasifikasiAdmin(item) {
                if (!confirm(`Eksekusi klasifikasi Algoritma C4.5 untuk usulan:\n"${item.nama}" (${item.kecamatan})?`)) {
                    return;
                }

                this.classifying = true;
                const id = item.id;

                fetch(`/usulan-lokasi/${id}/klasifikasi`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ _token: '{{ csrf_token() }}' })
                })
                .then(res => res.json())
                .then(res => {
                    this.classifying = false;
                    if (res.status === 'success' && res.data) {
                        this.currentKlasifikasiData = res.data;
                        this.showModalKlasifikasi = true;
                        
                        // Update item in local list
                        const target = window.rawUsulanList.find(u => u.id === id);
                        if (target) {
                            target.status = res.data.status;
                            target.confidence = res.data.confidence;
                            target.rule = res.data.rule;
                            target.rule_id = res.data.rule_id;
                            target.rule_detail = res.data.rule_detail;
                            target.classified_by = res.data.classified_by;
                        }
                    } else {
                        alert("⚠️ Gagal memproses klasifikasi: " + (res.message || 'Terjadi kesalahan.'));
                    }
                })
                .catch(err => {
                    this.classifying = false;
                    alert("⚠️ Terjadi kesalahan jaringan saat mengeksekusi C4.5.");
                });
            },

            // Lihat Detail Usulan
            bukaDetail(item) {
                this.detailItem = item;
                this.showModalDetail = true;
            },

            // Focus lokasi pada peta
            lihatDiPeta(item) {
                this.activeTab = 'input';
                this.$nextTick(() => {
                    if (window.setMarkerAndFly) {
                        window.setMarkerAndFly(item.latitude, item.longitude, item.nama);
                    }
                });
            }
        };
    }
</script>

<div class="flex-1 overflow-y-auto p-4 lg:p-6" id="page-usulan-lokasi" x-data="usulanApp()">
    <div class="max-w-6xl mx-auto space-y-5">

        {{-- Top Header Card --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-extrabold text-gray-800">
                        {{ $userRole === 'admin' ? 'Kelola & Klasifikasi Usulan Lokasi TPS' : 'Input Usulan Lokasi TPS Baru' }}
                    </h1>
                    @if($userRole === 'admin')
                        <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-purple-200">Admin Otoritas C4.5</span>
                    @else
                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-blue-200">Petugas Lapangan DLH</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $userRole === 'admin' 
                        ? 'Verifikasi parameter spasial usulan dan eksekusi klasifikasi Algoritma C4.5 untuk menentukan kelayakan lokasi TPS.' 
                        : 'Penentuan titik lokasi koordinat, pengukuran jarak permukiman & sumber air SNI, serta pengajuan usulan lokasi TPS.' }}
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                @if($modelActive && $modelInfo)
                    <div class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 rounded-full px-3 py-1.5">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-xs font-bold text-emerald-700">Model C4.5 Aktif</span>
                    </div>
                @endif
                <a href="{{ route('hasil-klasifikasi') }}" class="text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5">
                    <span>Lihat Peta Klasifikasi</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>
        </div>

        {{-- Tab Switcher --}}
        <div class="bg-white p-1.5 rounded-2xl border border-gray-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex gap-1 w-full sm:w-auto">
                <button type="button" @click="activeTab = 'input'"
                        class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all"
                        :class="activeTab === 'input' ? 'bg-emerald-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>1. Input Usulan & Peta Jarak</span>
                </button>

                <button type="button" @click="activeTab = 'daftar'"
                        class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all relative"
                        :class="activeTab === 'daftar' ? 'bg-emerald-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="8" height="4" x="8" y="2" rx="1"/>
                        <path d="M9 12h6"/><path d="M9 16h6"/>
                    </svg>
                    <span>2. Daftar Usulan Lokasi TPS</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                          :class="activeTab === 'daftar' ? 'bg-emerald-800 text-white' : 'bg-amber-100 text-amber-800 border border-amber-300'">
                        {{ $counts['menunggu'] }} Menunggu
                    </span>
                </button>
            </div>

            @if($userRole === 'admin' && $counts['menunggu'] > 0)
                <form action="{{ route('usulan-lokasi.klasifikasi-semua') }}" method="POST" onsubmit="return confirm('Klasifikasikan semua {{ $counts['menunggu'] }} usulan yang masih menunggu secara massal dengan C4.5?');">
                    @csrf
                    <button type="submit"
                            class="w-full sm:w-auto flex items-center justify-center gap-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span>⚡ Klasifikasi Semua Data Tertunda ({{ $counts['menunggu'] }})</span>
                    </button>
                </form>
            @endif
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 1: FORM INPUT USULAN & PETA PENGUKURAN JARAK --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'input'" x-cloak class="space-y-6">

            {{-- Alur Informasi Spasial Card --}}
            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-blue-50 rounded-2xl p-4 border border-emerald-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-extrabold text-emerald-900 uppercase tracking-wide">Alur Pengumpulan Data Spasial Petugas:</h2>
                        <p class="text-xs text-gray-600 mt-0.5">
                            <strong>1. Tentukan Titik TPS di Peta</strong> $\rightarrow$ 
                            <strong>2. Ukur Jarak Permukiman (&lt;200m / 200-500m / &gt;500m)</strong> $\rightarrow$ 
                            <strong>3. Ukur Jarak Sumber Air (&lt;100m / 100-300m / &gt;300m)</strong> $\rightarrow$ 
                            <strong>4. Simpan Usulan</strong> $\rightarrow$ 
                            <span class="text-amber-700 font-bold bg-amber-100 px-1.5 py-0.5 rounded">Status: Menunggu Klasifikasi Admin DLH</span>
                        </p>
                    </div>
                </div>
                <div class="text-[11px] text-gray-500 font-medium shrink-0 bg-white/80 px-3 py-1.5 rounded-xl border border-gray-200">
                    Acuan: <span class="font-bold text-gray-700">SNI 03-3241-1994</span>
                </div>
            </div>

            {{-- Main Form Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4 bg-gray-50/70 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        Form Data Spasial & Parameter C4.5
                    </h2>
                    <span class="text-xs text-gray-500 font-medium">Petugas Input: <strong class="text-emerald-700">{{ $userName }}</strong></span>
                </div>

                <form @submit.prevent="submitForm(false)" class="p-6 space-y-6" id="form-usulan-lokasi">
                    @csrf
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {{-- LEFT COLUMN: Lokasi, Kecamatan & Peta Leaflet --}}
                        <div class="space-y-4">
                            {{-- Nama Usulan Lokasi --}}
                            <div>
                                <label for="input-nama-lokasi" class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Usulan Lokasi TPS <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_lokasi" id="input-nama-lokasi"
                                       x-model="namaLokasi" required
                                       placeholder="Contoh: TPS 3R Desa Sinargalih RT 03"
                                       class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm font-medium text-gray-800 focus:outline-none focus:border-emerald-500 transition-colors shadow-sm" />
                            </div>

                            {{-- Kecamatan (BPS Cianjur) --}}
                            <div>
                                <label for="select-kecamatan-form" class="block text-sm font-semibold text-gray-700 mb-1.5">Kecamatan (BPS Cianjur) <span class="text-red-500">*</span></label>
                                <select name="kecamatan" id="select-kecamatan-form"
                                        x-model="kecamatan" required
                                        @change="fetchKepadatanBps($event.target.value)"
                                        class="w-full bg-white border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-800 focus:outline-none focus:border-emerald-500 transition-colors shadow-sm">
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

                            {{-- Koordinat GPS (Latitude & Longitude) --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="input-latitude" class="block text-sm font-semibold text-gray-700 mb-1.5">Latitude <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="latitude" id="input-latitude"
                                               x-model="latitude" required readonly
                                               placeholder="-6.8150"
                                               class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm font-mono text-gray-800 focus:outline-none pr-7 cursor-pointer" />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">°N</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="input-longitude" class="block text-sm font-semibold text-gray-700 mb-1.5">Longitude <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="longitude" id="input-longitude"
                                               x-model="longitude" required readonly
                                               placeholder="107.1380"
                                               class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm font-mono text-gray-800 focus:outline-none pr-7 cursor-pointer" />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">°E</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Peta Interaktif Leaflet --}}
                            <div class="rounded-xl overflow-hidden border-2 border-gray-200 relative" id="map-picker-wrapper">
                                <div class="bg-teal-700 px-3.5 py-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-teal-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <span class="text-xs font-bold text-white">Klik Peta untuk Menentukan Titik Lokasi TPS</span>
                                    </div>
                                    <div class="bg-black/30 rounded-lg px-2 py-0.5 text-xs text-teal-100 font-mono"
                                         x-text="(latitude && longitude) ? latitude + ', ' + longitude : 'Belum dipilih'"></div>
                                </div>

                                <div id="map-picker" class="w-full h-80 z-0"></div>

                                {{-- Overlay Legend Radius SNI --}}
                                <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur rounded-xl p-2.5 shadow-md border border-gray-200 z-[400] text-[11px] space-y-1">
                                    <p class="font-bold text-gray-700 uppercase tracking-wider text-[10px] mb-1">Radius Buffer SNI 03-3241-1994</p>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-amber-400 border border-amber-600 shrink-0"></span>
                                        <span class="text-gray-700 font-medium">Zona Permukiman (200m)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-cyan-400 border border-cyan-600 shrink-0"></span>
                                        <span class="text-gray-700 font-medium">Sempadan Air (100m)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN: Kriteria C4.5 & Hasil Pengukuran --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                                        <line x1="2" y1="20" x2="22" y2="20"/>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Hasil Pengukuran & Atribut C4.5</h3>
                            </div>

                            {{-- Kepadatan Penduduk (Otomatis BPS) --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                    Kepadatan Penduduk & Data Jiwa (Otomatis BPS)
                                </label>
                                <div class="relative">
                                    <input type="text" readonly :value="densityBpsText || 'Pilih Kecamatan pada form di sebelah kiri'"
                                           class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-800 focus:outline-none" />
                                    <template x-if="densityLoading">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-emerald-600 font-bold">Memuat BPS...</span>
                                    </template>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">Data kepadatan otomatis dikonversi ke kategori C4.5: Rendah (&lt;1.000), Sedang (1.000-2.500), Tinggi (&gt;2.500 jiwa/km²).</p>
                            </div>

                            {{-- Jarak Permukiman (Pengukuran Petugas) --}}
                            <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label for="select-jarak-permukiman-form" class="block text-sm font-bold text-amber-900">
                                        1. Jarak dari Permukiman (SNI 03-3241-1994) <span class="text-red-500">*</span>
                                    </label>
                                    <span class="text-[11px] bg-amber-200 text-amber-900 font-bold px-2 py-0.5 rounded-full">Buffer 200m</span>
                                </div>
                                <select name="jarak_permukiman" id="select-jarak-permukiman-form"
                                        x-model="jarakPermukiman" required
                                        class="w-full bg-white border-2 border-amber-300 rounded-xl px-4 py-2.5 text-sm font-bold text-gray-800 focus:outline-none focus:border-amber-500 transition-colors shadow-sm">
                                    <option value="Dekat (< 200m)">Dekat (&lt; 200m) — Di dalam area permukiman warga</option>
                                    <option value="Sedang (200-500m)">Sedang (200-500m) — Jarak aman sedang</option>
                                    <option value="Jauh (> 500m)">Jauh (&gt; 500m) — Jarak sangat aman dari permukiman</option>
                                </select>
                                <p class="text-[11px] text-amber-800">Petugas melakukan pengukuran langsung di lapangan atau mengacu pada radius lingkaran kuning 200m pada peta.</p>
                            </div>

                            {{-- Jarak Sumber Air (Pengukuran Petugas) --}}
                            <div class="bg-cyan-50/60 border border-cyan-200 rounded-2xl p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label for="select-jarak-air-form" class="block text-sm font-bold text-cyan-900">
                                        2. Jarak dari Sumber Air / Sungai <span class="text-red-500">*</span>
                                    </label>
                                    <span class="text-[11px] bg-cyan-200 text-cyan-900 font-bold px-2 py-0.5 rounded-full">Sempadan 100m</span>
                                </div>
                                <select name="jarak_air" id="select-jarak-air-form"
                                        x-model="jarakAir" required
                                        class="w-full bg-white border-2 border-cyan-300 rounded-xl px-4 py-2.5 text-sm font-bold text-gray-800 focus:outline-none focus:border-cyan-500 transition-colors shadow-sm">
                                    <option value="Dekat (< 100m)">Dekat (&lt; 100m) — Di dalam sempadan sempit sumber air</option>
                                    <option value="Sedang (100-300m)">Sedang (100-300m) — Di luar sempadan air minimum</option>
                                    <option value="Jauh (> 300m)">Jauh (&gt; 300m) — Jauh dan aman dari pencemaran air</option>
                                </select>
                                <p class="text-[11px] text-cyan-800">Petugas mengukur jarak titik TPS ke sungai, danau, mata air atau mengacu pada radius lingkaran biru 100m pada peta.</p>
                            </div>

                            {{-- Form Submission Actions --}}
                            <div class="pt-3 space-y-2.5">
                                {{-- Tombol Simpan Usulan untuk Petugas & Admin --}}
                                <button type="submit" :disabled="submitting"
                                        class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm py-3.5 px-6 rounded-xl shadow-lg shadow-emerald-600/20 hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                                    <template x-if="!submitting">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                            <span>💾 Simpan Usulan Lokasi TPS (Status: Menunggu Klasifikasi)</span>
                                        </span>
                                    </template>
                                    <template x-if="submitting">
                                        <span class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            Menyimpan Usulan...
                                        </span>
                                    </template>
                                </button>

                                {{-- Khusus Admin: Opsi Simpan & Langsung Klasifikasi C4.5 --}}
                                @if($userRole === 'admin')
                                    <button type="button" @click="submitForm(true)" :disabled="submitting"
                                            class="w-full bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-300 font-bold text-xs py-2.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2">
                                        <span>⚡ Simpan & Eksekusi Klasifikasi C4.5 Langsung (Admin Override)</span>
                                    </button>
                                @endif

                                <p class="text-[11px] text-center text-gray-500 italic">
                                    {{ $userRole === 'admin' 
                                        ? 'Sebagai Admin, Anda dapat menyimpan data usulan atau langsung mengklasifikasikannya.'
                                        : 'Data yang diinput oleh Petugas akan berstatus "Menunggu Klasifikasi" hingga diproses oleh Admin DLH.' }}
                                </p>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 2: DAFTAR USULAN LOKASI TPS & STATUS KLASIFIKASI --}}
        {{-- ============================================================ --}}
        <div x-show="activeTab === 'daftar'" x-cloak class="space-y-4">

            {{-- Summary Stats Row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm cursor-pointer transition-all hover:border-gray-400"
                     :class="statusFilter === 'all' ? 'ring-2 ring-emerald-500 bg-emerald-50/30' : ''"
                     @click="statusFilter = 'all'">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Usulan</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ $counts['total'] }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">Semua data usulan</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-sm cursor-pointer transition-all hover:border-amber-400"
                     :class="statusFilter === 'menunggu' ? 'ring-2 ring-amber-500 bg-amber-50/50' : ''"
                     @click="statusFilter = 'menunggu'">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Menunggu Klasifikasi
                    </p>
                    <p class="text-2xl font-black text-amber-700 mt-1">{{ $counts['menunggu'] }}</p>
                    <p class="text-[11px] text-amber-600 mt-0.5">Perlu klasifikasi Admin</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-sm cursor-pointer transition-all hover:border-emerald-400"
                     :class="statusFilter === 'layak' ? 'ring-2 ring-emerald-500 bg-emerald-50/50' : ''"
                     @click="statusFilter = 'layak'">
                    <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Dinyatakan Layak</p>
                    <p class="text-2xl font-black text-emerald-700 mt-1">{{ $counts['layak'] }}</p>
                    <p class="text-[11px] text-emerald-600 mt-0.5">Memenuhi kriteria C4.5</p>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-rose-200 shadow-sm cursor-pointer transition-all hover:border-rose-400"
                     :class="statusFilter === 'tidak_layak' ? 'ring-2 ring-rose-500 bg-rose-50/50' : ''"
                     @click="statusFilter = 'tidak_layak'">
                    <p class="text-xs font-bold text-rose-700 uppercase tracking-wider">Tidak Layak</p>
                    <p class="text-2xl font-black text-rose-700 mt-1">{{ $counts['tidak_layak'] }}</p>
                    <p class="text-[11px] text-rose-600 mt-0.5">Tidak memenuhi kriteria</p>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                
                {{-- Table Controls Bar --}}
                <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50/60">
                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <button type="button" @click="statusFilter = 'all'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                                :class="statusFilter === 'all' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100'">
                            Semua ({{ $counts['total'] }})
                        </button>
                        <button type="button" @click="statusFilter = 'menunggu'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                                :class="statusFilter === 'menunggu' ? 'bg-amber-600 text-white' : 'bg-white text-amber-700 border border-amber-200 hover:bg-amber-50'">
                            ⏳ Menunggu ({{ $counts['menunggu'] }})
                        </button>
                        <button type="button" @click="statusFilter = 'layak'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                                :class="statusFilter === 'layak' ? 'bg-emerald-600 text-white' : 'bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50'">
                            ✓ Layak ({{ $counts['layak'] }})
                        </button>
                        <button type="button" @click="statusFilter = 'tidak_layak'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                                :class="statusFilter === 'tidak_layak' ? 'bg-rose-600 text-white' : 'bg-white text-rose-700 border border-rose-200 hover:bg-rose-50'">
                            ✗ Tidak Layak ({{ $counts['tidak_layak'] }})
                        </button>
                    </div>

                    {{-- Search Input --}}
                    <div class="w-full sm:w-64 relative">
                        <input type="text" x-model="searchKeyword"
                               placeholder="Cari lokasi, kecamatan, petugas..."
                               class="w-full bg-white border border-gray-200 rounded-xl pl-9 pr-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:border-emerald-500 shadow-sm" />
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </div>
                </div>

                {{-- Table Data --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3">Nama Usulan Lokasi</th>
                                <th class="px-4 py-3">Kecamatan</th>
                                <th class="px-4 py-3">Koordinat</th>
                                <th class="px-4 py-3">Hasil Pengukuran</th>
                                <th class="px-4 py-3">Penginput / Waktu</th>
                                <th class="px-4 py-3 text-center">Status Data</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <template x-if="filteredList.length === 0">
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                                        <div class="max-w-xs mx-auto space-y-2">
                                            <p class="text-sm font-bold text-gray-500">Tidak ada data usulan lokasi</p>
                                            <p class="text-xs text-gray-400">Belum ada usulan yang cocok dengan filter yang dipilih.</p>
                                            <button @click="activeTab = 'input'" class="text-xs font-bold text-emerald-600 hover:underline">
                                                + Tambah Usulan Lokasi Baru
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(item, idx) in filteredList" :key="item.id">
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="px-4 py-3.5 text-center font-mono text-gray-400" x-text="idx + 1"></td>
                                    <td class="px-4 py-3.5 font-bold text-gray-800">
                                        <p x-text="item.nama"></p>
                                        <span class="text-[10px] text-gray-400 font-normal" x-text="item.jenis_fasilitas || 'TPS 3R'"></span>
                                    </td>
                                    <td class="px-4 py-3.5 font-semibold text-gray-700" x-text="item.kecamatan"></td>
                                    <td class="px-4 py-3.5 font-mono text-[11px] text-gray-500">
                                        <span x-text="item.latitude.toFixed(4) + ', ' + item.longitude.toFixed(4)"></span>
                                    </td>
                                    <td class="px-4 py-3.5 space-y-0.5">
                                        <div class="text-[11px] text-gray-700">
                                            <span class="text-gray-400">Permukiman:</span> <strong x-text="item.jarak_permukiman ? item.jarak_permukiman.split(' ')[0] : '-'"></strong>
                                        </div>
                                        <div class="text-[11px] text-gray-700">
                                            <span class="text-gray-400">Sumber Air:</span> <strong x-text="item.jarak_air ? item.jarak_air.split(' ')[0] : '-'"></strong>
                                        </div>
                                        <div class="text-[11px] text-gray-500">
                                            <span class="text-gray-400">Kepadatan:</span> <span x-text="item.kepadatan || 'Sedang'"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <p class="font-semibold text-gray-700 text-xs" x-text="item.petugas_nama || 'Petugas Lapangan'"></p>
                                        <p class="text-[10px] text-gray-400" x-text="item.created_at"></p>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <template x-if="item.status === 'layak'">
                                            <div class="inline-flex flex-col items-center">
                                                <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-[11px] font-extrabold px-2.5 py-1 rounded-full border border-emerald-300">
                                                    ✓ LAYAK
                                                </span>
                                                <span class="text-[10px] text-emerald-600 font-mono mt-0.5" x-text="item.confidence || '90%'"></span>
                                            </div>
                                        </template>

                                        <template x-if="item.status === 'tidak_layak'">
                                            <div class="inline-flex flex-col items-center">
                                                <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-800 text-[11px] font-extrabold px-2.5 py-1 rounded-full border border-rose-300">
                                                    ✗ TIDAK LAYAK
                                                </span>
                                                <span class="text-[10px] text-rose-600 font-mono mt-0.5" x-text="item.confidence || '90%'"></span>
                                            </div>
                                        </template>

                                        <template x-if="item.status === 'menunggu_klasifikasi' || !item.status">
                                            <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 text-[11px] font-bold px-2.5 py-1 rounded-full border border-amber-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                Menunggu Klasifikasi
                                            </span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3.5 text-right space-x-1">
                                        {{-- Khusus Admin: Tombol Klasifikasi C4.5 --}}
                                        @if($userRole === 'admin')
                                            <template x-if="item.status === 'menunggu_klasifikasi' || !item.status">
                                                <button type="button" @click="prosesKlasifikasiAdmin(item)"
                                                        class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-xs px-3 py-1.5 rounded-xl shadow transition-all">
                                                    ⚡ Klasifikasi C4.5
                                                </button>
                                            </template>

                                            <template x-if="item.status === 'layak' || item.status === 'tidak_layak'">
                                                <button type="button" @click="prosesKlasifikasiAdmin(item)"
                                                        title="Uji ulang klasifikasi C4.5"
                                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-2.5 py-1.5 rounded-xl transition-all">
                                                    🔄 Uji Ulang
                                                </button>
                                            </template>
                                        @endif

                                        <button type="button" @click="bukaDetail(item)"
                                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs px-2.5 py-1.5 rounded-xl transition-all" title="Lihat Detail">
                                            🔍 Detail
                                        </button>

                                        @if($userRole === 'admin')
                                            <form :action="'/usulan-lokasi/' + item.id" method="POST" class="inline" onsubmit="return confirm('Hapus usulan lokasi ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1.5 text-xs font-bold rounded-lg hover:bg-red-50 transition-all" title="Hapus Usulan">
                                                    🗑️
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- ============================================================ --}}
    {{-- MODAL HASIL KLASIFIKASI C4.5 (ADMIN FEEDBACK MODAL) --}}
    {{-- ============================================================ --}}
    <div x-show="showModalKlasifikasi" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[2000] flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-200 animate-in fade-in zoom-in duration-200"
             @click.outside="showModalKlasifikasi = false">
            
            <div class="p-6 text-center space-y-4" x-show="currentKlasifikasiData">
                <div class="w-16 h-16 rounded-3xl mx-auto flex items-center justify-center shadow-lg"
                     :class="currentKlasifikasiData?.status === 'layak' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                    <template x-if="currentKlasifikasiData?.status === 'layak'">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                    </template>
                    <template x-if="currentKlasifikasiData?.status !== 'layak'">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                    </template>
                </div>

                <div>
                    <span class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Hasil Keputusan Algoritma C4.5</span>
                    <h3 class="text-2xl font-black mt-1"
                        :class="currentKlasifikasiData?.status === 'layak' ? 'text-emerald-700' : 'text-rose-600'"
                        x-text="currentKlasifikasiData?.status === 'layak' ? '✓ STATUS: LAYAK' : '✗ STATUS: TIDAK LAYAK'"></h3>
                    <p class="text-sm font-semibold text-gray-700 mt-1" x-text="currentKlasifikasiData?.nama"></p>
                </div>

                {{-- Detail Parameter Decision --}}
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 text-left text-xs space-y-2">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-500 font-medium">Nilai Confidence:</span>
                        <span class="font-extrabold text-emerald-700 font-mono text-sm" x-text="currentKlasifikasiData?.confidence"></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-500 font-medium">Rule C4.5 yang Terpicu:</span>
                        <span class="font-bold text-gray-800 font-mono" x-text="currentKlasifikasiData?.rule_id || 'R2'"></span>
                    </div>
                    <div>
                        <span class="text-gray-500 font-medium block mb-1">Decision Path:</span>
                        <p class="bg-white p-2 rounded-xl border border-gray-200 font-mono text-[11px] text-gray-700 leading-relaxed" x-text="currentKlasifikasiData?.rule"></p>
                    </div>
                    <div class="text-[11px] text-gray-500 pt-1">
                        Diverifikasi & diklasifikasikan oleh: <strong class="text-gray-700" x-text="currentKlasifikasiData?.classified_by || 'Administrator DLH'"></strong>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showModalKlasifikasi = false"
                            class="flex-1 py-3 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        Tutup
                    </button>
                    <a :href="'/hasil-klasifikasi?new_id=usulan-' + currentKlasifikasiData?.id"
                       class="flex-1 py-3 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl text-center shadow-lg transition-all">
                        Buka di Peta Spasial →
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL DETAIL USULAN LOKASI --}}
    {{-- ============================================================ --}}
    <div x-show="showModalDetail" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[2000] flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-gray-200"
             @click.outside="showModalDetail = false">
            <div class="flex items-center justify-between border-b pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-gray-800" x-text="detailItem?.nama"></h3>
                    <p class="text-xs text-gray-400" x-text="detailItem?.kecamatan + ' • ' + (detailItem?.jenis_fasilitas || 'TPS 3R')"></p>
                </div>
                <button @click="showModalDetail = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
            </div>

            <div class="space-y-3 text-xs" x-show="detailItem">
                <div class="bg-gray-50 p-3 rounded-xl space-y-1.5 border border-gray-100">
                    <p class="font-bold text-gray-600 uppercase text-[10px]">Data Koordinat & Wilayah</p>
                    <div class="grid grid-cols-2 gap-2 text-gray-700 font-mono">
                        <div>Lat: <span class="font-bold" x-text="detailItem?.latitude"></span></div>
                        <div>Lng: <span class="font-bold" x-text="detailItem?.longitude"></span></div>
                    </div>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl space-y-1.5 border border-gray-100">
                    <p class="font-bold text-gray-600 uppercase text-[10px]">Parameter Pengukuran Lapangan</p>
                    <div class="space-y-1 text-gray-700">
                        <p>🏠 Jarak Permukiman: <strong x-text="detailItem?.jarak_permukiman"></strong></p>
                        <p>💧 Jarak Sumber Air: <strong x-text="detailItem?.jarak_air"></strong></p>
                        <p>👥 Kepadatan BPS: <strong x-text="detailItem?.kepadatan"></strong></p>
                    </div>
                </div>

                <div class="bg-gray-50 p-3 rounded-xl space-y-1.5 border border-gray-100">
                    <p class="font-bold text-gray-600 uppercase text-[10px]">Status Usulan</p>
                    <p>Status: <strong class="uppercase" x-text="detailItem?.status"></strong></p>
                    <p>Penginput: <strong x-text="detailItem?.petugas_nama"></strong></p>
                    <p>Waktu Input: <span x-text="detailItem?.created_at"></span></p>
                </div>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="button" @click="showModalDetail = false"
                        class="flex-1 py-2.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                    Tutup
                </button>
                <button type="button" @click="lihatDiPeta(detailItem); showModalDetail = false;"
                        class="flex-1 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all">
                    📍 Tampilkan di Peta
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // 1. Inisialisasi Peta Leaflet
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

    function setMarker(lat, lng, labelText) {
        labelText = labelText || 'Titik Usulan Dipilih';
        if (selectedMarker) {
            selectedMarker.setLatLng([lat, lng]);
        } else {
            selectedMarker = L.marker([lat, lng], {
                icon: createIcon('#059669', 32),
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
                <p style="font-weight: 700; margin: 0 0 4px; font-size: 13px;">📍 ${labelText}</p>
                <p style="color: #6b7280; font-size: 11px; margin: 0; font-family: monospace;">${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                <p style="color: #059669; font-size: 11px; margin: 4px 0 0; font-weight: 600;">Radius Buffer SNI (200m & 100m) Aktif</p>
            </div>
        `).openPopup();
    }

    window.setMarkerAndFly = function(lat, lng, name) {
        mapPicker.flyTo([lat, lng], 15, { duration: 1.2 });
        setMarker(lat, lng, name);
        updateSniBufferRadiusCircles(lat, lng);
        const alpine = getAlpine();
        if (alpine) {
            alpine.latitude = lat.toFixed(6);
            alpine.longitude = lng.toFixed(6);
            alpine.namaLokasi = name;
        }
    };

    // Auto-pan saat pilih kecamatan
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

    // Event Klik Peta
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

    // Invalidate size saat tab berganti
    document.addEventListener('click', function() {
        setTimeout(() => {
            mapPicker.invalidateSize();
        }, 200);
    });
});
</script>
@endsection
