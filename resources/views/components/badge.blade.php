{{-- Badge Component --}}
{{-- Usage: <x-badge type="kepadatan" value="Tinggi" /> --}}
{{-- Usage: <x-badge type="label" value="Layak" /> --}}
{{-- Usage: <x-badge type="jarak" value="Sedang" /> --}}

@props([
    'type' => 'default',
    'value' => '',
])

@php
    $classes = 'text-xs font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1';

    switch ($type) {
        case 'kepadatan':
            $colorClass = match($value) {
                'Tinggi' => 'bg-red-100 text-red-700',
                'Sedang' => 'bg-yellow-100 text-yellow-700',
                'Rendah' => 'bg-green-100 text-green-700',
                default  => 'bg-gray-100 text-gray-600',
            };
            break;
        case 'label':
            $colorClass = match($value) {
                'Layak' => 'bg-green-100 text-green-700 font-bold',
                'Tidak Layak' => 'bg-red-100 text-red-600 font-bold',
                default => 'bg-gray-100 text-gray-600',
            };
            break;
        case 'jarak':
            $colorClass = 'bg-gray-100 text-gray-600';
            break;
        default:
            $colorClass = 'bg-gray-100 text-gray-600';
    }
@endphp

<span class="{{ $classes }} {{ $colorClass }} badge-{{ $type }}">
    @if ($type === 'label' && $value === 'Layak')
        <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
        </svg>
    @elseif ($type === 'label' && $value === 'Tidak Layak')
        <svg class="w-[11px] h-[11px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
        </svg>
    @endif
    {{ $type === 'label' && $value === 'Tidak Layak' ? 'TL' : $value }}
</span>
