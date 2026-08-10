{{-- Halaman Uji Confusion Matrix --}}
@extends('layouts.app')

@section('title', 'Confusion Matrix')
@section('page-title', 'Confusion Matrix')

@php
    $activeMenu = 'confusion-matrix';

    // $evaluation passed from ConfusionMatrixController
    $eval = $evaluation ?? [
        'tp' => 0, 'tn' => 0, 'fp' => 0, 'fn' => 0, 'total_data' => 0,
        'accuracy' => 0, 'precision' => 0, 'recall' => 0, 'f1_score' => 0, 'specificity' => 0,
        'iterations' => []
    ];

    $total = $eval['total_data'] ?? 0;
    $matrix = ['tp' => $eval['tp'] ?? 0, 'tn' => $eval['tn'] ?? 0, 'fp' => $eval['fp'] ?? 0, 'fn' => $eval['fn'] ?? 0];

    $accuracyVal = ($eval['accuracy'] ?? 0) / 100;
    $precisionVal = ($eval['precision'] ?? 0) / 100;
    $recallVal = ($eval['recall'] ?? 0) / 100;
    $f1Val = ($eval['f1_score'] ?? 0) / 100;

    $metricCards = [
        ['label' => 'Akurasi', 'value' => ($eval['accuracy'] ?? 0) . '%', 'raw' => $accuracyVal, 'desc' => '(TP + TN) / Total', 'color' => 'green', 'icon' => 'award', 'detail' => ($matrix['tp'] + $matrix['tn']) . ' dari ' . $total . ' sampel uji benar'],
        ['label' => 'Presisi', 'value' => ($eval['precision'] ?? 0) . '%', 'raw' => $precisionVal, 'desc' => 'TP / (TP + FP)', 'color' => 'teal', 'icon' => 'target', 'detail' => $matrix['tp'] . ' dari ' . ($matrix['tp'] + $matrix['fp']) . ' prediksi positif benar'],
        ['label' => 'Recall', 'value' => ($eval['recall'] ?? 0) . '%', 'raw' => $recallVal, 'desc' => 'TP / (TP + FN)', 'color' => 'blue', 'icon' => 'refresh', 'detail' => $matrix['tp'] . ' dari ' . ($matrix['tp'] + $matrix['fn']) . ' aktual positif terdeteksi'],
        ['label' => 'F1-Score', 'value' => ($eval['f1_score'] ?? 0) . '%', 'raw' => $f1Val, 'desc' => '2×(P×R)/(P+R)', 'color' => 'purple', 'icon' => 'trending-up', 'detail' => 'Harmonic mean presisi & recall'],
    ];

    $classHistory = $eval['iterations'] ?? [];
@endphp

@section('content')
<div class="flex-1 bg-gray-50 overflow-y-auto" id="page-confusion-matrix">

    {{-- Top Bar --}}
    <x-topbar title="Uji Confusion Matrix" subtitle="Evaluasi performa model C4.5 — Data Uji: {{ $total }} sampel (UC-07)">
        <x-slot:actions>
            <div class="flex items-center gap-1.5 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-3 py-1.5 w-fit">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span class="font-medium hidden sm:inline">K-Fold Cross Validation: 5-Fold Iteration</span>
                <span class="font-medium sm:hidden">5-Fold CV</span>
            </div>
        </x-slot:actions>
    </x-topbar>

    <div class="px-4 lg:px-8 py-5 space-y-5">

        @if ($total == 0)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 lg:p-5 flex items-center gap-3" id="empty-data-alert">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900">Data Latih Kosong / Model Belum Dilatih</h3>
                    <p class="text-xs text-amber-700 mt-0.5">Belum ada data latih untuk diuji pada Confusion Matrix. Silakan buka menu <a href="{{ route('data-latih') }}" class="font-bold underline text-amber-900 hover:text-amber-800">Kelola Data Latih</a> untuk mengisi data dan klik <strong>"⚡ Pembentukan Model C4.5"</strong>.</p>
                </div>
            </div>
        @endif

        {{-- Metric Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4" id="metric-cards-matrix">
            @foreach ($metricCards as $i => $card)
                @php
                    $colorMap = [
                        'green' => ['bg' => 'bg-green-700', 'light' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'bar' => 'bg-green-700'],
                        'teal' => ['bg' => 'bg-teal-600', 'light' => 'bg-teal-50', 'text' => 'text-teal-700', 'border' => 'border-teal-200', 'bar' => 'bg-teal-600'],
                        'blue' => ['bg' => 'bg-blue-600', 'light' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'bar' => 'bg-blue-600'],
                        'purple' => ['bg' => 'bg-purple-600', 'light' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'bar' => 'bg-purple-600'],
                    ];
                    $c = $colorMap[$card['color']];
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border {{ $c['border'] }} p-4 lg:p-5 hover:shadow-md transition-shadow"
                     id="metric-{{ Str::slug($card['label']) }}">
                    <div class="flex items-start justify-between mb-2">
                        <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center shadow-md">
                            @switch($card['icon'])
                                @case('award')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                                    </svg>
                                    @break
                                @case('target')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
                                    </svg>
                                    @break
                                @case('refresh')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
                                    </svg>
                                    @break
                                @case('trending-up')
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                                    </svg>
                                    @break
                            @endswitch
                        </div>
                        <span class="text-xs font-semibold {{ $c['text'] }} {{ $c['light'] }} rounded-full px-1.5 py-0.5">C4.5</span>
                    </div>
                    <p class="text-2xl lg:text-3xl font-extrabold text-gray-800 mb-0.5">{{ $card['value'] }}</p>
                    <p class="text-xs lg:text-sm font-semibold text-gray-600">{{ $card['label'] }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 mb-2 font-mono">{{ $card['desc'] }}</p>
                    {{-- Progress bar --}}
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full {{ $c['bar'] }}" style="width: {{ $card['raw'] * 100 }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5 leading-tight">{{ $card['detail'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Confusion Matrix + Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 lg:gap-5">

            {{-- Confusion Matrix 2x2 --}}
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 lg:p-6" id="section-confusion-matrix">
                <div class="mb-4">
                    <h2 class="text-sm lg:text-base font-bold text-gray-800">Confusion Matrix — Algoritma C4.5</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Kelas: Layak (+) · Tidak Layak (−) · Total: {{ $total }} sampel latih historis</p>
                </div>

                <div class="overflow-x-auto">
                    <div class="min-w-[280px]">
                        {{-- Header row --}}
                        <div class="flex mb-1">
                            <div class="w-20 shrink-0"></div>
                            <div class="flex-1 text-center">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Prediksi Model</p>
                            </div>
                        </div>
                        <div class="flex mb-2">
                            <div class="w-20 shrink-0"></div>
                            <div class="flex-1 grid grid-cols-2 gap-2 text-center">
                                <div class="text-xs font-semibold text-green-700 bg-green-50 rounded-lg py-1.5">Layak (+)</div>
                                <div class="text-xs font-semibold text-red-600 bg-red-50 rounded-lg py-1.5">Tidak Layak (−)</div>
                            </div>
                        </div>

                        {{-- Matrix body --}}
                        <div class="flex gap-2">
                            {{-- Aktual label (rotated) --}}
                            <div class="w-20 shrink-0 flex flex-col justify-center items-center">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider -rotate-90 whitespace-nowrap">Aktual DLH</p>
                            </div>

                            <div class="flex-1 flex flex-col gap-2">
                                {{-- Row 1: Aktual Layak --}}
                                <div class="flex gap-2 items-stretch">
                                    <div class="w-16 flex items-center justify-center shrink-0">
                                        <span class="text-xs font-semibold text-green-700 bg-green-50 rounded-lg py-1 px-1.5 text-center leading-tight">Layak (+)</span>
                                    </div>
                                    {{-- True Positive --}}
                                    <div class="flex-1 bg-green-600 rounded-2xl p-3 lg:p-4 flex flex-col items-center justify-center shadow-md" id="cell-tp">
                                        <p class="text-xs font-bold text-green-100 uppercase tracking-wider mb-0.5">True Positive</p>
                                        <p class="text-4xl lg:text-5xl font-black text-white">{{ $matrix['tp'] }}</p>
                                        <p class="text-xs text-green-200 mt-0.5">{{ $total > 0 ? number_format(($matrix['tp'] / $total) * 100, 1) : '0.0' }}%</p>
                                        <div class="mt-1.5 bg-green-500 rounded-full px-2 py-0.5">
                                            <p class="text-xs text-green-100 font-semibold">✓ Benar</p>
                                        </div>
                                    </div>
                                    {{-- False Negative --}}
                                    <div class="flex-1 bg-orange-100 border-2 border-orange-300 rounded-2xl p-3 lg:p-4 flex flex-col items-center justify-center" id="cell-fn">
                                        <p class="text-xs font-bold text-orange-600 uppercase tracking-wider mb-0.5">False Negative</p>
                                        <p class="text-4xl lg:text-5xl font-black text-orange-700">{{ $matrix['fn'] }}</p>
                                        <p class="text-xs text-orange-500 mt-0.5">{{ $total > 0 ? number_format(($matrix['fn'] / $total) * 100, 1) : '0.0' }}%</p>
                                        <div class="mt-1.5 bg-orange-200 rounded-full px-2 py-0.5">
                                            <p class="text-xs text-orange-600 font-semibold">Tipe II</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 2: Aktual Tidak Layak --}}
                                <div class="flex gap-2 items-stretch">
                                    <div class="w-16 flex items-center justify-center shrink-0">
                                        <span class="text-xs font-semibold text-red-600 bg-red-50 rounded-lg py-1 px-1.5 text-center leading-tight">Tidak Layak (−)</span>
                                    </div>
                                    {{-- False Positive --}}
                                    <div class="flex-1 bg-red-100 border-2 border-red-300 rounded-2xl p-3 lg:p-4 flex flex-col items-center justify-center" id="cell-fp">
                                        <p class="text-xs font-bold text-red-600 uppercase tracking-wider mb-0.5">False Positive</p>
                                        <p class="text-4xl lg:text-5xl font-black text-red-700">{{ $matrix['fp'] }}</p>
                                        <p class="text-xs text-red-500 mt-0.5">{{ $total > 0 ? number_format(($matrix['fp'] / $total) * 100, 1) : '0.0' }}%</p>
                                        <div class="mt-1.5 bg-red-200 rounded-full px-2 py-0.5">
                                            <p class="text-xs text-red-600 font-semibold">Tipe I</p>
                                        </div>
                                    </div>
                                    {{-- True Negative --}}
                                    <div class="flex-1 bg-teal-600 rounded-2xl p-3 lg:p-4 flex flex-col items-center justify-center shadow-md" id="cell-tn">
                                        <p class="text-xs font-bold text-teal-100 uppercase tracking-wider mb-0.5">True Negative</p>
                                        <p class="text-4xl lg:text-5xl font-black text-white">{{ $matrix['tn'] }}</p>
                                        <p class="text-xs text-teal-200 mt-0.5">{{ $total > 0 ? number_format(($matrix['tn'] / $total) * 100, 1) : '0.0' }}%</p>
                                        <div class="mt-1.5 bg-teal-500 rounded-full px-2 py-0.5">
                                            <p class="text-xs text-teal-100 font-semibold">✓ Benar</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span>Benar: <strong class="text-green-700">{{ $matrix['tp'] + $matrix['tn'] }}</strong></span>
                            <span>Salah: <strong class="text-red-600">{{ $matrix['fp'] + $matrix['fn'] }}</strong></span>
                            <span>Total Dataset: <strong class="text-gray-700">{{ $total }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Radar + Pie Charts --}}
            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">

                {{-- Radar Chart --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 lg:p-5" id="section-radar-chart">
                    <h3 class="text-sm font-bold text-gray-800 mb-0.5">Radar Performa Model</h3>
                    <p class="text-xs text-gray-400 mb-3">Algoritma C4.5 vs threshold 80%</p>
                    <div class="relative h-[160px] w-full">
                        <canvas id="chart-radar"></canvas>
                    </div>
                </div>

                {{-- Pie Chart --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 lg:p-5" id="section-pie-chart">
                    <h3 class="text-sm font-bold text-gray-800 mb-0.5">Distribusi Prediksi</h3>
                    <p class="text-xs text-gray-400 mb-3">TP / FP / FN / TN</p>
                    <div class="flex items-center gap-3">
                        <div class="relative w-24 h-24 shrink-0">
                            <canvas id="chart-pie"></canvas>
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm shrink-0 bg-green-700"></div>
                                <span class="text-xs text-gray-600 flex-1 truncate">True Positive (TP)</span>
                                <span class="text-xs font-bold text-gray-700">{{ $matrix['tp'] }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm shrink-0 bg-red-500"></div>
                                <span class="text-xs text-gray-600 flex-1 truncate">False Positive (FP)</span>
                                <span class="text-xs font-bold text-gray-700">{{ $matrix['fp'] }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm shrink-0 bg-orange-500"></div>
                                <span class="text-xs text-gray-600 flex-1 truncate">False Negative (FN)</span>
                                <span class="text-xs font-bold text-gray-700">{{ $matrix['fn'] }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm shrink-0 bg-teal-600"></div>
                                <span class="text-xs text-gray-600 flex-1 truncate">True Negative (TN)</span>
                                <span class="text-xs font-bold text-gray-700">{{ $matrix['tn'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Penjelasan Akademis & Rumus Matematika Confusion Matrix --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4" id="section-rumus-matematika">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                        Konsep & Rumus Matematika Evaluasi Confusion Matrix
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Standar pengukuran performa klasifikasi biner pada Algoritma C4.5</p>
                </div>
                <span class="text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-1.5">K = 5 Folds</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-gradient-to-br from-green-50 to-emerald-50/50 p-4 rounded-xl border border-green-200">
                    <p class="font-bold text-green-800 text-sm mb-1">1. Akurasi (Accuracy)</p>
                    <p class="text-gray-600 mb-2">Mengukur rasio kebenaran prediksi total (positif & negatif) terhadap seluruh sampel.</p>
                    <div class="bg-white p-2 rounded border border-green-200 font-mono text-center font-bold text-green-700">
                        Accuracy = (TP + TN) / (TP + TN + FP + FN)
                    </div>
                </div>

                <div class="bg-gradient-to-br from-teal-50 to-cyan-50/50 p-4 rounded-xl border border-teal-200">
                    <p class="font-bold text-teal-800 text-sm mb-1">2. Presisi (Precision)</p>
                    <p class="text-gray-600 mb-2">Tingkat ketepatan antara data yang diprediksi Layak dengan data aktual Layak.</p>
                    <div class="bg-white p-2 rounded border border-teal-200 font-mono text-center font-bold text-teal-700">
                        Precision = TP / (TP + FP)
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-indigo-50/50 p-4 rounded-xl border border-blue-200">
                    <p class="font-bold text-blue-800 text-sm mb-1">3. Recall (Sensitivity)</p>
                    <p class="text-gray-600 mb-2">Rasio keberhasilan model dalam menemukan kembali seluruh data aktual Layak.</p>
                    <div class="bg-white p-2 rounded border border-blue-200 font-mono text-center font-bold text-blue-700">
                        Recall = TP / (TP + FN)
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="bg-purple-50/70 p-4 rounded-xl border border-purple-200">
                    <p class="font-bold text-purple-800 text-sm mb-1">4. F1-Score (Harmonic Mean)</p>
                    <p class="text-gray-600 mb-2">Rata-rata harmonis antara Presisi dan Recall untuk mengukur keseimbangan model.</p>
                    <div class="bg-white p-2 rounded border border-purple-200 font-mono text-center font-bold text-purple-700">
                        F1-Score = 2 × (Precision × Recall) / (Precision + Recall)
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <p class="font-bold text-gray-800 text-sm mb-1">5. Spesifisitas (Specificity)</p>
                    <p class="text-gray-600 mb-2">Kemampuan model mengenali lokasi TPS yang Tidak Layak secara akurat.</p>
                    <div class="bg-white p-2 rounded border border-gray-200 font-mono text-center font-bold text-gray-700">
                        Specificity = TN / (TN + FP)
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Evaluasi K-Fold Cross Validation (Fold 1 - 5) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 lg:p-5" id="section-riwayat-evaluasi">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-sm lg:text-base font-bold text-gray-800">Hasil Evaluasi 5-Fold Cross Validation (C4.5 Training per Fold)</h2>
                    <p class="text-xs text-gray-400">Model C4.5 di-training pada (K-1) fold dan diuji pada fold validasi independen</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-full px-3 py-1">
                        Rata-rata Akurasi K-Fold: {{ $eval['kfold_avg_accuracy'] ?? $eval['accuracy'] }}%
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-sm min-w-[500px]" id="tabel-riwayat-evaluasi">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fold / Iterasi</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sampel Uji</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">TP / TN / FP / FN</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Akurasi</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Presisi</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Recall</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">F1-Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classHistory as $i => $row)
                            <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors"
                                id="row-iter-{{ $i + 1 }}">
                                <td class="px-4 py-3">
                                    <span class="text-xs font-bold text-gray-700">
                                        Fold {{ $row['iterasi'] ?? ($i + 1) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-500 font-semibold">
                                    {{ $row['sample_size'] ?? '-' }} data
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-mono">
                                    <span class="text-green-700 font-bold">{{ $row['tp'] ?? 0 }}</span> /
                                    <span class="text-teal-700 font-bold">{{ $row['tn'] ?? 0 }}</span> /
                                    <span class="text-red-600 font-bold">{{ $row['fp'] ?? 0 }}</span> /
                                    <span class="text-orange-600 font-bold">{{ $row['fn'] ?? 0 }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-extrabold text-green-700">{{ $row['accuracy'] }}%</span>
                                </td>
                                <td class="px-4 py-3 text-center"><span class="text-xs text-gray-600 font-medium">{{ $row['precision'] }}%</span></td>
                                <td class="px-4 py-3 text-center"><span class="text-xs text-gray-600 font-medium">{{ $row['recall'] }}%</span></td>
                                <td class="px-4 py-3 text-center"><span class="text-xs font-semibold text-purple-700">{{ $row['f1_score'] ?? $row['f1'] ?? '-' }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-xs text-gray-400">Belum ada data evaluasi. Silakan tambahkan data latih terlebih dahulu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // CHART.JS — Radar Chart
    // ============================================
    const radarCtx = document.getElementById('chart-radar').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Akurasi', 'Presisi', 'Recall', 'F1-Score', 'Spesifisitas'],
            datasets: [{
                label: 'C4.5',
                data: [
                    {{ $eval['accuracy'] }},
                    {{ $eval['precision'] }},
                    {{ $eval['recall'] }},
                    {{ $eval['f1_score'] }},
                    {{ ($matrix['tn'] + $matrix['fp']) > 0 ? number_format(($matrix['tn'] / ($matrix['tn'] + $matrix['fp'])) * 100, 1) : '87.5' }}
                ],
                borderColor: '#2E7D32',
                backgroundColor: 'rgba(46, 125, 50, 0.15)',
                borderWidth: 2,
                pointBackgroundColor: '#2E7D32',
                pointBorderColor: '#fff',
                pointBorderWidth: 1,
                pointRadius: 3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { font: { size: 9 }, color: '#6b7280', stepSize: 20 },
                    pointLabels: { font: { size: 9 }, color: '#6b7280' },
                    grid: { color: '#e5e7eb' },
                    angleLines: { color: '#e5e7eb' },
                },
            },
        },
    });

    // ============================================
    // CHART.JS — Doughnut/Pie Chart
    // ============================================
    const pieCtx = document.getElementById('chart-pie').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['TP', 'FP', 'FN', 'TN'],
            datasets: [{
                data: [{{ $matrix['tp'] }}, {{ $matrix['fp'] }}, {{ $matrix['fn'] }}, {{ $matrix['tn'] }}],
                backgroundColor: ['#2E7D32', '#ef5350', '#f57c00', '#00695C'],
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '55%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.label + ': ' + ctx.raw + ' sampel';
                        }
                    },
                    backgroundColor: '#fff',
                    titleColor: '#374151',
                    bodyColor: '#6b7280',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    cornerRadius: 8,
                },
            },
        },
    });

});
</script>
@endsection
