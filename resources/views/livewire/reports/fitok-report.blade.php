<div>
    <!-- Filtri Periodo -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Periodo Predefinito -->
                <div>
                    <label class="form-label">Periodo</label>
                    <select wire:model.live="periodo" class="form-select">
                        <option value="oggi">Oggi</option>
                        <option value="settimana_corrente">Settimana corrente</option>
                        <option value="mese_corrente">Mese corrente</option>
                        <option value="trimestre_corrente">Trimestre corrente</option>
                        <option value="anno_corrente">Anno corrente</option>
                        <option value="mese_precedente">Mese precedente</option>
                        <option value="anno_precedente">Anno precedente</option>
                        <option value="personalizzato">Personalizzato</option>
                    </select>
                </div>

                <!-- Data Inizio -->
                <div>
                    <label class="form-label">Da</label>
                    <input type="date" wire:model.live="dataInizio" class="form-input">
                </div>

                <!-- Data Fine -->
                <div>
                    <label class="form-label">A</label>
                    <input type="date" wire:model.live="dataFine" class="form-input">
                </div>

                <!-- Export -->
                <div class="md:col-span-2 flex items-end gap-2">
                    <button wire:click="exportPdf" class="btn-secondary">
                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        PDF
                    </button>
                    <button wire:click="exportExcel" class="btn-secondary">
                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125" />
                        </svg>
                        Excel
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>Filtro lotto carico</span>
                        <x-help-tooltip text="Filtra il registro per codice lotto materiale di carico." />
                    </label>
                    <select wire:model.live="filtroLottoMateriale" class="form-select">
                        <option value="">Tutti i lotti carico</option>
                        @foreach($lottoMaterialeSuggestions as $suggestion)
                            <option value="{{ $suggestion }}">{{ $suggestion }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>Filtro lotto produzione</span>
                        <x-help-tooltip text="Filtra i movimenti associati al lotto di produzione destinatario." />
                    </label>
                    <select wire:model.live="filtroLottoProduzione" class="form-select">
                        <option value="">Tutti i lotti produzione</option>
                        @foreach($lottoProduzioneSuggestions as $suggestion)
                            <option value="{{ $suggestion }}">{{ $suggestion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Riepilogo -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <x-stat-card
            title="Carichi"
            :value="number_format($riepilogo['carichi'], 2, ',', '.')"
            icon="arrow-down-circle"
            color="green"
        />
        <x-stat-card
            title="Scarichi"
            :value="number_format($riepilogo['scarichi'], 2, ',', '.')"
            icon="arrow-up-circle"
            color="red"
        />
        <x-stat-card
            title="Rettifiche +"
            :value="number_format($riepilogo['rettifiche_positive'], 2, ',', '.')"
            icon="plus-circle"
            color="blue"
        />
        <x-stat-card
            title="Rettifiche -"
            :value="number_format($riepilogo['rettifiche_negative'], 2, ',', '.')"
            icon="minus-circle"
            color="orange"
        />
        <x-stat-card
            title="Saldo Periodo"
            :value="number_format($riepilogo['saldo'], 2, ',', '.')"
            icon="scale"
            :color="$riepilogo['saldo'] >= 0 ? 'green' : 'red'"
        />
    </div>

    <!-- Lotti in Scadenza Alert -->
    @if($lottiInScadenza->isNotEmpty())
        <div class="alert alert-warning mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <strong>Attenzione:</strong> {{ $lottiInScadenza->count() }} lotti FITOK con trattamento in scadenza nei prossimi 30 giorni.
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach($lottiInScadenza->take(5) as $lotto)
                            <li>
                                @php
                                    $dataScadenza = $lotto->fitok_data_scadenza
                                        ? \Carbon\Carbon::parse($lotto->fitok_data_scadenza)->format('d/m/Y')
                                        : null;
                                @endphp
                                <strong>{{ $lotto->codice_lotto }}</strong> ({{ $lotto->prodotto_nome }}) -
                                @if($dataScadenza)
                                    Scadenza: {{ $dataScadenza }}
                                @else
                                    Trattamento: {{ \Carbon\Carbon::parse($lotto->fitok_data_trattamento)->format('d/m/Y') }}
                                @endif
                            </li>
                        @endforeach
                        @if($lottiInScadenza->count() > 5)
                            <li class="text-muted-foreground">...e altri {{ $lottiInScadenza->count() - 5 }} lotti</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if(($riepilogoProduzione['lotti_non_certificabili_fitok'] ?? 0) > 0)
        <div class="alert alert-warning mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Zm0-12A9.75 9.75 0 1 0 21.75 12 9.75 9.75 0 0 0 12 3Z" />
                </svg>
                <div>
                    <strong>Tracciabilità FITOK:</strong>
                    nel periodo ci sono
                    <strong>{{ number_format((int) ($riepilogoProduzione['lotti_non_certificabili_fitok'] ?? 0), 0, ',', '.') }}</strong>
                    lotti non certificabili FITOK (misti o non FITOK).
                    <div class="text-sm mt-1 text-muted-foreground">
                        Verifica la colonna <em>Stato certificazione uscita</em> per identificare i lotti misti.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Riepilogo per Prodotto -->
    @if($perProdotto->isNotEmpty())
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Riepilogo per Prodotto</h3>
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Codice</th>
                            <th>Prodotto</th>
                            <th>Unità</th>
                            <th class="text-right">Carichi</th>
                            <th class="text-right">Scarichi</th>
                            <th class="text-right">Saldo</th>
                            <th class="text-center">Movimenti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perProdotto as $prodotto)
                            <tr>
                                <td class="font-mono">{{ $prodotto['codice'] }}</td>
                                <td>{{ $prodotto['nome'] }}</td>
                                <td>{{ $prodotto['unita_misura'] }}</td>
                                <td class="text-right text-green-600">+{{ number_format($prodotto['totale_carichi'], 2, ',', '.') }}</td>
                                <td class="text-right text-red-600">-{{ number_format($prodotto['totale_scarichi'], 2, ',', '.') }}</td>
                                <td class="text-right font-medium">
                                    @php $saldo = $prodotto['totale_carichi'] - $prodotto['totale_scarichi']; @endphp
                                    <span class="{{ $saldo >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($saldo, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $prodotto['movimenti_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="fitok-riepilogo-mobile-list" class="md:hidden divide-y divide-border">
                @foreach($perProdotto as $prodotto)
                    @php $saldo = $prodotto['totale_carichi'] - $prodotto['totale_scarichi']; @endphp
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-mono text-sm">{{ $prodotto['codice'] }}</div>
                                <div class="font-medium">{{ $prodotto['nome'] }}</div>
                            </div>
                            <div class="text-xs text-muted-foreground">{{ $prodotto['unita_misura'] }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="text-green-600">+{{ number_format($prodotto['totale_carichi'], 2, ',', '.') }}</div>
                            <div class="text-red-600 text-right">-{{ number_format($prodotto['totale_scarichi'], 2, ',', '.') }}</div>
                            <div class="col-span-2">
                                <span class="text-muted-foreground">Saldo:</span>
                                <span class="{{ $saldo >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($saldo, 2, ',', '.') }}
                                </span>
                                <span class="text-xs text-muted-foreground ml-2">Movimenti: {{ $prodotto['movimenti_count'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($mappaDestinazioniFitok->isNotEmpty())
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Mappa destinazioni quota FITOK</h3>
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th>Lotto carico sorgente</th>
                            <th>Lotto produzione destinatario</th>
                            <th>Prodotto</th>
                            <th class="text-right">Quantità destinata</th>
                            <th>Stato certificazione uscita</th>
                            <th class="text-right">N. movimenti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mappaDestinazioniFitok as $row)
                            <tr>
                                <td class="font-mono">{{ $row['lotto_carico_codice'] }}</td>
                                <td class="font-mono">{{ $row['lotto_produzione_codice'] }}</td>
                                <td>
                                    <span class="font-medium">{{ $row['prodotto_codice'] }}</span>
                                    <br>
                                    <span class="text-sm text-muted-foreground">{{ $row['prodotto_nome'] }}</span>
                                </td>
                                <td class="text-right font-mono">
                                    {{ number_format($row['quantita_destinata'], 4, ',', '.') }}
                                    <span class="text-muted-foreground">{{ $row['unita_misura'] }}</span>
                                </td>
                                <td>{{ $row['stato_certificazione_uscita'] }}</td>
                                <td class="text-right">{{ $row['movimenti_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="fitok-mappa-mobile-list" class="md:hidden divide-y divide-border">
                @foreach($mappaDestinazioniFitok as $row)
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-xs text-muted-foreground">Lotto carico</div>
                                <div class="font-mono">{{ $row['lotto_carico_codice'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-muted-foreground">Lotto produzione</div>
                                <div class="font-mono">{{ $row['lotto_produzione_codice'] }}</div>
                            </div>
                        </div>
                        <div>
                            <div class="font-medium">{{ $row['prodotto_codice'] }}</div>
                            <div class="text-sm text-muted-foreground">{{ $row['prodotto_nome'] }}</div>
                        </div>
                        <div class="text-sm">
                            <span class="text-muted-foreground">Quantità:</span>
                            <span class="font-mono">{{ number_format($row['quantita_destinata'], 4, ',', '.') }} {{ $row['unita_misura'] }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-muted-foreground">Stato:</span> {{ $row['stato_certificazione_uscita'] }}
                            <span class="text-xs text-muted-foreground ml-2">Movimenti: {{ $row['movimenti_count'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Registro Movimenti -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Registro Movimenti FITOK</h3>
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="table table-compact">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Lotto carico</th>
                        <th>Lotto produzione destinatario</th>
                        <th>Stato certificazione uscita</th>
                        <th>Prodotto</th>
                        <th class="text-right">Quantità</th>
                        <th>Certificato</th>
                        <th>Data Tratt.</th>
                        <th>Tipo Tratt.</th>
                        <th>Paese</th>
                        <th>Documento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimenti as $movimento)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($movimento->data_movimento)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge badge-{{ $movimento->tipo_color }}">{{ $movimento->tipo_label }}</span>
                            </td>
                            <td class="font-mono">{{ $movimento->codice_lotto }}</td>
                            <td class="font-mono">{{ $movimento->lotto_produzione_codice ?? '-' }}</td>
                            <td>
                                @if(!$movimento->lotto_produzione_id)
                                    <span class="text-muted-foreground">-</span>
                                @elseif($movimento->lotto_produzione_fitok_percentuale === null)
                                    <span class="badge badge-muted">In attesa calcolo FITOK</span>
                                @elseif((float) $movimento->lotto_produzione_fitok_percentuale >= 100)
                                    <span class="badge badge-success">Certificabile FITOK</span>
                                @elseif((float) $movimento->lotto_produzione_fitok_percentuale > 0)
                                    <span class="badge badge-warning">Misto (non certificabile FITOK)</span>
                                @else
                                    <span class="badge badge-destructive">Non FITOK</span>
                                @endif
                            </td>
                            <td>
                                <span class="font-medium">{{ $movimento->prodotto_codice }}</span>
                                <br>
                                <span class="text-sm text-muted-foreground">{{ $movimento->prodotto_nome }}</span>
                            </td>
                            <td class="text-right font-mono">
                                {{ number_format($movimento->quantita, 4, ',', '.') }}
                                <span class="text-muted-foreground">{{ $movimento->unita_misura }}</span>
                            </td>
                            <td>{{ $movimento->fitok_certificato ?? '-' }}</td>
                            <td>
                                @if($movimento->fitok_data_trattamento)
                                    {{ \Carbon\Carbon::parse($movimento->fitok_data_trattamento)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $movimento->fitok_tipo_trattamento ?? '-' }}</td>
                            <td>{{ $movimento->fitok_paese_origine ?? '-' }}</td>
                            <td>
                                @if($movimento->documento_numero)
                                    {{ $movimento->documento_tipo }} n. {{ $movimento->documento_numero }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted-foreground py-8">
                                Nessun movimento FITOK nel periodo selezionato
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="fitok-registro-mobile-list" class="md:hidden divide-y divide-border">
            @forelse($movimenti as $movimento)
                <div class="p-4 space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm">{{ \Carbon\Carbon::parse($movimento->data_movimento)->format('d/m/Y') }}</div>
                        <div>
                            <span class="badge badge-{{ $movimento->tipo_color }}">{{ $movimento->tipo_label }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="font-mono text-sm">{{ $movimento->codice_lotto }}</div>
                        <div class="text-xs text-muted-foreground">
                            Lotto produzione: {{ $movimento->lotto_produzione_codice ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="font-medium">{{ $movimento->prodotto_codice }}</div>
                        <div class="text-sm text-muted-foreground">{{ $movimento->prodotto_nome }}</div>
                    </div>

                    <div class="text-sm font-mono">
                        {{ number_format($movimento->quantita, 4, ',', '.') }} {{ $movimento->unita_misura }}
                    </div>

                    <div class="text-xs">
                        @if(!$movimento->lotto_produzione_id)
                            <span class="text-muted-foreground">Stato certificazione: -</span>
                        @elseif($movimento->lotto_produzione_fitok_percentuale === null)
                            <span class="badge badge-muted">In attesa calcolo FITOK</span>
                        @elseif((float) $movimento->lotto_produzione_fitok_percentuale >= 100)
                            <span class="badge badge-success">Certificabile FITOK</span>
                        @elseif((float) $movimento->lotto_produzione_fitok_percentuale > 0)
                            <span class="badge badge-warning">Misto (non certificabile FITOK)</span>
                        @else
                            <span class="badge badge-destructive">Non FITOK</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-muted-foreground">
                    Nessun movimento FITOK nel periodo selezionato
                </div>
            @endforelse
        </div>
    </div>
</div>
