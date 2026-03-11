<div>
    @php
        $isAdmin = auth()->user()?->isAdmin() ?? false;
    @endphp
    <!-- Filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            placeholder="Cerca lotto, fornitore, prodotto..." class="form-input pl-10 w-full">
                    </div>
                </div>

                <!-- Prodotto Filter -->
                <div class="w-full lg:w-56">
                    <select wire:model.live="prodotto" class="form-select w-full">
                        <option value="">Tutti i prodotti</option>
                        @foreach ($prodotti as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Categoria Filter -->
                <div class="w-full lg:w-40">
                    <select wire:model.live="categoria" class="form-select w-full">
                        <option value="">Tutte le categorie</option>
                        @foreach ($categorie as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Giacenza Filter -->
                <div class="w-full lg:w-40">
                    <select wire:model.live="giacenza" class="form-select w-full">
                        <option value="">Tutte le giacenze</option>
                        <option value="positiva">Con giacenza</option>
                        <option value="bassa">Giacenza bassa</option>
                        <option value="esaurita">Esaurita</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 mt-4">
                <!-- Solo FITOK -->
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model.live="soloFitok" type="checkbox" class="form-checkbox">
                    <span class="text-sm">Solo materiali FITOK</span>
                </label>

                <!-- Reset -->
                @if ($search || $prodotto || $categoria || $giacenza || $soloFitok)
                    <button wire:click="resetFilters" class="btn-ghost text-sm">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset filtri
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
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('codice_lotto')">
                            <div class="flex items-center gap-2">
                                Codice Lotto
                                @if ($sortField === 'codice_lotto')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('prodotto')">
                            <div class="flex items-center gap-2">
                                Prodotto
                                @if ($sortField === 'prodotto')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('fornitore')">
                            <div class="flex items-center gap-2">
                                Fornitore
                                @if ($sortField === 'fornitore')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('data_arrivo')">
                            <div class="flex items-center gap-2">
                                Data Arrivo
                                @if ($sortField === 'data_arrivo')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>Dimensioni</th>
                        <th class="text-right">Giacenza</th>
                        <th class="text-right">Peso</th>
                        <th class="text-center">FITOK</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lotti as $lotto)
                        <tr wire:key="lotto-{{ $lotto->id }}">
                            <td class="font-mono text-sm font-medium">{{ $lotto->codice_lotto }}</td>
                            <td>
                                <div>
                                    <div class="font-medium">{{ $lotto->prodotto?->nome ?? 'N/D' }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        @if ($lotto->prodotto)
                                            <span
                                                class="badge badge-secondary text-xs">{{ $lotto->prodotto->categoria->label() }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div>{{ $lotto->fornitore ?: '-' }}</div>
                                    @if ($lotto->numero_ddt)
                                        <div class="text-xs text-muted-foreground">DDT: {{ $lotto->numero_ddt }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                {{ $lotto->data_arrivo?->format('d/m/Y') ?: '-' }}
                            </td>
                            <td class="text-sm">
                                @if ($lotto->dimensioni)
                                    {{ $lotto->dimensioni }}
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @php
                                    $giacenza = $lotto->giacenza_calcolata;
                                    $um = $lotto->prodotto?->unita_misura->abbreviation() ?? '-';
                                @endphp
                                <span
                                    class="font-mono font-medium {{ $giacenza <= 0 ? 'text-destructive' : ($giacenza <= 10 ? 'text-warning' : 'text-foreground') }}">
                                    {{ number_format($giacenza, 2, ',', '.') }}
                                </span>
                                <span class="text-muted-foreground text-sm">{{ $um }}</span>
                            </td>
                            <td class="text-right">
                                @if($lotto->peso_visualizzato_kg !== null)
                                    <span class="font-mono font-medium">
                                        {{ number_format((float) $lotto->peso_visualizzato_kg, 2, ',', '.') }}
                                    </span>
                                    <span class="text-muted-foreground text-sm">kg</span>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($lotto->prodotto?->soggetto_fitok)
                                    @if ($lotto->fitok_certificato)
                                        <span class="badge badge-success"
                                            title="Certificato: {{ $lotto->fitok_certificato }}">FITOK</span>
                                    @else
                                        <span class="badge badge-warning" title="Dati FITOK mancanti">FITOK!</span>
                                    @endif
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('magazzino.movimenti', $lotto) }}"
                                        class="btn-icon" title="Movimenti lotto">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 3v18h18M7.5 15.75 10.5 12l2.25 2.25 4.5-6" />
                                        </svg>
                                    </a>
                                    @if($isAdmin)
                                        <a href="{{ route('magazzino.scarico') }}?lotto={{ $lotto->id }}"
                                            class="btn-icon" title="Scarico">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                            </svg>
                                        </a>
                                        <button x-data
                                            @click="$dispatch('open-rettifica', { lottoId: {{ $lotto->id }} })"
                                            class="btn-icon" title="Rettifica">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                    <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    <p>Nessun lotto trovato</p>
                                    @if ($search || $prodotto || $categoria || $giacenza || $soloFitok)
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
        @if ($lotti->hasPages())
            <div class="card-footer">
                {{ $lotti->links() }}
            </div>
        @endif
    </div>
</div>
