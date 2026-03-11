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
                        <label class="form-label mb-1">Cerca</label>
                        <!-- <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg> -->
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Cerca prodotto per codice o nome..."
                            class="form-input pl-10 w-full"
                        >
                    </div>
                </div>

                <!-- Categoria Filter -->
                <div class="w-full lg:w-48">
                    <label class="form-label mb-1">Categoria</label>
                    <select wire:model.live="categoria" class="form-select w-full">
                        <option value="">Tutte le categorie</option>
                        @foreach($categorie as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- FITOK Filter -->
                <div class="w-full lg:w-48">
                    <label class="form-label mb-1">Filtro FITOK</label>
                    <select wire:model.live="filtroFitok" class="form-select w-full">
                        <option value="">Tutti (FITOK)</option>
                        <option value="solo_fitok">Solo FITOK</option>
                        <option value="solo_non_fitok">Solo Non-FITOK</option>
                    </select>
                </div>

                <!-- Giacenza Filter -->
                <div class="w-full lg:w-40">
                    <label class="form-label flex items-center gap-1 mb-1">
                        <span>Filtro giacenza</span>
                        <x-help-tooltip text="Mostra solo i prodotti che hanno almeno una quantita disponibile in magazzino (giacenza totale maggiore di zero)." />
                    </label>
                    <select wire:model.live="filtroGiacenza" class="form-select w-full">
                        <option value="">Tutte giacenze</option>
                        <option value="positiva">Con giacenza (&gt; 0)</option>
                        <option value="zero">Senza giacenza (&lt;= 0)</option>
                    </select>
                </div>

                <!-- Scarti Filter -->
                <div class="w-full lg:w-48">
                    <label class="form-label flex items-center gap-1 mb-1">
                        <span>Filtro scarti</span>
                        <x-help-tooltip text="Mostra i prodotti che hanno scarti riutilizzabili non ancora riutilizzati." />
                    </label>
                    <select wire:model.live="filtroScarti" class="form-select w-full">
                        <option value="">Tutti (scarti riutilizzabili)</option>
                        <option value="con_scarti">Con scarti disponibili</option>
                        <option value="senza_scarti">Senza scarti disponibili</option>
                    </select>
                </div>
            </div>

            <!-- Reset Button -->
            @if($search || $categoria || $filtroFitok || ($filtroGiacenza && $filtroGiacenza !== 'positiva') || $filtroScarti)
                <div class="flex items-center gap-4 mt-4">
                    <button wire:click="resetFilters" class="btn-ghost text-sm">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset filtri
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="mb-6">
        <div class="inline-flex rounded-lg border border-border bg-muted/20 p-1">
            <button
                type="button"
                wire:click="setActiveTab('giacenze')"
                @class([
                    'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                    'bg-background shadow-sm' => $activeTab === 'giacenze',
                    'text-muted-foreground hover:text-foreground' => $activeTab !== 'giacenze',
                ])
            >
                Giacenze
            </button>
            <button
                type="button"
                wire:click="setActiveTab('opzionato')"
                @class([
                    'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                    'bg-background shadow-sm' => $activeTab === 'opzionato',
                    'text-muted-foreground hover:text-foreground' => $activeTab !== 'opzionato',
                ])
            >
                Opzionato/Consumato
            </button>
            <button
                type="button"
                wire:click="setActiveTab('scarti')"
                @class([
                    'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                    'bg-background shadow-sm' => $activeTab === 'scarti',
                    'text-muted-foreground hover:text-foreground' => $activeTab !== 'scarti',
                ])
            >
                Scarti
            </button>
        </div>
    </div>

    <div @class(['space-y-6', 'hidden' => $activeTab !== 'opzionato'])>
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <span>Materiale opzionato e consumato</span>
                    <x-help-tooltip text="L'opzionato è riservato al lotto/BOM e non dovrebbe essere riallocato ad altre lavorazioni." />
                </h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="rounded border border-border px-3 py-2">
                        <div class="text-xs text-muted-foreground">Totale tracciato</div>
                        <div class="text-lg font-semibold">{{ number_format($totaliConsumiTracciabilita['totale'], 3, ',', '.') }}</div>
                    </div>
                    <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2">
                        <div class="text-xs text-muted-foreground">Opzionato</div>
                        <div class="text-lg font-semibold text-amber-700">{{ number_format($totaliConsumiTracciabilita['opzionato'], 3, ',', '.') }}</div>
                    </div>
                    <div class="rounded border border-blue-200 bg-blue-50 px-3 py-2">
                        <div class="text-xs text-muted-foreground">Consumato</div>
                        <div class="text-lg font-semibold text-blue-700">{{ number_format($totaliConsumiTracciabilita['consumato'], 3, ',', '.') }}</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-compact">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Stato</th>
                                <th>Lotto materiale</th>
                                <th>Prodotto</th>
                                <th class="text-right">Quantità</th>
                                <th>Lotto produzione</th>
                                <th>BOM</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consumiTracciabilita as $consumo)
                                <tr wire:key="consumo-traccia-{{ $consumo->id }}">
                                    <td class="text-sm">{{ \Carbon\Carbon::parse($consumo->data_evento)->format('d/m/Y') }}</td>
                                    <td>
                                        @if($consumo->stato === 'opzionato')
                                            <span class="badge badge-warning text-xs">Opzionato</span>
                                        @else
                                            <span class="badge badge-info text-xs">Consumato</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-sm">{{ $consumo->lotto_materiale_codice }}</td>
                                    <td>
                                        @if($consumo->prodotto_codice)
                                            <span class="font-medium">{{ $consumo->prodotto_codice }}</span>
                                            <span class="text-muted-foreground"> - {{ $consumo->prodotto_nome }}</span>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-mono">
                                        {{ number_format((float) $consumo->quantita, 3, ',', '.') }}
                                        @php
                                            $uom = strtolower((string) ($consumo->unita_misura ?? ''));
                                        @endphp
                                        @if($uom !== '')
                                            <span class="text-xs text-muted-foreground">{{ \App\Enums\UnitaMisura::tryFrom($uom)?->abbreviation() ?? $uom }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-sm">{{ $consumo->lotto_produzione_codice ?: '-' }}</td>
                                    <td class="font-mono text-sm">{{ $consumo->bom_codice ?: '-' }}</td>
                                    <td class="text-sm text-muted-foreground">{{ $consumo->display_note ?? ($consumo->note ?: '-') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-6 text-muted-foreground">
                                        Nessun consumo opzionato/consumato per i filtri attivi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div @class(['space-y-6', 'hidden' => $activeTab !== 'scarti'])>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="text-sm text-muted-foreground">Scarti Totali</div>
                    <div class="text-2xl font-bold">{{ number_format($totaliScarti['totale'], 3, ',', '.') }} m³</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="text-sm text-muted-foreground">Scarti FITOK</div>
                    <div class="text-2xl font-bold text-green-700">{{ number_format($totaliScarti['fitok'], 3, ',', '.') }} m³</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="text-sm text-muted-foreground">Scarti Non-FITOK</div>
                    <div class="text-2xl font-bold text-orange-700">{{ number_format($totaliScarti['non_fitok'], 3, ',', '.') }} m³</div>
                </div>
            </div>
        </div>

        <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <span>Tracciabilita scarti per lotto</span>
                <x-help-tooltip text="Classifica scarto: riutilizzabile, non riutilizzabile, oppure gia riutilizzato in un altro lotto." />
            </h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="rounded border border-border px-3 py-2">
                    <div class="text-xs text-muted-foreground">Scarti registrati (totale)</div>
                    <div class="text-lg font-semibold">{{ number_format($totaliScartiTracciabilita['totale'], 3, ',', '.') }} m³</div>
                </div>
                <div class="rounded border border-border px-3 py-2">
                    <div class="text-xs text-muted-foreground">Pezzi scarto</div>
                    <div class="text-lg font-semibold">{{ number_format($totaliScartiTracciabilita['pezzi_totali'], 0, ',', '.') }}</div>
                </div>
                <div class="rounded border border-border px-3 py-2">
                    <div class="text-xs text-muted-foreground">Peso stimato</div>
                    <div class="text-lg font-semibold">{{ number_format($totaliScartiTracciabilita['peso_totale_kg'], 3, ',', '.') }} kg</div>
                </div>
                <div class="rounded border border-green-200 bg-green-50 px-3 py-2">
                    <div class="text-xs text-muted-foreground">Riutilizzabile disponibile</div>
                    <div class="text-lg font-semibold text-green-700">{{ number_format($totaliScartiTracciabilita['riutilizzabile_disponibile'], 3, ',', '.') }} m³</div>
                </div>
                <div class="rounded border border-blue-200 bg-blue-50 px-3 py-2">
                    <div class="text-xs text-muted-foreground">Riutilizzato</div>
                    <div class="text-lg font-semibold text-blue-700">{{ number_format($totaliScartiTracciabilita['riutilizzato'], 3, ',', '.') }} m³</div>
                </div>
                <div class="rounded border border-orange-200 bg-orange-50 px-3 py-2">
                    <div class="text-xs text-muted-foreground">Non riutilizzabile</div>
                    <div class="text-lg font-semibold text-orange-700">{{ number_format($totaliScartiTracciabilita['non_riutilizzabile'], 3, ',', '.') }} m³</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Lotto produzione origine</th>
                            <th>Lotto materiale origine</th>
                            <th>Prodotto</th>
                            <th>Dimensioni scarto (mm)</th>
                            <th>Tipologia</th>
                            <th class="text-right">Volume</th>
                            <th class="text-right">Peso stimato</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scartiTracciabilita as $scarto)
                            <tr wire:key="scarto-traccia-{{ $scarto->id }}">
                                <td class="text-sm">{{ \Carbon\Carbon::parse($scarto->created_at)->format('d/m/Y') }}</td>
                                <td class="font-mono text-sm">{{ $scarto->lotto_produzione_codice ?: '-' }}</td>
                                <td class="font-mono text-sm">{{ $scarto->lotto_materiale_codice ?: '-' }}</td>
                                <td>
                                    @if($scarto->prodotto_codice)
                                        <span class="font-medium">{{ $scarto->prodotto_codice }}</span>
                                        <span class="text-muted-foreground"> - {{ $scarto->prodotto_nome }}</span>
                                    @else
                                        <span class="text-muted-foreground">-</span>
                                    @endif
                                </td>
                                <td class="font-mono text-sm">
                                    {{ number_format((float) $scarto->lunghezza_mm, 0, ',', '.') }}
                                    x
                                    {{ number_format((float) $scarto->larghezza_mm, 0, ',', '.') }}
                                    x
                                    {{ number_format((float) $scarto->spessore_mm, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if($scarto->tipologia_label === 'Riutilizzabile disponibile')
                                        <span class="badge badge-success text-xs">Riutilizzabile disponibile</span>
                                    @elseif($scarto->tipologia_label === 'Riutilizzato')
                                        <span class="badge badge-info text-xs">Riutilizzato</span>
                                        @if($scarto->lotto_riuso_codice)
                                            <div class="text-xs text-muted-foreground mt-1">In: {{ $scarto->lotto_riuso_codice }}</div>
                                        @endif
                                    @else
                                        <span class="badge badge-warning text-xs">Non riutilizzabile</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono">{{ number_format((float) $scarto->volume_mc, 3, ',', '.') }} m³</td>
                                <td class="text-right font-mono">
                                    @if($scarto->peso_stimato_kg !== null)
                                        {{ number_format((float) $scarto->peso_stimato_kg, 3, ',', '.') }} kg
                                    @else
                                        <span class="text-muted-foreground">-</span>
                                    @endif
                                </td>
                                <td class="text-sm text-muted-foreground">{{ $scarto->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-6 text-muted-foreground">
                                    Nessuno scarto registrato per i filtri attivi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                <h4 class="font-medium text-sm mb-3">Scarti riutilizzabili compatibili (per dimensione)</h4>
                <div class="overflow-x-auto">
                    <table class="table table-compact">
                        <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th>Dimensioni (mm)</th>
                                <th class="text-right">Pezzi</th>
                                <th class="text-right">Volume</th>
                                <th class="text-right">Peso stimato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scartiRiepilogoCompatibili as $gruppo)
                                <tr>
                                    <td>
                                        <span class="font-medium">{{ $gruppo->prodotto_codice ?: '-' }}</span>
                                        @if($gruppo->prodotto_nome)
                                            <span class="text-muted-foreground"> - {{ $gruppo->prodotto_nome }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-sm">
                                        {{ number_format((float) $gruppo->lunghezza_mm, 0, ',', '.') }}
                                        x
                                        {{ number_format((float) $gruppo->larghezza_mm, 0, ',', '.') }}
                                        x
                                        {{ number_format((float) $gruppo->spessore_mm, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-mono">{{ number_format((int) $gruppo->pezzi, 0, ',', '.') }}</td>
                                    <td class="text-right font-mono">{{ number_format((float) $gruppo->volume_mc, 3, ',', '.') }} m³</td>
                                    <td class="text-right font-mono">{{ number_format((float) $gruppo->peso_totale_kg, 3, ',', '.') }} kg</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted-foreground">
                                        Nessuno scarto riutilizzabile compatibile per i filtri attivi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div @class(['space-y-6', 'hidden' => $activeTab !== 'giacenze'])>
    <!-- Tabella -->
    <div class="card">
        <div class="hidden md:block overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('codice')">
                            <div class="flex items-center gap-2">
                                Codice
                                @if($sortField === 'codice')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('nome')">
                            <div class="flex items-center gap-2">
                                Nome
                                @if($sortField === 'nome')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="text-right cursor-pointer hover:bg-muted/50" wire:click="sortBy('giacenza_totale')">
                            <div class="flex items-center justify-end gap-2">
                                Giacenza Totale
                                @if($sortField === 'giacenza_totale')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="text-center cursor-pointer hover:bg-muted/50" wire:click="sortBy('fitok_percentuale')">
                            <div class="flex items-center justify-center gap-2">
                                FITOK
                                @if($sortField === 'fitok_percentuale')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="text-right cursor-pointer hover:bg-muted/50" wire:click="sortBy('scarti_volume')">
                            <div class="flex items-center justify-end gap-2">
                                Scarti Disponibili
                                @if($sortField === 'scarti_volume')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="text-center">Lotti Attivi</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prodotti as $prodotto)
                        <!-- Main Product Row -->
                        <tr wire:key="prodotto-{{ $prodotto->id }}" class="cursor-pointer hover:bg-muted/30" wire:click="toggle({{ $prodotto->id }})">
                            <td class="font-mono text-sm font-medium">{{ $prodotto->codice }}</td>
                            <td>
                                <div>
                                    <div class="font-medium">{{ $prodotto->nome }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        <span class="badge badge-secondary text-xs">{{ $prodotto->categoria->label() }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="font-mono font-medium {{ $prodotto->giacenza_totale > 0 ? 'text-foreground' : 'text-destructive' }}">
                                    {{ number_format($prodotto->giacenza_totale, 2, ',', '.') }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ $prodotto->unita_misura->abbreviation() }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($prodotto->soggetto_fitok)
                                    <div class="space-y-1">
                                        <div>
                                            @if($prodotto->fitok_percentuale_giacenza >= 100)
                                                <span class="badge badge-success">{{ number_format($prodotto->fitok_percentuale_giacenza, 0) }}%</span>
                                            @elseif($prodotto->fitok_percentuale_giacenza > 0)
                                                <span class="badge badge-warning">{{ number_format($prodotto->fitok_percentuale_giacenza, 0) }}%</span>
                                            @else
                                                <span class="badge badge-muted">0%</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            Giacenza {{ number_format($prodotto->fitok_percentuale_giacenza ?? 0, 0) }}%
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            @if($prodotto->fitok_percentuale_produzione !== null)
                                                Produzione {{ number_format($prodotto->fitok_percentuale_produzione, 0) }}%
                                            @else
                                                Produzione N/D
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted-foreground text-sm">N/A</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($prodotto->scarti_volume > 0)
                                    <div class="font-mono font-medium text-primary">
                                        {{ number_format($prodotto->scarti_volume, 3, ',', '.') }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">m³</div>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ $prodotto->lotti_attivi_count }}</span>
                            </td>
                            <td class="text-right" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        wire:click="toggle({{ $prodotto->id }})"
                                        class="btn-icon"
                                        title="{{ $expanded === $prodotto->id ? 'Comprimi' : 'Espandi lotti' }}"
                                    >
                                        <svg class="w-4 h-4 transition-transform {{ $expanded === $prodotto->id ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    @if($isAdmin)
                                        <a href="{{ route('prodotti.show', $prodotto) }}" class="btn-icon" title="Dettagli prodotto">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Expanded Lotti Details -->
                        @if($expanded === $prodotto->id && $prodotto->lottiConGiacenza->count() > 0)
                            <tr wire:key="prodotto-{{ $prodotto->id }}-details">
                                <td colspan="7" class="bg-muted/20 p-0">
                                    <div class="p-4">
                                        <h4 class="font-medium text-sm mb-3 text-muted-foreground">Lotti attivi per {{ $prodotto->nome }}</h4>
                                        <div class="overflow-x-auto">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr class="bg-muted/30">
                                                        <th>Codice Lotto</th>
                                                        <th>Fornitore</th>
                                                        <th>Data Arrivo</th>
                                                        <th class="text-right">Giacenza</th>
                                                        <th class="text-center">FITOK</th>
                                                        <th class="text-right">Azioni</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($prodotto->lottiConGiacenza as $lotto)
                                                        <tr wire:key="lotto-{{ $lotto->id }}">
                                                            <td class="font-mono text-sm">{{ $lotto->codice_lotto }}</td>
                                                            <td>
                                                                <div>{{ $lotto->fornitore ?: '-' }}</div>
                                                                @if($lotto->numero_ddt)
                                                                    <div class="text-xs text-muted-foreground">DDT: {{ $lotto->numero_ddt }}</div>
                                                                @endif
                                                            </td>
                                                            <td class="text-sm">{{ $lotto->data_arrivo?->format('d/m/Y') ?: '-' }}</td>
                                                            <td class="text-right">
                                                                <span class="font-mono font-medium">
                                                                    {{ number_format($lotto->giacenza_calcolata, 2, ',', '.') }}
                                                                </span>
                                                                <span class="text-muted-foreground text-sm ml-1">{{ $prodotto->unita_misura->abbreviation() }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                @if($prodotto->soggetto_fitok)
                                                                    @if($lotto->fitok_certificato)
                                                                        <span class="badge badge-success text-xs" title="Certificato: {{ $lotto->fitok_certificato }}">FITOK</span>
                                                                    @else
                                                                        <span class="badge badge-warning text-xs" title="Dati FITOK mancanti">FITOK!</span>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted-foreground text-xs">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-right">
                                                                <div class="flex items-center justify-end gap-1">
                                                                    @if($isAdmin)
                                                                        <a href="{{ route('magazzino.scarico') }}?lotto={{ $lotto->id }}" class="btn-icon btn-icon-sm" title="Scarico">
                                                                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                                                            </svg>
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                    <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    <p>Nessun prodotto trovato</p>
                                    @if($search || $categoria || $filtroFitok || ($filtroGiacenza && $filtroGiacenza !== 'positiva') || $filtroScarti)
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

        <div id="magazzino-giacenze-mobile-list" class="md:hidden divide-y divide-border">
            @forelse($prodotti as $prodotto)
                <div class="p-4 space-y-3" wire:key="prodotto-mobile-{{ $prodotto->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-mono text-sm font-semibold">{{ $prodotto->codice }}</div>
                            <div class="font-medium">{{ $prodotto->nome }}</div>
                            <div class="text-xs text-muted-foreground">{{ $prodotto->categoria->label() }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-medium {{ $prodotto->giacenza_totale > 0 ? 'text-foreground' : 'text-destructive' }}">
                                {{ number_format($prodotto->giacenza_totale, 2, ',', '.') }}
                            </div>
                            <div class="text-xs text-muted-foreground">{{ $prodotto->unita_misura->abbreviation() }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-muted-foreground">Lotti attivi:</span>
                            <span class="badge badge-secondary">{{ $prodotto->lotti_attivi_count }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-muted-foreground">Scarti:</span>
                            @if($prodotto->scarti_volume > 0)
                                {{ number_format($prodotto->scarti_volume, 3, ',', '.') }} m³
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <div class="text-xs">
                        <span class="text-muted-foreground">FITOK:</span>
                        @if($prodotto->soggetto_fitok)
                            @if($prodotto->fitok_percentuale_giacenza >= 100)
                                <span class="badge badge-success">{{ number_format($prodotto->fitok_percentuale_giacenza, 0) }}%</span>
                            @elseif($prodotto->fitok_percentuale_giacenza > 0)
                                <span class="badge badge-warning">{{ number_format($prodotto->fitok_percentuale_giacenza, 0) }}%</span>
                            @else
                                <span class="badge badge-muted">0%</span>
                            @endif
                            <span class="text-muted-foreground ml-1">
                                (Produzione
                                @if($prodotto->fitok_percentuale_produzione !== null)
                                    {{ number_format($prodotto->fitok_percentuale_produzione, 0) }}%
                                @else
                                    N/D
                                @endif)
                            </span>
                        @else
                            N/A
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <button wire:click="toggle({{ $prodotto->id }})" class="btn-sm btn-secondary">
                            {{ $expanded === $prodotto->id ? 'Nascondi lotti' : 'Mostra lotti' }}
                        </button>
                        @if($isAdmin)
                            <a href="{{ route('prodotti.show', $prodotto) }}" class="btn-sm btn-secondary">Dettagli</a>
                        @endif
                    </div>

                    @if($expanded === $prodotto->id && $prodotto->lottiConGiacenza->count() > 0)
                        <div class="rounded border border-border bg-muted/20 p-3 space-y-2">
                            <div class="text-xs font-medium text-muted-foreground">Lotti attivi</div>
                            @foreach($prodotto->lottiConGiacenza as $lotto)
                                <div class="rounded bg-background border border-border px-3 py-2 text-xs">
                                    <div class="font-mono">{{ $lotto->codice_lotto }}</div>
                                    <div class="text-muted-foreground">{{ $lotto->fornitore ?: '-' }}</div>
                                    <div>
                                        {{ number_format($lotto->giacenza_calcolata, 2, ',', '.') }}
                                        {{ $prodotto->unita_misura->abbreviation() }}
                                    </div>
                                    <div class="mt-1">
                                        @if($isAdmin)
                                            <a href="{{ route('magazzino.scarico') }}?lotto={{ $lotto->id }}" class="btn-sm btn-secondary">Scarico</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-muted-foreground">
                    Nessun prodotto trovato
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($prodotti->hasPages())
            <div class="card-footer">
                {{ $prodotti->links() }}
            </div>
        @endif
    </div>
    </div>
</div>
