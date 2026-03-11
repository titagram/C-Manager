@props([
    'title' => 'Processo operativo',
    'contextLabel' => null,
    'steps' => [],
])

<div class="card mb-6">
    <div class="card-header">
        <div class="flex flex-col gap-1">
            <h3 class="card-title">{{ $title }}</h3>
            @if ($contextLabel)
                <p class="text-sm text-muted-foreground">{{ $contextLabel }}</p>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            @foreach ($steps as $index => $step)
                @php
                    $status = $step['status'] ?? 'pending';
                    $statusLabel = $step['status_label'] ?? 'In attesa';

                    $statusClasses = match ($status) {
                        'completed' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                        'current' => 'border-blue-300 bg-blue-50 text-blue-800',
                        'inconsistent' => 'border-red-300 bg-red-50 text-red-800',
                        'skipped' => 'border-amber-300 bg-amber-50 text-amber-800',
                        default => 'border-slate-200 bg-slate-50 text-slate-700',
                    };
                @endphp

                <div class="rounded-lg border p-3 {{ $statusClasses }}">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold">{{ $step['label'] }}</span>
                        <span class="rounded-full border px-2 py-0.5 text-xs font-medium">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    @if (!empty($step['url']))
                        <a href="{{ $step['url'] }}" class="text-sm font-medium underline-offset-2 hover:underline">
                            Apri
                        </a>
                    @else
                        <span class="text-sm font-medium opacity-70">Apri</span>
                    @endif

                    <p class="mt-2 text-xs leading-5">
                        {{ $step['meta'] ?? '-' }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</div>
