<div>
    <!-- Header con filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Ricerca -->
                <div class="flex-1">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        class="form-input w-full"
                        placeholder="Cerca per codice o ragione sociale..."
                    >
                </div>
                
                <!-- Filtro Stato -->
                <div class="w-full md:w-40">
                    <select wire:model.live="stato" class="form-input w-full">
                        <option value="">Tutti</option>
                        <option value="attivi">Solo attivi</option>
                        <option value="inattivi">Solo inattivi</option>
                    </select>
                </div>

                <!-- Filtro Nazione -->
                <div class="w-full md:w-40">
                    <select wire:model.live="nazione" class="form-input w-full">
                        <option value="">Tutte le nazioni</option>
                        @foreach($nazioniDisponibili as $naz)
                            <option value="{{ $naz }}">{{ $naz }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset -->
                @if($search || $stato || $nazione)
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
                        <th class="cursor-pointer" wire:click="sortBy('codice')">
                            <div class="flex items-center gap-1">
                                Codice
                                @if($sortField === 'codice')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('ragione_sociale')">
                            <div class="flex items-center gap-1">
                                Ragione Sociale
                                @if($sortField === 'ragione_sociale')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>Nazione</th>
                        <th>Città</th>
                        <th class="text-center" title="Numero totale di lotti materiale forniti">Lotti</th>
                        <th class="text-center">Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fornitori as $fornitore)
                        <tr wire:key="{{ $fornitore->id }}">
                            <td class="font-mono font-medium">{{ $fornitore->codice }}</td>
                            <td>{{ $fornitore->ragione_sociale }}</td>
                            <td>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    {{ $fornitore->nazione }}
                                </span>
                            </td>
                            <td class="text-muted-foreground">{{ $fornitore->citta ?? '-' }}</td>
                            <td class="text-center">
                                <a href="{{ route('magazzino.index', ['search' => $fornitore->ragione_sociale]) }}"
                                    class="badge badge-outline hover:bg-primary/20 transition-colors cursor-pointer"
                                    title="Numero totale di lotti materiale forniti. Clicca per visualizzarli in magazzino.">
                                    {{ $fornitore->lotti_materiale_count }}
                                </a>
                            </td>
                            <td class="text-center">
                                <button
                                    wire:click="toggleActive({{ $fornitore->id }})"
                                    class="cursor-pointer"
                                    title="{{ $fornitore->is_active ? 'Disattiva' : 'Attiva' }}"
                                >
                                    @if($fornitore->is_active)
                                        <span class="badge badge-success">Attivo</span>
                                    @else
                                        <span class="badge badge-secondary">Inattivo</span>
                                    @endif
                                </button>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Magazzino / Lotti -->
                                    <a
                                        href="{{ route('magazzino.index', ['search' => $fornitore->ragione_sociale]) }}"
                                        class="btn-icon"
                                        title="Visualizza Lotti Forniti"
                                    >
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3.75h3M12 15.75h3M12 11.25a2.25 2.25 0 002.25-2.25V6m0 0l-2.25 2.25M14.25 6l2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                        </svg>
                                    </a>

                                    <!-- Nuovo Carico -->
                                    <a
                                        href="{{ route('magazzino.carico', ['fornitore_id' => $fornitore->id]) }}"
                                        class="btn-icon"
                                        title="Nuovo Carico da Fornitore"
                                    >
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </a>

                                    <!-- Modifica -->
                                    <a
                                        href="{{ route('fornitori.edit', $fornitore) }}"
                                        class="btn-icon"
                                        title="Modifica"
                                    >
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                        </svg>
                                    </a>

                                    <!-- Elimina -->
                                    <button
                                        wire:click="delete({{ $fornitore->id }})"
                                        wire:confirm="Sei sicuro di voler eliminare questo fornitore?"
                                        class="btn-icon text-destructive hover:text-destructive"
                                        title="Elimina"
                                    >
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-muted-foreground">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8 opacity-50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                    </svg>
                                    <span>Nessun fornitore trovato</span>

                                    @if($search || $stato || $nazione)
                                        <button wire:click="resetFilters" class="text-primary hover:underline">
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

        @if($fornitori->hasPages())
            <div class="card-footer">
                {{ $fornitori->links() }}
            </div>
        @endif
    </div>
</div>
