<x-layouts.app title="Modifica Lotto Produzione">
    <x-page-header title="Modifica Lotto Produzione" description="Modifica l'ordine di lavorazione">
        <a href="{{ route('lotti.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Torna alla lista
        </a>
    </x-page-header>

    @php
        $lottoModel = $lotto instanceof \App\Models\LottoProduzione
            ? $lotto
            : \App\Models\LottoProduzione::query()->findOrFail((int) $lotto);
        $flow = app(\App\Support\ProductionFlowStepper::class)->forLotto($lottoModel);
    @endphp
    <x-production-flow-stepper
        :steps="$flow['steps']"
        :context-label="$flow['context_label']"
    />

    <livewire:forms.lotto-produzione-form :lotto="$lottoModel" />
</x-layouts.app>
