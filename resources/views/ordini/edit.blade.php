<x-layouts.app title="Modifica Ordine">
    <x-page-header title="Modifica Ordine" description="Modifica i dettagli dell'ordine">
        <a href="{{ route('ordini.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Torna alla lista
        </a>
    </x-page-header>

    @php
        $flow = app(\App\Support\ProductionFlowStepper::class)->forOrdine($ordine);
    @endphp
    <x-production-flow-stepper
        :steps="$flow['steps']"
        :context-label="$flow['context_label']"
    />

    <livewire:forms.ordine-form :ordine="$ordine" />
</x-layouts.app>
