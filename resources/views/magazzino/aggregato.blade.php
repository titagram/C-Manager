<x-layouts.app title="Magazzino - Vista Aggregata">
    <x-page-header title="Magazzino - Vista Aggregata" description="Giacenze aggregate per prodotto con stato FITOK e scarti">
        <a href="{{ route('magazzino.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
            </svg>
            Vista Lotti
        </a>
        @if(auth()->user()?->isAdmin())
            <a href="{{ route('magazzino.carico') }}" class="btn-primary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuovo Carico
            </a>
            <a href="{{ route('magazzino.scarico') }}" class="btn-secondary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                </svg>
                Scarico
            </a>
        @endif
    </x-page-header>

    <livewire:magazzino-aggregato />
</x-layouts.app>
