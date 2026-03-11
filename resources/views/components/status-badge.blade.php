@props([
    'status',
    'type' => 'info'
])

@php
    $classes = match($type) {
        'success' => 'status-badge status-badge-success',
        'warning' => 'status-badge status-badge-warning',
        'destructive', 'error' => 'status-badge status-badge-destructive',
        default => 'status-badge status-badge-info',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $status }}
</span>
