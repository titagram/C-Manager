<x-layouts.app title="Modifica Preventivo">
    @php
        $preventivoModel = \App\Models\Preventivo::findOrFail($preventivo);
    @endphp

    <x-page-header title="Preventivo {{ $preventivoModel->numero }}" description="Modifica preventivo">
        <div class="flex gap-2">
            <a href="{{ route('preventivi.pdf', $preventivoModel->id) }}" class="btn-secondary" target="_blank">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Scarica PDF
            </a>
            <a href="{{ route('preventivi.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Torna alla lista
            </a>
        </div>
    </x-page-header>

    @if(session('success'))
        <div class="alert alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(!$preventivoModel->canBeEdited())
        <div class="alert alert-warning mb-6">
            Questo preventivo è in stato "{{ $preventivoModel->stato->label() }}" e non può essere modificato.
        </div>
    @endif

    @php
        $flow = app(\App\Support\ProductionFlowStepper::class)->forPreventivo($preventivoModel);
        $nextAction = app(\App\Support\NextActionAdvisor::class)->forPreventivo($preventivoModel);
    @endphp
    <x-production-flow-stepper
        :steps="$flow['steps']"
        :context-label="$flow['context_label']"
    />

    <x-next-action-advice :advice="$nextAction" />

    <livewire:forms.preventivo-form :preventivo="$preventivoModel" />
</x-layouts.app>
