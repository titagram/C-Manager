<x-layouts.app title="Nuovo Ordine">
    <x-page-header title="Nuovo Ordine" description="Crea un nuovo ordine cliente">
        <a href="{{ route('ordini.index') }}" class="btn-ghost">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Indietro
        </a>
    </x-page-header>

    <livewire:forms.ordine-form />
</x-layouts.app>
