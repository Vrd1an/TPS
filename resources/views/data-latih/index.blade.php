{{-- Halaman Kelola Data Latih — Training Center C4.5 --}}
@extends('layouts.app')

@section('title', 'Kelola Data Latih')
@section('page-title', 'Kelola Data Latih')

@php
    $activeMenu = 'data-latih';

    // $dataset passed from DataLatihController (21 Data Latih Historis Cianjur)
    $dataLatih = $dataset ?? [];
    $totalData = count($dataLatih);
    $layakCount = count(array_filter($dataLatih, fn($d) => strtolower($d['status'] ?? $d['label'] ?? '') === 'layak'));
    $tidakLayakCount = $totalData - $layakCount;
    $modelInfo = $modelInfo ?? ['status' => 'belum_dibentuk'];
@endphp

@section('content')
<div class="flex-1 bg-gray-50 overflow-y-auto" id="page-data-latih"
     x-data="{
        training: false,
        trainingStep: 0,
        trainingDone: false,
        trainingResult: null,
        trainingError: null,
        modelStatus: '{{ $modelInfo['status'] ?? 'belum_dibentuk' }}',
        steps: [
            'Mengambil data historis...',
            'Menghitung Entropy total dataset...',
            'Menghitung Information Gain setiap atribut...',
            'Memilih Root Node (Gain tertinggi)...',
            'Membentuk Decision Tree rekursif...',
            'Mengekstrak Rule IF-THEN...',
            'Menyimpan model ke database...',
            'Training selesai!'
        ],
        async startTraining() {
            this.training = true;
            this.trainingStep = 0;
            this.trainingDone = false;
            this.trainingResult = null;
            this.trainingError = null;

            // Animate steps
            for (let i = 0; i < this.steps.length - 1; i++) {
                this.trainingStep = i;
                await new Promise(r => setTimeout(r, 600 + Math.random() * 400));
            }

            try {
                const response = await fetch('{{ route('data-latih.train') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.status === 'success') {
                    this.trainingStep = this.steps.length - 1;
                    this.trainingDone = true;
                    this.trainingResult = data.data;
                    this.modelStatus = 'aktif';
                } else {
                    this.trainingError = data.message || 'Training gagal.';
                    this.training = false;
                }
            } catch (err) {
                this.trainingError = 'Terjadi kesalahan jaringan saat training.';
                this.training = false;
            }
        }
     }">

    {{-- Top Bar --}}
    <x-topbar title="Kelola Data Latih" subtitle="Dataset training algoritma C4.5 — {{ $totalData }} entri historis Cianjur (UC-03)">
        <x-slot:actions>
            {{-- Tombol Kosongkan Data --}}
            <form action="{{ route('data-latih.clear') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        onclick="return confirm('Apakah Anda yakin ingin mengosongkan seluruh data latih dan usulan lokasi?');"
                        class="flex items-center gap-1.5 text-xs font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2 hover:bg-red-100 transition-colors shadow-sm"
                        id="btn-clear-data">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                    Kosongkan Data
                </button>
            </form>

            {{-- Tombol Import Data Excel --}}
            <button @click="$dispatch('open-modal-import')"
                    class="flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-100 border border-emerald-300 hover:bg-emerald-200 rounded-lg px-3.5 py-2 transition-colors shadow-sm"
                    id="btn-import-excel">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/>
                </svg>
                Import Data Excel
            </button>
        </x-slot:actions>
    </x-topbar>

    <div class="px-4 lg:px-8 py-5 space-y-5">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl p-4 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- MODEL STATUS BANNER + TRAINING BUTTON --}}
        {{-- ============================================================ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="section-model-training">
            {{-- Header --}}
            <div class="border-b border-gray-100 px-5 py-4 bg-gradient-to-r from-indigo-50/50 to-purple-50/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/>
                                <path d="M13 6h3a2 2 0 0 1 2 2v7"/><path d="M6 9v12"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">Status Model Decision Tree C4.5</h2>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="w-2 h-2 rounded-full"
                                      :class="{
                                          'bg-emerald-500': modelStatus === 'aktif',
                                          'bg-amber-500 animate-pulse': modelStatus === 'training',
                                          'bg-gray-400': modelStatus === 'belum_dibentuk' || modelStatus === 'nonaktif'
                                      }"></span>
                                <span class="text-xs font-bold"
                                      :class="{
                                          'text-emerald-700': modelStatus === 'aktif',
                                          'text-amber-700': modelStatus === 'training',
                                          'text-gray-500': modelStatus === 'belum_dibentuk' || modelStatus === 'nonaktif'
                                      }"
                                      x-text="modelStatus === 'aktif' ? 'Model Aktif' : (modelStatus === 'training' ? 'Sedang Training...' : 'Belum Dibangun')"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Training --}}
                    <button @click="startTraining()"
                            :disabled="training || {{ $totalData }} === 0"
                            class="flex items-center gap-2 text-sm font-bold text-white rounded-xl px-5 py-2.5 shadow-lg transition-all duration-200 shrink-0"
                            :class="training ? 'bg-gray-400 cursor-not-allowed' : '{{ $totalData > 0 ? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 hover:shadow-xl' : 'bg-gray-400 cursor-not-allowed' }}'"
                            id="btn-train-c45">
                        <template x-if="!training">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                                Proses Training C4.5
                            </span>
                        </template>
                        <template x-if="training && !trainingDone">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Training...
                            </span>
                        </template>
                        <template x-if="trainingDone">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                                </svg>
                                Training Selesai!
                            </span>
                        </template>
                    </button>
                </div>
            </div>

            {{-- Training Progress Steps --}}
            <template x-if="training">
                <div class="px-5 py-4 space-y-2">
                    <template x-for="(step, i) in steps" :key="i">
                        <div class="flex items-center gap-3 transition-all duration-300"
                             :class="i <= trainingStep ? 'opacity-100' : 'opacity-30'">
                            <template x-if="i < trainingStep">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                                </svg>
                            </template>
                            <template x-if="i === trainingStep && !trainingDone">
                                <svg class="animate-spin w-4 h-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="i === trainingStep && trainingDone">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                                </svg>
                            </template>
                            <template x-if="i > trainingStep">
                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 shrink-0"></div>
                            </template>
                            <span class="text-sm" :class="i <= trainingStep ? 'text-gray-800 font-semibold' : 'text-gray-400'" x-text="step"></span>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Training Error --}}
            <template x-if="trainingError">
                <div class="px-5 py-4">
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span x-text="trainingError"></span>
                    </div>
                </div>
            </template>

            {{-- Training Result Card --}}
            <template x-if="trainingDone && trainingResult">
                <div class="px-5 pb-5">
                    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-200 p-4 mt-2">
                        <h3 class="text-sm font-bold text-emerald-800 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                            </svg>
                            Decision Tree Berhasil Dibentuk
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                <p class="text-lg font-extrabold text-indigo-700" x-text="trainingResult.root_attribute || '-'"></p>
                                <p class="text-xs text-gray-500 mt-0.5">Root Node</p>
                            </div>
                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                <p class="text-lg font-extrabold text-emerald-700" x-text="trainingResult.total_rules || 0"></p>
                                <p class="text-xs text-gray-500 mt-0.5">Jumlah Rule</p>
                            </div>
                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                <p class="text-lg font-extrabold text-purple-700" x-text="trainingResult.total_nodes || 0"></p>
                                <p class="text-xs text-gray-500 mt-0.5">Jumlah Node</p>
                            </div>
                            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                                <p class="text-lg font-extrabold text-gray-700" x-text="trainingResult.total_data_latih || 0"></p>
                                <p class="text-xs text-gray-500 mt-0.5">Data Latih</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-emerald-700">
                                <span class="font-bold">Entropy Total:</span> <span x-text="trainingResult.entropy_total"></span>
                            </p>
                            <a href="{{ url('/decision-tree') }}" class="text-xs font-bold text-indigo-700 hover:text-indigo-900 bg-white border border-indigo-200 rounded-lg px-3 py-1.5 hover:bg-indigo-50 transition-colors">
                                Lihat Decision Tree →
                            </a>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Empty Data Warning --}}
            @if($totalData === 0)
                <div class="px-5 pb-4">
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        <span>Belum ada data historis. Klik <strong>Import Data Historis</strong> untuk memasukkan data DLH.</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3" id="stats-data-latih">
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <p class="text-2xl font-extrabold text-gray-800">{{ $totalData }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total Data Latih</p>
            </div>
            <div class="bg-green-50 border border-green-100 rounded-xl p-4 shadow-sm text-center">
                <p class="text-2xl font-extrabold text-green-700">{{ $layakCount }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Status Layak</p>
            </div>
            <div class="bg-red-50 border border-red-100 rounded-xl p-4 shadow-sm text-center">
                <p class="text-2xl font-extrabold text-red-600">{{ $tidakLayakCount }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Status Tidak Layak</p>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="tabel-data-latih">
            <div class="border-b border-gray-100 px-5 py-3.5 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Data Mentah Historis (64 Fasilitas Pengelola Sampah DLH Cianjur)</span>
                    <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                        Dataset Mentah
                    </span>
                </div>
                <p class="text-[11px] text-gray-500">💡 Klik <strong class="text-emerald-700">Pembentukan Model C4.5</strong> di atas untuk mengeksekusi tahapan KDD (Selection, Preprocessing, Transformation, Rules & C4.5).</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">NO</th>
                            <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama BANK SAMPAH / FASILITAS</th>
                            <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">KATEGORI FASILITAS</th>
                            <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">ALAMAT</th>
                            <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($dataLatih as $index => $row)
                            @php
                                $jenisFas = $row['jenis_fasilitas'] ?? 'TPS 3R';
                                $badgeColor = match($jenisFas) {
                                    'Biodigester' => 'bg-sky-100 text-sky-700 border-sky-200',
                                    'Bank Sampah' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'Rumah Kompos' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    default => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors" id="row-data-{{ $row['id'] ?? $index }}">
                                <td class="px-4 py-3 text-xs text-gray-400 font-medium">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-800 text-sm">{{ $row['nama_fasilitas'] ?? $row['nama'] ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2.5 py-0.5 text-xs font-bold border rounded-full {{ $badgeColor }}">{{ $jenisFas }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $row['alamat_desa'] ?? $row['kecamatan'] ?? '' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <form action="{{ url('/data-latih/' . ($row['id'] ?? $index)) }}" method="POST" onsubmit="return confirm('Hapus baris data latih ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-semibold">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-12 text-gray-400">Belum ada data mentah terdaftar. Klik Import Data Historis di atas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- CTA — Buat Usulan --}}
        <div class="bg-green-700 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" id="cta-usulan-lokasi">
            <div>
                <p class="text-white font-bold">Siap Klasifikasi Lokasi Baru?</p>
                <p class="text-green-200 text-sm mt-0.5">
                    @if($modelInfo['status'] === 'aktif')
                        Model C4.5 aktif — ajukan usulan lokasi TPS untuk diproses
                    @else
                        Lakukan Training terlebih dahulu sebelum klasifikasi
                    @endif
                </p>
            </div>
            <a href="{{ url('/usulan-lokasi') }}"
               class="shrink-0 bg-white text-green-700 hover:bg-green-50 font-bold text-sm rounded-xl px-5 py-2.5 transition-colors shadow-md text-center"
               id="btn-buat-usulan">
                Buat Usulan Lokasi →
            </a>
        </div>
    </div>

    {{-- Modal Import Data Excel --}}
    <div x-data="{ open: false }"
         x-on:open-modal-import.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 max-w-lg w-full p-6 space-y-5" @click.away="open = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Import Data Latih (Excel / CSV)</h3>
                        <p class="text-xs text-gray-500">Format sesuai dataset gabungan TPS Cianjur</p>
                    </div>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('data-latih.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700">Pilih File Excel / CSV dari Komputer (.xlsx, .xls, .csv)</label>
                    <input type="file" name="excel_file" required accept=".xlsx,.xls,.csv"
                           class="w-full text-xs text-gray-500 border border-gray-200 rounded-xl p-2.5 bg-gray-50 focus:outline-none focus:border-emerald-500">
                    <p class="text-[11px] text-gray-400">Sistem mendukung file Excel seperti <code class="bg-gray-100 px-1 py-0.5 rounded text-emerald-700 font-bold">fasilitas ps - Copy.xlsx</code> maupun dataset TPS DLH Cianjur lainnya.</p>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
                    <button type="button" @click="open = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-700 rounded-xl hover:bg-emerald-800 shadow-md transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Proses Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
