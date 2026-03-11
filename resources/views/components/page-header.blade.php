@props([
    'title',
    'description' => null
])

<div class="mb-6">
    <h1 class="text-2xl font-semibold">{{ $title }}</h1>
    @if($description)
        <p class="text-muted-foreground mt-1">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
