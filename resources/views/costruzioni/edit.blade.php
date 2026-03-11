<x-layouts.app title="Modifica Costruzione">
    <x-page-header title="Modifica Costruzione" description="Aggiorna i dettagli della costruzione">
        <a href="{{ route('costruzioni.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Torna alla lista
        </a>
    </x-page-header>

    <div x-data="{ tab: 'dettagli' }" class="space-y-6">
        <!-- Tabs Header -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button 
                    @click="tab = 'dettagli'" 
                    :class="tab === 'dettagli' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                >
                    Dettagli Generali
                </button>
                <button 
                    @click="tab = 'componenti'" 
                    :class="tab === 'componenti' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                >
                    Componenti
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div x-show="tab === 'dettagli'" style="display: none;">
            <livewire:forms.costruzione-form :costruzione="$costruzione" />
        </div>

        <div x-show="tab === 'componenti'" style="display: none;">
            <div class="card p-6">
                <livewire:costruzioni.componenti-manager :costruzione="$costruzione" />
            </div>
        </div>
    </div>
</x-layouts.app>
