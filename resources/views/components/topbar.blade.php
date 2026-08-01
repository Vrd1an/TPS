{{-- Topbar Component --}}
{{-- Usage: <x-topbar title="Dashboard Utama" subtitle="SIG Kelayakan Lokasi TPS"> <x-slot:actions>...</x-slot:actions> </x-topbar> --}}

@props([
    'title' => '',
    'subtitle' => '',
])

<div class="bg-white border-b border-gray-200 px-4 lg:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sticky top-0 z-10 shadow-sm"
     id="topbar">
    <div>
        <h1 class="text-base lg:text-xl font-bold text-gray-800">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-xs lg:text-sm text-gray-500 hidden sm:block">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex items-center gap-2 flex-wrap">
            {{ $actions }}
        </div>
    @endif
</div>
