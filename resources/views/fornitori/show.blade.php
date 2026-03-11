<x-layouts.app title="Modifica Fornitore">
    <x-page-header title="Modifica Fornitore" description="Aggiorna i dati del fornitore">
        <a href="{{ route('fornitori.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Torna alla lista
        </a>
    </x-page-header>

    @php
        $fornitoreModel = \App\Models\Fornitore::findOrFail($fornitore);
    @endphp

    <livewire:forms.fornitore-form :fornitore="$fornitoreModel" />
</x-layouts.app>
