@props([
    'text',
    'placement' => 'top',
])

@php
    $tooltipPositionClasses = $placement === 'bottom'
        ? 'top-full left-1/2 z-30 mt-2 -translate-x-1/2'
        : 'bottom-full left-1/2 z-30 mb-2 -translate-x-1/2';
@endphp

<span class="group relative inline-flex items-center align-middle">
    <button
        type="button"
        class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-border text-[10px] font-semibold leading-none text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
        aria-label="{{ $text }}"
        title="{{ $text }}"
    >
        ?
    </button>
    <span
        role="tooltip"
        class="{{ $tooltipPositionClasses }} pointer-events-none invisible absolute w-64 scale-95 rounded-md bg-slate-900 px-2 py-1 text-xs leading-5 text-white opacity-0 shadow-lg transition-all duration-150 group-hover:visible group-hover:scale-100 group-hover:opacity-100 group-focus-within:visible group-focus-within:scale-100 group-focus-within:opacity-100"
    >
        {{ $text }}
    </span>
</span>
