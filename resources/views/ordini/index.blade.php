<x-layouts.app title="Ordini">
    <x-page-header title="Ordini" description="Gestione ordini clienti">
        <a href="{{ route('ordini.create') }}" class="btn-primary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuovo Ordine
        </a>
    </x-page-header>

    @if(session('success'))
        <div class="alert alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    <livewire:tables.ordini-table />
</x-layouts.app>
