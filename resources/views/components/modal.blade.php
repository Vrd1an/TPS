{{-- Modal Component --}}
{{-- Usage:
    <x-modal id="modal-tambah-data" title="Tambah Data" maxWidth="md">
        <p>Modal content here...</p>
        <x-slot:footer>
            <button>Batal</button>
            <button>Simpan</button>
        </x-slot:footer>
    </x-modal>

    Open with: @click="$dispatch('open-modal', 'modal-tambah-data')"
    Or: Alpine x-data="{ open: false }" pattern
--}}

@props([
    'id' => 'modal',
    'title' => '',
    'maxWidth' => 'md',
])

@php
    $maxWidthClass = match($maxWidth) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        default => 'max-w-md',
    };
@endphp

<div x-show="open" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     id="{{ $id }}">

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.outside="open = false"
         class="bg-white rounded-2xl shadow-2xl w-full {{ $maxWidthClass }}">

        {{-- Header --}}
        @if ($title)
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">{{ $title }}</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        @endif

        {{-- Body --}}
        <div class="p-6">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @if (isset($footer))
            <div class="px-6 pb-5 flex gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
