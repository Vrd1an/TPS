{{-- Metric Card Component --}}
{{-- Usage: <x-metric-card title="Total Data" value="64 TPS" subtitle="Kabupaten Cianjur" icon="database" color="teal" trend="+8 dari bulan lalu" /> --}}

@props([
    'title' => '',
    'value' => '',
    'subtitle' => '',
    'icon' => 'database',
    'color' => 'teal',
    'trend' => '',
    'href' => '#',
    'detail' => '',
    'formula' => '',
])

@php
    $colorMap = [
        'teal'   => ['bg' => 'bg-teal-600',   'light' => 'bg-teal-50',   'text' => 'text-teal-700',   'border' => 'border-teal-200'],
        'amber'  => ['bg' => 'bg-amber-500',  'light' => 'bg-amber-50',  'text' => 'text-amber-700',  'border' => 'border-amber-200'],
        'green'  => ['bg' => 'bg-green-700',   'light' => 'bg-green-50',  'text' => 'text-green-700',  'border' => 'border-green-200'],
        'blue'   => ['bg' => 'bg-blue-600',    'light' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
        'purple' => ['bg' => 'bg-purple-600',  'light' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200'],
        'red'    => ['bg' => 'bg-red-600',     'light' => 'bg-red-50',    'text' => 'text-red-700',    'border' => 'border-red-200'],
    ];
    $c = $colorMap[$color] ?? $colorMap['teal'];
@endphp

<a href="{{ $href }}"
   class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-start gap-4 hover:shadow-md transition-all text-left group"
   id="metric-card-{{ Str::slug($title) }}">
    {{-- Icon --}}
    <div class="{{ $c['bg'] }} w-12 h-12 rounded-xl flex items-center justify-center shadow-md shrink-0 group-hover:scale-105 transition-transform">
        @switch($icon)
            @case('database')
                <svg class="w-[22px] h-[22px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>
                </svg>
                @break
            @case('map-pin')
                <svg class="w-[22px] h-[22px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                @break
            @case('bar-chart')
                <svg class="w-[22px] h-[22px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                @break
            @case('award')
                <svg class="w-[22px] h-[22px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                </svg>
                @break
            @case('target')
                <svg class="w-[22px] h-[22px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
                </svg>
                @break
            @case('refresh')
                <svg class="w-[22px] h-[22px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
                </svg>
                @break
            @case('trending-up')
                <svg class="w-[22px] h-[22px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                </svg>
                @break
        @endswitch
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <p class="text-sm text-gray-500 font-medium">{{ $title }}</p>
        <p class="text-2xl font-extrabold text-gray-800 mt-0.5">{{ $value }}</p>
        @if ($subtitle)
            <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
        @endif
        @if ($formula)
            <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $formula }}</p>
        @endif
        @if ($trend)
            <div class="flex items-center gap-1 mt-1.5 {{ $c['light'] }} rounded-full px-2 py-0.5 w-fit">
                <svg class="w-2.5 h-2.5 {{ $c['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                </svg>
                <span class="text-xs font-medium {{ $c['text'] }}">{{ $trend }}</span>
            </div>
        @endif
        @if ($detail)
            <p class="text-xs text-gray-400 mt-1.5 leading-tight">{{ $detail }}</p>
        @endif
    </div>
</a>
