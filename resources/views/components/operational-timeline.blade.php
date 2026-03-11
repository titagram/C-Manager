@props([
    'title' => 'Timeline audit',
    'events' => collect(),
])

@php
    $eventsCollection = $events instanceof \Illuminate\Support\Collection ? $events : collect($events);
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        @if($eventsCollection->isEmpty())
            <p class="text-sm text-muted-foreground">Nessun evento audit disponibile.</p>
        @else
            <ol class="space-y-3">
                @foreach($eventsCollection as $event)
                    @php
                        $tone = (string) ($event['tone'] ?? 'slate');
                        $accentClass = match ($tone) {
                            'indigo' => 'border-indigo-300 bg-indigo-50/50',
                            'violet' => 'border-violet-300 bg-violet-50/50',
                            'amber' => 'border-amber-300 bg-amber-50/50',
                            'green' => 'border-green-300 bg-green-50/50',
                            'rose' => 'border-rose-300 bg-rose-50/50',
                            default => 'border-slate-300 bg-slate-50/50',
                        };
                    @endphp
                    <li class="rounded-md border {{ $accentClass }} px-3 py-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-foreground">{{ $event['title'] }}</p>
                                @if(!empty($event['description']))
                                    <p class="text-xs text-muted-foreground mt-1">{{ $event['description'] }}</p>
                                @endif
                            </div>
                            <time class="text-xs text-muted-foreground whitespace-nowrap">{{ $event['at_label'] }}</time>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
