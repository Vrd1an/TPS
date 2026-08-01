@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')

@php
    $activeMenu = 'pengaturan';
@endphp

@section('content')
<div class="flex-1 bg-gray-50 overflow-y-auto" id="page-pengaturan">
    <x-topbar title="Pengaturan Sistem" subtitle="Kelola konfigurasi Algoritma C4.5, integrasi QGIS Server, dan profil admin"></x-topbar>

    <div class="px-4 lg:px-8 py-6 max-w-4xl mx-auto space-y-6">

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2 text-sm shadow-sm">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="m9 11 3 3 6-6"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ url('/pengaturan') }}" method="POST" class="space-y-6">
            @csrf

            {{-- 1. Konfigurasi Algoritma C4.5 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:p-6 space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center text-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M12 20v-8m0 0V4m0 8h8m-8 0H4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm lg:text-base font-bold text-gray-800">Parameter Algoritma C4.5</h2>
                        <p class="text-xs text-gray-400">Konfigurasi batas kelayakan evaluasi model pohon keputusan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Akurasi Target (%)</label>
                        <input type="number" name="accuracy_threshold" value="{{ $settings['accuracy_threshold'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Minimum Split Size</label>
                        <input type="number" name="min_split_size" value="{{ $settings['min_split_size'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Min. Confidence (%)</label>
                        <input type="number" name="min_confidence" value="{{ $settings['min_confidence'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors" />
                    </div>
                </div>
            </div>

            {{-- 2. Integrasi QGIS Server --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:p-6 space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center text-teal-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm lg:text-base font-bold text-gray-800">Integrasi QGIS Server</h2>
                        <p class="text-xs text-gray-400">Endpoint API dan path proyek spasial untuk render Web GIS</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">QGIS Server URL CGI</label>
                        <input type="url" name="qgis_url" value="{{ $settings['qgis_url'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Path Project QGIS (.qgs / .qgz)</label>
                        <input type="text" name="qgis_project" value="{{ $settings['qgis_project'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors font-mono" />
                    </div>
                </div>
            </div>

            {{-- 3. Profil Administrator --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:p-6 space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm lg:text-base font-bold text-gray-800">Profil Instansi Admin</h2>
                        <p class="text-xs text-gray-400">Informasi identitas admin pengelola sistem</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                        <input type="text" name="admin_name" value="{{ $settings['admin_name'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Alamat Email</label>
                        <input type="email" name="admin_email" value="{{ $settings['admin_email'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Instansi / Dinas</label>
                        <input type="text" name="admin_agency" value="{{ $settings['admin_agency'] }}" 
                               class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 font-medium focus:outline-none focus:border-green-500 transition-colors" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="reset" class="px-5 py-3 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    Reset
                </button>
                <button type="submit" class="px-6 py-3 bg-green-700 hover:bg-green-800 text-white rounded-xl text-sm font-semibold shadow-md hover:shadow-lg transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
