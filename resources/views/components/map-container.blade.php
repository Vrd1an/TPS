{{-- Map Container Component (Leaflet.js Wrapper) --}}
{{-- Usage: <x-map-container mapId="map-dashboard" height="h-72" :showLegend="true" /> --}}
{{-- The actual Leaflet initialization should be done in the page's @yield('scripts') section --}}

@props([
    'mapId' => 'map-container',
    'height' => 'h-72',
    'showLegend' => true,
    'showZoom' => true,
    'showScale' => true,
])

<div class="relative w-full {{ $height }} rounded-xl overflow-hidden border border-gray-200 shadow-inner"
     id="{{ $mapId }}-wrapper">

    {{-- Leaflet Map Target --}}
    <div id="{{ $mapId }}" class="w-full h-full z-0"></div>

    @if ($showLegend)
        {{-- Legend overlay --}}
        <div class="absolute bottom-3 left-3 bg-white bg-opacity-95 rounded-lg px-3 py-2 shadow-md border border-gray-200 z-[400]"
             id="{{ $mapId }}-legend">
            <p class="text-xs font-semibold text-gray-600 mb-1.5">Keterangan</p>
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-700 border border-white shadow-sm"></div>
                    <span class="text-xs text-gray-600">Layak</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-700 border border-white shadow-sm"></div>
                    <span class="text-xs text-gray-600">Tidak Layak</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-yellow-600 border border-white shadow-sm"></div>
                    <span class="text-xs text-gray-600">Dalam Proses</span>
                </div>
            </div>
        </div>
    @endif

    {{-- QGIS Integration badge --}}
    <div class="absolute top-3 right-3 bg-green-700 text-white rounded-lg px-3 py-1.5 text-xs font-bold shadow-md flex items-center gap-1.5 z-[400]">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/>
            <path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
        </svg>
        QGIS Integration
    </div>
</div>
