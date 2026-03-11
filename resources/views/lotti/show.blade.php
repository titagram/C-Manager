<x-layouts.app title="Dettaglio Lotto Produzione">
    <x-page-header title="Dettaglio Lotto Produzione" description="Visualizza e modifica il lotto">
        <a href="{{ route('lotti.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Torna alla lista
        </a>
    </x-page-header>

    @php
        $lottoModel = $lotto instanceof \App\Models\LottoProduzione
            ? $lotto->loadMissing([
                'preventivo',
                'ordine',
                'consumiMateriale.lottoMateriale',
                'movimenti.lottoMateriale',
                'scarti',
            ])
            : \App\Models\LottoProduzione::query()
                ->with([
                    'preventivo',
                    'ordine',
                    'consumiMateriale.lottoMateriale',
                    'movimenti.lottoMateriale',
                    'scarti',
                ])
                ->findOrFail($lotto);
        $flow = app(\App\Support\ProductionFlowStepper::class)->forLotto($lottoModel);
        $timeline = app(\App\Support\OperationalTimeline::class)->forLotto($lottoModel);
        $nextAction = app(\App\Support\NextActionAdvisor::class)->forLotto($lottoModel);
    @endphp

    <x-production-flow-stepper
        :steps="$flow['steps']"
        :context-label="$flow['context_label']"
    />

    <x-next-action-advice :advice="$nextAction" />

    <div class="mb-6">
        <x-operational-timeline
            title="Timeline audit lotto"
            :events="$timeline"
        />
    </div>

    <livewire:forms.lotto-produzione-form :lotto="$lottoModel" />
</x-layouts.app>
