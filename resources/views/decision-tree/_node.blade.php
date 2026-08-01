{{-- Recursive tree node partial for Decision Tree visualization --}}
@php
    $node = $node ?? [];
    $depth = $depth ?? 0;
    $indent = $depth * 2;
@endphp

@if(($node['type'] ?? '') === 'leaf')
    {{-- Leaf Node --}}
    @php
        $isLayak = strtolower($node['label'] ?? '') === 'layak';
    @endphp
    <div class="ml-{{ min($indent, 12) }} my-1.5 inline-flex items-center gap-2">
        <div class="w-5 h-5 rounded-full {{ $isLayak ? 'bg-emerald-500' : 'bg-red-500' }} flex items-center justify-center shrink-0">
            @if($isLayak)
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path d="m5 12 5 5L20 7"/>
                </svg>
            @else
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            @endif
        </div>
        <span class="text-sm font-bold {{ $isLayak ? 'text-emerald-700' : 'text-red-700' }}">
            {{ $isLayak ? 'LAYAK' : 'TIDAK LAYAK' }}
        </span>
        <span class="text-xs text-gray-400">(n={{ $node['count'] ?? 0 }})</span>
    </div>

@elseif(($node['type'] ?? '') === 'node')
    {{-- Internal Node --}}
    @php
        $attrLabel = match($node['attribute'] ?? '') {
            'kepadatan' => 'Kepadatan Penduduk',
            'jarak_permukiman' => 'Jarak Permukiman',
            'jarak_air' => 'Jarak Sumber Air',
            default => $node['attribute'] ?? 'Unknown',
        };
        $nodeColors = match($depth) {
            0 => 'bg-indigo-100 border-indigo-300 text-indigo-800',
            1 => 'bg-purple-100 border-purple-300 text-purple-800',
            default => 'bg-sky-100 border-sky-300 text-sky-800',
        };
    @endphp

    <div class="{{ $depth > 0 ? 'ml-6 border-l-2 border-gray-200 pl-4' : '' }} my-2">
        {{-- Node Box --}}
        <div class="inline-flex items-center gap-2 mb-2">
            <div class="px-3 py-1.5 rounded-lg border-2 {{ $nodeColors }} text-sm font-bold shadow-sm">
                @if($depth === 0)
                    <span class="text-[10px] font-bold uppercase tracking-wider opacity-70 mr-1">ROOT:</span>
                @endif
                {{ $attrLabel }}
            </div>
            <span class="text-xs text-gray-400">Gain={{ round($node['gain'] ?? 0, 4) }}</span>
        </div>

        {{-- Children Branches --}}
        @if(isset($node['children']))
            @foreach($node['children'] as $value => $child)
                <div class="ml-4 border-l-2 border-dashed border-gray-300 pl-4 my-1">
                    {{-- Branch Label --}}
                    <div class="flex items-center gap-2 my-1.5">
                        <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                        <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">= {{ $value }}</span>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>
                    {{-- Recursive child --}}
                    @include('decision-tree._node', ['node' => $child, 'depth' => $depth + 1])
                </div>
            @endforeach
        @endif
    </div>
@endif
