<x-layouts.app title="Clienti">
    <x-page-header title="Clienti" description="Anagrafica clienti">
        <a href="{{ route('clienti.create') }}" class="btn-primary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuovo Cliente
        </a>
    </x-page-header>

    <livewire:tables.clienti-table />
</x-layouts.app>
