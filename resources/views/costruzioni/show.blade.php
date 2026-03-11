<x-layouts.app title="Dettagli Costruzione">
    <x-page-header title="{{ $costruzione->nome }}" description="Visualizza i dettagli della costruzione">
        <div class="flex gap-2">
            <a href="{{ route('costruzioni.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Torna alla lista
            </a>
            <a href="{{ route('costruzioni.edit', $costruzione) }}" class="btn-primary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                Modifica
            </a>
        </div>
    </x-page-header>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informazioni Costruzione</h3>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Categoria</dt>
                    <dd class="mt-1">
                        <span class="badge badge-secondary">
                            {{ \App\Enums\TipoCostruzione::from($costruzione->categoria)->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Nome</dt>
                    <dd class="mt-1 text-sm text-foreground">{{ $costruzione->nome }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-muted-foreground">Descrizione</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        {{ $costruzione->descrizione ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Stato</dt>
                    <dd class="mt-1">
                        @if($costruzione->is_active)
                            <span class="badge badge-success">Attiva</span>
                        @else
                            <span class="badge badge-secondary">Inattiva</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">Creata il</dt>
                    <dd class="mt-1 text-sm text-foreground">
                        {{ $costruzione->created_at->format('d/m/Y H:i') }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</x-layouts.app>
