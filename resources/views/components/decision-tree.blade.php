{{-- Decision Tree Visualization Component --}}
{{-- Usage: <x-decision-tree :rules="['Jarak Air = Jauh', 'Jarak Permukiman = Sedang', 'Kepadatan = Tinggi', 'LAYAK']" status="layak" /> --}}

@props([
    'rules' => [],
    'status' => 'layak',
])

<div class="space-y-1" id="decision-tree-viz">
    @foreach ($rules as $index => $step)
        @php $isLast = $index === count($rules) - 1; @endphp
        <div class="flex items-start gap-2">
            <div class="flex flex-col items-center mt-0.5">
                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0
                    {{ $isLast
                        ? ($status === 'layak' ? 'bg-green-600 border-green-600' : 'bg-red-600 border-red-600')
                        : 'bg-white border-gray-300' }}">
                    <div class="w-1.5 h-1.5 rounded-full {{ $isLast ? 'bg-white' : 'bg-gray-400' }}"></div>
                </div>
                @if (!$isLast)
                    <div class="w-0.5 h-3 bg-gray-200 mt-0.5"></div>
                @endif
            </div>
            <span class="text-xs leading-relaxed
                {{ $isLast
                    ? ($status === 'layak' ? 'font-bold text-green-700' : 'font-bold text-red-600')
                    : 'text-gray-600' }}">
                {{ $step }}
            </span>
        </div>
    @endforeach
</div>
