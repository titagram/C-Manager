<div x-data="{
    showQuickProduct: false,
    selectedOption: 'nuovo'
}" x-on:close-modal.window="if ($event.detail.name === 'quick-product') showQuickProduct = false">
    <form wire:submit="save">
        @if($isReadOnly)
            <div class="alert alert-warning mb-6">
                Questo preventivo è in stato "{{ $preventivoStatoLabel }}" e non può essere modificato.
            </div>
        @endif

        @error('preventivo')
            <div class="alert alert-warning mb-6">
                {{ $message }}
            </div>
        @enderror

        <fieldset @disabled($isReadOnly) class="{{ $isReadOnly ? 'opacity-70' : '' }}">
        <!-- Header -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Dati Preventivo</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Cliente -->
                    <div>
                        <label class="form-label">Cliente</label>
                        <select wire:model="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror">
                            <option value="">Seleziona cliente...</option>
                            @foreach($clienti as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->ragione_sociale }}</option>
                            @endforeach
                        </select>
                        @error('cliente_id')
                        <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Validità -->
                    <div>
                        <label class="form-label">Valido fino al</label>
                        <input type="date" wire:model="validita_fino" class="form-input">
                    </div>

                    <!-- Descrizione -->
                    <div>
                        <label class="form-label">Descrizione</label>
                        <input type="text" wire:model="descrizione" class="form-input"
                            placeholder="Descrizione lavoro...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Righe -->
        <div class="card mb-6">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">Righe Preventivo</h3>
                @if(!$isReadOnly)
                <button type="button" wire:click="aggiungiRiga" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Aggiungi Riga
                </button>
                @endif
            </div>
            <div class="card-body p-0">
                @error('righe')
                <div class="p-4 text-red-600 bg-red-50">{{ $message }}</div>
                @enderror

                <div class="overflow-x-auto">
                    <table class="table table-compact">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th class="w-48">Prodotto</th>
                                <th>Descrizione</th>
                                <th class="w-24">Lung. mm</th>
                                <th class="w-24">Larg. mm</th>
                                <th class="w-24">Spess. mm</th>
                                <th class="w-20">Qtà</th>
                                <th class="w-20">Scarto %</th>
                                <th class="w-24">Prezzo Unit.</th>
                                <th class="w-24 text-right">Q.tà Calc.</th>
                                <th class="w-16 text-center">BOM</th>
                                <th class="w-28 text-right">Totale</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($righe as $index => $riga)
                            <tr wire:key="riga-{{ $index }}">
                                <td class="text-center text-muted-foreground">{{ $index + 1 }}</td>

                                @if(!empty($riga['lotto_produzione_id']))
                                {{-- Riga legata a Lotto --}}
                                <td colspan="2" class="bg-blue-50">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                                </svg>
                                                Lotto
                                            </span>
                                            <span class="font-medium">{{ $riga['descrizione'] }}</span>
                                            <a href="{{ route('lotti.edit', $riga['lotto_produzione_id']) }}?from=preventivo&preventivo_id={{ $preventivoId }}"
                                                class="text-xs text-blue-600 hover:text-blue-800 underline ml-auto">
                                                Modifica lotto →
                                            </a>
                                        </div>
                                        @php
                                            $lottoDim = trim(
                                                (int) ($riga['lunghezza_mm'] ?? 0) . ' x ' .
                                                (int) ($riga['larghezza_mm'] ?? 0) . ' x ' .
                                                (int) ($riga['spessore_mm'] ?? 0) . ' mm'
                                            );
                                            $lottoQty = (int) ($riga['quantita'] ?? 1);
                                            $lottoPeso = (float) ($riga['peso_totale_kg'] ?? 0);
                                            $showPeso = (bool) ($riga['show_weight_in_quote'] ?? false);
                                        @endphp
                                        <div class="text-xs text-blue-900/80">
                                            Dimensioni: {{ $lottoDim }} | Qtà: {{ $lottoQty }}
                                            @if($showPeso && $lottoPeso > 0)
                                                | Peso: {{ number_format($lottoPeso, 2, ',', '.') }} kg
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td colspan="7" class="bg-blue-50 text-right text-muted-foreground text-sm">
                                    <span class="italic">Valori calcolati dal lotto di produzione</span>
                                </td>
                                <td class="text-center bg-blue-50">
                                    <input type="checkbox" checked disabled
                                        class="form-checkbox h-4 w-4 text-primary border-input rounded">
                                </td>
                                @else
                                {{-- Riga manuale con prodotto --}}
                                @php
                                $prodottoSelezionato = !empty($riga['prodotto_id'])
                                ? $prodotti->firstWhere('id', (int) $riga['prodotto_id'])
                                : null;
                                $unitaRiga = strtolower($riga['unita_misura'] ??
                                ($prodottoSelezionato?->unita_misura?->value ?? 'mc'));
                                $unitaPrezzo = collect($unitaMisura)->firstWhere('value', $unitaRiga)?->abbreviation()
                                ?? 'm³';
                                @endphp
                                <td>
                                    <div class="flex gap-1">
                                        <select wire:model="righe.{{ $index }}.prodotto_id"
                                            wire:change="selezionaProdotto({{ $index }}, $event.target.value)"
                                            class="form-select form-select-sm flex-1">
                                            <option value="">-</option>
                                            @foreach($prodotti as $prodotto)
                                            <option value="{{ $prodotto->id }}">{{ $prodotto->codice }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" @click="showQuickProduct = true"
                                            class="btn-icon btn-sm shrink-0" title="Nuovo Prodotto">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" wire:model="righe.{{ $index }}.descrizione"
                                        class="form-input form-input-sm @error('righe.'.$index.'.descrizione') is-invalid @enderror"
                                        placeholder="Descrizione...">
                                </td>
                                <td>
                                    @if(in_array($unitaRiga, ['mc', 'mq', 'ml']))
                                    <input type="number" wire:model.blur="righe.{{ $index }}.lunghezza_mm"
                                        class="form-input form-input-sm text-right @error('righe.'.$index.'.lunghezza_mm') is-invalid @enderror"
                                        step="1" min="1">
                                    @else
                                    <span
                                        class="text-muted-foreground text-xs flex items-center justify-center h-full">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array($unitaRiga, ['mc', 'mq']))
                                    <input type="number" wire:model.blur="righe.{{ $index }}.larghezza_mm"
                                        class="form-input form-input-sm text-right @error('righe.'.$index.'.larghezza_mm') is-invalid @enderror"
                                        step="1" min="1">
                                    @else
                                    <span
                                        class="text-muted-foreground text-xs flex items-center justify-center h-full">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($unitaRiga === 'mc')
                                    <input type="number" wire:model.blur="righe.{{ $index }}.spessore_mm"
                                        class="form-input form-input-sm text-right @error('righe.'.$index.'.spessore_mm') is-invalid @enderror"
                                        step="1" min="1">
                                    @else
                                    <span
                                        class="text-muted-foreground text-xs flex items-center justify-center h-full">—</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" wire:model.blur="righe.{{ $index }}.quantita"
                                        class="form-input form-input-sm text-right @error('righe.'.$index.'.quantita') is-invalid @enderror"
                                        min="1">
                                </td>
                                <td>
                                    <input type="number" wire:model.blur="righe.{{ $index }}.coefficiente_scarto"
                                        class="form-input form-input-sm text-right" step="0.01" min="0" max="1">
                                </td>
                                <td>
                                    <div class="flex gap-1 items-start">
                                        <input type="number" wire:model.blur="righe.{{ $index }}.prezzo_unitario"
                                            class="form-input form-input-sm text-right @error('righe.'.$index.'.prezzo_unitario') is-invalid @enderror flex-1"
                                            step="0.01" min="0">
                                        <select wire:model.live="righe.{{ $index }}.unita_misura"
                                            class="form-select form-select-sm w-20" title="Unità di misura della riga">
                                            @foreach($unitaMisura as $unita)
                                            <option value="{{ $unita->value }}">{{ $unita->abbreviation() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="text-[11px] text-muted-foreground text-right mt-1">
                                        Prezzo /{{ $unitaPrezzo }}
                                    </div>
                                </td>
                                <td class="text-right font-mono">
                                    {{ number_format($riga['materiale_lordo'] ?? 0, 3, ',', '.') }}
                                    <div class="text-[11px] text-muted-foreground">
                                        {{ $unitaPrezzo }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" wire:model="righe.{{ $index }}.include_in_bom"
                                        class="form-checkbox h-4 w-4 text-primary border-input rounded"
                                        title="Includi nella distinta materiali">
                                    <input type="hidden" wire:model="righe.{{ $index }}.tipo_riga" value="sfuso">
                                </td>
                                @endif

                                <td class="text-right font-medium">
                                    € {{ number_format($riga['totale_riga'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td>
                                    @if(count($righe) > 1 && !$isReadOnly)
                                    <button type="button" wire:click="rimuoviRiga({{ $index }})"
                                        class="text-red-600 hover:text-red-800" title="Rimuovi riga">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Lavorazioni Extra -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Lavorazioni Extra</h3>
            </div>
            <div class="card-body">
                @if(!$showLavorazioniExtra && (float) $totale_lavorazioni <= 0)
                    <button type="button" wire:click="abilitaLavorazioniExtra" class="btn-secondary btn-sm">
                        Aggiungi lavorazioni extra
                    </button>
                    <p class="text-xs text-muted-foreground mt-2">
                        Questa voce resta nascosta nel preventivo finché non valorizzata.
                    </p>
                @else
                    <div class="flex flex-col md:flex-row md:items-end gap-3">
                        <div class="w-full md:w-64">
                            <label class="form-label">Importo lavorazioni extra (€)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model.blur="totale_lavorazioni"
                                class="form-input @error('totale_lavorazioni') is-invalid @enderror"
                                placeholder="0,00"
                            >
                            @error('totale_lavorazioni')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="button" wire:click="rimuoviLavorazioniExtra" class="btn-ghost btn-sm">
                            Rimuovi voce
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Totali -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="flex justify-end">
                    <div class="w-80 space-y-2">
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Totale Materiali Sfusi:</span>
                            <span class="font-medium">€ {{ number_format($totale_materiali, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Subtotale Lotti Produzione:</span>
                            <span class="font-medium">€ {{ number_format($totale_lotti, 2, ',', '.') }}</span>
                        </div>
                        @if((float) $totale_lavorazioni > 0)
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-muted-foreground">Lavorazioni extra:</span>
                            <span class="font-medium">€ {{ number_format($totale_lavorazioni, 2, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between py-3 text-lg">
                            <span class="font-semibold">TOTALE:</span>
                            <span class="font-bold text-primary">€ {{ number_format($totale, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('preventivi.index') }}" class="btn-secondary">
                Annulla
            </a>
            @if(!$isReadOnly)
            <button type="submit" class="btn-primary">
                {{ $isEditing ? 'Aggiorna Preventivo' : 'Crea Preventivo' }}
            </button>
            @endif
        </div>
        </fieldset>
    </form>

    <!-- Product Modal -->
    <div x-show="showQuickProduct" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true" @keydown.escape.window="showQuickProduct = false"
        @close-product-modal.window="showQuickProduct = false">
        <!-- Overlay -->
        <div x-show="showQuickProduct" x-transition.opacity class="fixed inset-0 bg-black/80 backdrop-blur-sm"
            @click="showQuickProduct = false"></div>

        <!-- Modal Panel -->
        <div x-show="showQuickProduct" x-transition class="relative flex items-center justify-center min-h-screen p-4"
            @click.away="showQuickProduct = false">
            <div class="bg-card w-full max-w-lg rounded-xl shadow-xl border border-border overflow-hidden" @click.stop>
                <livewire:forms.prodotto-quick-create />
            </div>
        </div>
    </div>

    <!-- Modal Aggiungi Riga -->
    <div x-show="$wire.showRigaModal" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" aria-modal="true" @keydown.escape.window="$wire.showRigaModal = false">
        <!-- Overlay -->
        <div x-show="$wire.showRigaModal" x-transition.opacity class="fixed inset-0 bg-black/80 backdrop-blur-sm"
            @click="$wire.showRigaModal = false"></div>

        <!-- Modal Panel -->
        <div x-show="$wire.showRigaModal" x-transition
            class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-card w-full max-w-md rounded-xl shadow-xl border border-border" @click.stop>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Aggiungi Riga al Preventivo</h3>

                    <div class="space-y-4">
                        <!-- Opzione: Duplica Lotto Esistente -->
                        <div>
                            <label
                                class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-muted/50 transition-colors"
                                :class="selectedOption === 'duplica' ? 'border-primary bg-primary/5' : 'border-border'"
                                @click="selectedOption = 'duplica'">
                                <input type="radio" name="option" value="duplica" x-model="selectedOption" class="mt-1">
                                <div>
                                    <div class="font-medium">Duplica lotto esistente</div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Riutilizza configurazione di un lotto già creato
                                    </div>
                                </div>
                            </label>

                            <div x-show="selectedOption === 'duplica'" x-transition class="mt-3 ml-7">
                                <select wire:model="lottoToDuplicateId" class="form-select">
                                    <option value="">Seleziona lotto...</option>
                                    @foreach($lottiDisponibili as $lotto)
                                    <option value="{{ $lotto->id }}">
                                        {{ $lotto->codice_lotto }} - {{ $lotto->prodotto_finale ?? 'N/A' }}
                                        @if($lotto->dimensioni)
                                        ({{ $lotto->dimensioni }})
                                        @endif
                                        [{{ $lotto->stato->label() }}]
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Opzione: Nuovo Lotto -->
                        <div>
                            <label
                                class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-muted/50 transition-colors"
                                :class="selectedOption === 'nuovo' ? 'border-primary bg-primary/5' : 'border-border'"
                                @click="selectedOption = 'nuovo'">
                                <input type="radio" name="option" value="nuovo" x-model="selectedOption" class="mt-1">
                                <div>
                                    <div class="font-medium">Crea nuovo lotto</div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Configura una nuova costruzione personalizzata
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Opzione: Riga materiale sfuso -->
                        <div>
                            <label
                                class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-muted/50 transition-colors"
                                :class="selectedOption === 'sfuso' ? 'border-primary bg-primary/5' : 'border-border'"
                                @click="selectedOption = 'sfuso'">
                                <input type="radio" name="option" value="sfuso" x-model="selectedOption" class="mt-1">
                                <div>
                                    <div class="font-medium">Aggiungi materiale sfuso</div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Riga manuale con materiale e quantita per la distinta materiali
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="$wire.showRigaModal = false" class="btn-secondary">
                            Annulla
                        </button>
                        <button type="button" @click="
                                    if (selectedOption === 'sfuso') {
                                        $wire.creaRigaSfusa();
                                    } else {
                                        $wire.creaRigaDaLotto(selectedOption === 'duplica' ? $wire.lottoToDuplicateId : null);
                                    }
                                " :disabled="selectedOption === 'duplica' && !$wire.lottoToDuplicateId"
                            class="btn-primary"
                            :class="{ 'opacity-50 cursor-not-allowed': selectedOption === 'duplica' && !$wire.lottoToDuplicateId }">
                            Continua →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
