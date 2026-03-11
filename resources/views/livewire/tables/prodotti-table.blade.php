<div>
    <!-- Filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cerca prodotto..."
                            class="form-input pl-10 w-full">
                    </div>
                </div>

                <!-- Categoria Filter -->
                <div class="w-full md:w-48">
                    <select wire:model.live="categoria" class="form-select w-full">
                        <option value="">Tutte le categorie</option>
                        @foreach ($categorie as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
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
                @if ($search || $categoria || $stato)
                    <button wire:click="resetFilters" class="btn-ghost">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
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
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('codice')">
                            <div class="flex items-center gap-2">
                                Codice
                                @if ($sortField === 'codice')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('nome')">
                            <div class="flex items-center gap-2">
                                Nome
                                @if ($sortField === 'nome')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>Categoria</th>
                        <th>U.M.</th>
                        <th class="text-right">Prezzo</th>
                        <th class="text-center">FITOK</th>
                        <th class="text-center">Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prodotti as $prodotto)
                        <tr wire:key="prodotto-{{ $prodotto->id }}">
                            <td class="font-mono text-sm">{{ $prodotto->codice }}</td>
                            <td>
                                <div>
                                    <div class="font-medium">{{ $prodotto->nome }}</div>
                                    @if ($prodotto->descrizione)
                                        <div class="text-xs text-muted-foreground truncate max-w-xs">
                                            {{ $prodotto->descrizione }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $prodotto->categoria?->label() ?? '-' }}</span>
                            </td>
                            <td>{{ $prodotto->unita_misura?->abbreviation() ?? '-' }}</td>
                            <td class="text-right font-mono">
                                @if ($prodotto->prezzo_unitario)
                                    {{ number_format($prodotto->prezzo_unitario, 2, ',', '.') }} &euro;
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($prodotto->soggetto_fitok)
                                    <span class="badge badge-primary">FITOK</span>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button wire:click="toggleActive({{ $prodotto->id }})" wire:loading.attr="disabled"
                                    class="inline-flex" title="{{ $prodotto->is_active ? 'Disattiva' : 'Attiva' }}">
                                    @if ($prodotto->is_active)
                                        <span class="badge badge-success">Attivo</span>
                                    @else
                                        <span class="badge badge-muted">Inattivo</span>
                                    @endif
                                    <!-- Loading Spinner for Toggle -->
                                    <span wire:loading wire:target="toggleActive({{ $prodotto->id }})"
                                        class="ml-2 loading loading-spinner loading-xs"></span>
                                </button>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('prodotti.show', $prodotto) }}" class="btn-icon"
                                        title="Dettagli">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    <button
                                        wire:click="duplica({{ $prodotto->id }})"
                                        wire:loading.attr="disabled"
                                        class="btn-icon"
                                        title="Duplica"
                                    >
                                        <span wire:loading.remove wire:target="duplica({{ $prodotto->id }})">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                            </svg>
                                        </span>
                                        <span wire:loading wire:target="duplica({{ $prodotto->id }})"
                                            class="loading loading-spinner loading-xs"></span>
                                    </button>
                                    <button wire:click="delete({{ $prodotto->id }})"
                                        wire:confirm="Sei sicuro di voler eliminare questo prodotto?"
                                        wire:loading.attr="disabled"
                                        class="btn-icon text-destructive hover:bg-destructive/10" title="Elimina">
                                        <span wire:loading.remove wire:target="delete({{ $prodotto->id }})">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </span>
                                        <span wire:loading wire:target="delete({{ $prodotto->id }})"
                                            class="loading loading-spinner loading-xs"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                    <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    <p>Nessun prodotto trovato</p>
                                    @if ($search || $categoria || $stato)
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
        @if ($prodotti->hasPages())
            <div class="card-footer">
                {{ $prodotti->links() }}
            </div>
        @endif
    </div>
</div>
