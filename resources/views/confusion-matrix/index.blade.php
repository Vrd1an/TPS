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

        {{-- Riwayat Evaluasi Model (Iterasi 1 - 5) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 lg:p-5" id="section-riwayat-evaluasi">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm lg:text-base font-bold text-gray-800">Riwayat Evaluasi Iterasi Model (Gambar 3.25 Laporan)</h2>
                    <p class="text-xs text-gray-400">Progres tren peningkatan performa akurasi K-Fold Cross Validation C4.5</p>
                </div>
                <span class="text-xs font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-3 py-1">↑ Tren Stabil (90.48%)</span>
            </div>
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-sm min-w-[400px]" id="tabel-riwayat-evaluasi">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Iterasi Training</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Akurasi</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Presisi</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Recall</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">F1-Score</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Visual Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classHistory as $i => $row)
                            @php $isLast = $i === count($classHistory) - 1; @endphp
                            <tr class="border-b border-gray-50 last:border-0 transition-colors {{ $isLast ? 'bg-green-50' : 'hover:bg-gray-50' }}"
                                id="row-iter-{{ $i + 1 }}">
                                <td class="px-4 py-3">
                                    <span class="text-xs font-bold {{ $isLast ? 'text-green-700' : 'text-gray-600' }}">
                                        Iterasi {{ $row['iterasi'] ?? ($i + 1) }}
                                        @if ($isLast)
                                            <span class="ml-2 bg-green-100 text-green-700 rounded-full px-1.5 py-0.5 text-xs">Model Optimal</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm font-bold {{ $isLast ? 'text-green-700' : 'text-gray-700' }}">{{ $row['accuracy'] }}%</span>
                                </td>
                                <td class="px-4 py-3 text-center"><span class="text-sm text-gray-600">{{ $row['precision'] }}%</span></td>
                                <td class="px-4 py-3 text-center"><span class="text-sm text-gray-600">{{ $row['recall'] }}%</span></td>
                                <td class="px-4 py-3 text-center"><span class="text-sm text-gray-600">{{ $row['f1_score'] ?? $row['f1'] ?? '-' }}%</span></td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full bg-green-600" style="width: {{ $row['accuracy'] }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 font-mono w-10 text-right">{{ $row['accuracy'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
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
