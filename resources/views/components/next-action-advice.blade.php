@props([
    'advice' => null,
])

@if($advice)
    @php
        $level = (string) ($advice['level'] ?? 'info');
        [$wrapperClass, $titleClass, $messageClass, $buttonClass] = match ($level) {
            'success' => [
                'border-green-200 bg-green-50/60',
                'text-green-900',
                'text-green-800',
                'btn-success',
            ],
            'warning' => [
                'border-amber-200 bg-amber-50/60',
                'text-amber-900',
                'text-amber-800',
                'btn-secondary',
            ],
            'muted' => [
                'border-slate-200 bg-slate-50/60',
                'text-slate-900',
                'text-slate-700',
                'btn-secondary',
            ],
            default => [
                'border-blue-200 bg-blue-50/60',
                'text-blue-900',
                'text-blue-800',
                'btn-primary',
            ],
        };
    @endphp

    <div class="rounded-lg border {{ $wrapperClass }} px-4 py-3 mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide {{ $titleClass }}">Prossima azione consigliata</p>
        <h3 class="mt-1 text-sm font-semibold {{ $titleClass }}">{{ $advice['title'] }}</h3>
        <p class="mt-1 text-sm {{ $messageClass }}">{{ $advice['message'] }}</p>

        @if(!empty($advice['cta_url']) && !empty($advice['cta_label']))
            <div class="mt-3">
                <a href="{{ $advice['cta_url'] }}" class="{{ $buttonClass }}">
                    {{ $advice['cta_label'] }}
                </a>
            </div>
        @endif
    </div>
@endif
