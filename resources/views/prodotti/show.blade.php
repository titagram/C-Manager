<x-layouts.app title="Modifica Prodotto">
    <x-page-header title="Modifica Prodotto" description="Aggiorna i dati del prodotto">
        <a href="{{ route('prodotti.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Torna alla lista
        </a>
    </x-page-header>

    @php
        $prodottoModel = \App\Models\Prodotto::findOrFail($prodotto);
    @endphp

    <livewire:forms.prodotto-form :prodotto="$prodottoModel" />
</x-layouts.app>
