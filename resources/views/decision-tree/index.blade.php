{{-- Halaman Visualisasi Decision Tree C4.5 --}}
@extends('layouts.app')

@section('title', 'Decision Tree C4.5')
@section('page-title', 'Decision Tree C4.5')

@php
    $activeMenu = 'decision-tree';
@endphp

@section('content')
<div class="flex-1 bg-gray-50 overflow-y-auto" id="page-decision-tree">

    {{-- Top Bar --}}
    <x-topbar title="Model Decision Tree C4.5" subtitle="Visualisasi pohon keputusan yang dibentuk dari data historis DLH">
        <x-slot:actions>
            @if($hasModel)
                <div class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-1.5">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-xs font-bold text-emerald-700">Model Aktif</span>
                </div>
            @else
                <div class="flex items-center gap-1.5 bg-gray-100 border border-gray-200 rounded-full px-2.5 py-1.5">
                    <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    <span class="text-xs font-bold text-gray-500">Belum Dibangun</span>
                </div>
            @endif
        </x-slot:actions>
    </x-topbar>

    <div class="px-4 lg:px-8 py-5 space-y-5">

        @if(!$hasModel)
            {{-- No Model Warning --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
                <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Decision Tree Belum Dibentuk</h2>
                <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                    Silakan lakukan Training Data Historis terlebih dahulu di menu Kelola Data Latih untuk membentuk Decision Tree.
                </p>
                <a href="{{ url('/data-latih') }}"
                   class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-sm px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Buka Kelola Data Latih
                </a>
            </div>
        @else
            {{-- Model Info Summary --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3" id="model-info-cards">
                <div class="bg-white rounded-xl p-4 shadow-sm text-center border border-gray-100">
                    <p class="text-2xl font-extrabold text-indigo-700">{{ $model['root_attribute'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Root Node</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm text-center border border-gray-100">
                    <p class="text-2xl font-extrabold text-emerald-700">{{ $model['total_rules'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Jumlah Rule</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm text-center border border-gray-100">
                    <p class="text-2xl font-extrabold text-purple-700">{{ $model['total_nodes'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Jumlah Node</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm text-center border border-gray-100">
                    <p class="text-2xl font-extrabold text-gray-700">{{ $model['total_data_latih'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Data Historis</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm text-center border border-gray-100">
                    <p class="text-2xl font-extrabold text-amber-600">{{ $model['entropy_total'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Entropy Total</p>
                </div>
            </div>

            {{-- Information Gain Summary --}}
            @if($trainingLog && isset($trainingLog['gain_summary']))
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="section-gain-summary">
                    <div class="border-b border-gray-100 px-5 py-4 bg-gray-50/50">
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                            Information Gain per Atribut
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @foreach($trainingLog['gain_summary'] as $attr => $gainData)
                                @php
                                    $attrLabel = match($attr) {
                                        'kepadatan' => 'Kepadatan Penduduk',
                                        'jarak_permukiman' => 'Jarak Permukiman',
                                        'jarak_air' => 'Jarak Sumber Air',
                                        default => $attr,
                                    };
                                    $isRoot = $attrLabel === $model['root_attribute'];
                                    $gainVal = $gainData['gain'] ?? 0;
                                    $barWidth = $model['entropy_total'] > 0 ? min(100, round(($gainVal / $model['entropy_total']) * 100)) : 0;
                                @endphp
                                <div class="rounded-xl p-4 {{ $isRoot ? 'bg-indigo-50 border-2 border-indigo-300' : 'bg-gray-50 border border-gray-200' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-bold {{ $isRoot ? 'text-indigo-700' : 'text-gray-700' }}">{{ $attrLabel }}</span>
                                        @if($isRoot)
                                            <span class="text-[10px] font-bold bg-indigo-600 text-white px-2 py-0.5 rounded-full">ROOT</span>
                                        @endif
                                    </div>
                                    <p class="text-2xl font-extrabold {{ $isRoot ? 'text-indigo-700' : 'text-gray-800' }}">{{ round($gainVal, 4) }}</p>
                                    <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $isRoot ? 'bg-indigo-600' : 'bg-gray-400' }}"
                                             style="width: {{ $barWidth }}%"></div>
                                    </div>

                                    {{-- Partition Details --}}
                                    @if(isset($gainData['partitions']))
                                        <div class="mt-3 space-y-1">
                                            @foreach($gainData['partitions'] as $val => $part)
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="text-gray-600">{{ $val }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-400">n={{ $part['count'] }}</span>
                                                        <span class="font-mono font-bold {{ $isRoot ? 'text-indigo-600' : 'text-gray-700' }}">E={{ $part['entropy'] }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Decision Tree Visual --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="section-tree-visual">
                <div class="border-b border-gray-100 px-5 py-4 bg-gray-50/50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                            <path d="M18 9a9 9 0 0 1-9 9"/>
                        </svg>
                        Struktur Decision Tree
                    </h2>
                </div>
                <div class="p-5 overflow-x-auto">
                    @if($tree)
                        <div class="min-w-[600px]">
                            @include('decision-tree._node', ['node' => $tree, 'depth' => 0])
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rules IF-THEN Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="section-rules-table">
                <div class="border-b border-gray-100 px-5 py-4 bg-gray-50/50 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="m16 13-3.5 3.5-2-2L8 17"/>
                        </svg>
                        Daftar Rule IF-THEN
                    </h2>
                    <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">{{ count($rules) }} Rules</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($rules as $idx => $rule)
                        @php
                            $isLayak = strtolower($rule['conclusion'] ?? '') === 'layak';
                        @endphp
                        <div class="px-5 py-4 hover:bg-gray-50/50 transition-colors">
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 w-16 text-xs font-bold {{ $isLayak ? 'text-emerald-700 bg-emerald-100' : 'text-red-700 bg-red-100' }} px-2.5 py-1 rounded-lg text-center">
                                    {{ $rule['rule_id'] }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap gap-1.5 items-center">
                                        <span class="text-xs font-bold text-gray-500">IF</span>
                                        @foreach($rule['conditions'] as $condIdx => $cond)
                                            @if($condIdx > 0)
                                                <span class="text-xs font-bold text-amber-600">AND</span>
                                            @endif
                                            <span class="text-xs font-semibold text-indigo-800 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md">{{ $cond }}</span>
                                        @endforeach
                                        <span class="text-xs font-bold text-gray-500">THEN</span>
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-md {{ $isLayak ? 'text-emerald-800 bg-emerald-100 border border-emerald-300' : 'text-red-800 bg-red-100 border border-red-300' }}">
                                            {{ $rule['conclusion'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Support: {{ $rule['support'] ?? 0 }} data</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Metadata --}}
            <div class="bg-gray-100 rounded-xl px-4 py-3 text-xs text-gray-500 flex flex-wrap gap-4">
                <span>🕒 Training: {{ $model['trained_at'] ? \Carbon\Carbon::parse($model['trained_at'])->format('d M Y, H:i:s') : '-' }}</span>
                <span>📊 Entropy Total: {{ $model['entropy_total'] }}</span>
                <span>🗄️ Data Latih: {{ $model['total_data_latih'] }} record</span>
            </div>
        @endif

    </div>
</div>
@endsection
