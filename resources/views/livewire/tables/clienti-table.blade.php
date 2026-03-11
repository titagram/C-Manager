<div>
    <!-- Filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Cerca per ragione sociale, P.IVA, email..."
                            class="form-input pl-10 w-full"
                        >
                    </div>
                </div>

                <!-- Stato Filter -->
                <div class="w-full md:w-40">
                    <select wire:model.live="stato" class="form-select w-full">
                        <option value="">Tutti</option>
                        <option value="attivi">Solo attivi</option>
                        <option value="inattivi">Solo inattivi</option>
                    </select>
                </div>

                <!-- Reset -->
                @if($search || $stato)
                    <button wire:click="resetFilters" class="btn-ghost">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('ragione_sociale')">
                            <div class="flex items-center gap-2">
                                Ragione Sociale
                                @if($sortField === 'ragione_sociale')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>P.IVA / C.F.</th>
                        <th>Contatti</th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('citta')">
                            <div class="flex items-center gap-2">
                                Sede
                                @if($sortField === 'citta')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="text-center" title="Numero di preventivi associati al cliente">Preventivi</th>
                        <th class="text-center" title="Numero totale di lotti di produzione associati al cliente">Lotti</th>
                        <th class="text-center">Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clienti as $cliente)
                        <tr wire:key="cliente-{{ $cliente->id }}">
                            <td>
                                <div class="font-medium">{{ $cliente->ragione_sociale }}</div>
                            </td>
                            <td class="text-sm">
                                @if($cliente->partita_iva)
                                    <div class="font-mono">{{ $cliente->partita_iva }}</div>
                                @endif
                                @if($cliente->codice_fiscale)
                                    <div class="text-muted-foreground font-mono text-xs">{{ $cliente->codice_fiscale }}</div>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if($cliente->email)
                                    <div>{{ $cliente->email }}</div>
                                @endif
                                @if($cliente->telefono)
                                    <div class="text-muted-foreground">{{ $cliente->telefono }}</div>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if($cliente->citta)
                                    <div>{{ $cliente->citta }}</div>
                                    @if($cliente->provincia)
                                        <div class="text-muted-foreground">({{ $cliente->provincia }})</div>
                                    @endif
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('preventivi.index', ['search' => $cliente->ragione_sociale]) }}"
                                    class="badge badge-secondary hover:bg-primary/20 transition-colors cursor-pointer"
                                    title="Numero di preventivi associati a questo cliente. Clicca per visualizzarli.">
                                    {{ $cliente->preventivi_count }}
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('lotti.index', ['cliente' => $cliente->id]) }}"
                                    class="badge badge-secondary hover:bg-primary/20 transition-colors cursor-pointer"
                                    title="Numero totale di lotti di produzione associati a questo cliente. Clicca per visualizzarli.">
                                    {{ $cliente->lotti_produzione_count }}
                                </a>
                            </td>
                            <td class="text-center">
                                <button
                                    wire:click="toggleActive({{ $cliente->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex"
                                    title="{{ $cliente->is_active ? 'Disattiva' : 'Attiva' }}"
                                >
                                    @if($cliente->is_active)
                                        <span class="badge badge-success">Attivo</span>
                                    @else
                                        <span class="badge badge-muted">Inattivo</span>
                                    @endif
                                    <span wire:loading wire:target="toggleActive({{ $cliente->id }})" class="ml-2 loading loading-spinner loading-xs"></span>
                                </button>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('clienti.show', $cliente) }}" class="btn-icon" title="Dettagli">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                        </svg>
                                    </a>
                                    <button
                                        wire:click="delete({{ $cliente->id }})"
                                        wire:confirm="Sei sicuro di voler eliminare questo cliente?"
                                        wire:loading.attr="disabled"
                                        class="btn-icon text-destructive hover:bg-destructive/10"
                                        title="Elimina"
                                    >
                                        <span wire:loading.remove wire:target="delete({{ $cliente->id }})">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </span>
                                        <span wire:loading wire:target="delete({{ $cliente->id }})" class="loading loading-spinner loading-xs"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                    <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                    <p>Nessun cliente trovato</p>
                                    @if($search || $stato)
                                        <button wire:click="resetFilters" class="btn-link text-sm">
                                            Rimuovi filtri
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($clienti->hasPages())
            <div class="card-footer">
                {{ $clienti->links() }}
            </div>
        @endif
    </div>
</div>
