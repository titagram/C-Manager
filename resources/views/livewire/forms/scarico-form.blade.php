<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Selezione Lotto -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Seleziona Lotto</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <label for="lotto_id" class="form-label">Lotto da scaricare *</label>
                    <select
                        wire:model.live="lotto_id"
                        id="lotto_id"
                        class="form-select @error('lotto_id') form-input-error @enderror"
                    >
                        <option value="">Seleziona un lotto...</option>
                        @foreach($lotti as $lotto)
                            <option value="{{ $lotto->id }}">
                                {{ $lotto->codice_lotto }} -
                                {{ $lotto->prodotto->nome }}
                                ({{ number_format($lotto->giacenza_calcolata, 2, ',', '.') }} {{ $lotto->prodotto->unita_misura->abbreviation() }} disponibili)
                            </option>
                        @endforeach
                    </select>
                    @error('lotto_id')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info Lotto Selezionato -->
                @if($lottoSelezionato)
                    <div class="p-4 bg-muted/30 rounded-lg">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-muted-foreground">Prodotto:</span>
                                <p class="font-medium">{{ $lottoSelezionato->prodotto->nome }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Fornitore:</span>
                                <p class="font-medium">{{ $lottoSelezionato->fornitore ?: '-' }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Data arrivo:</span>
                                <p class="font-medium">{{ $lottoSelezionato->data_arrivo?->format('d/m/Y') ?: '-' }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Giacenza:</span>
                                <p class="font-medium text-primary">
                                    {{ number_format($giacenzaDisponibile, 2, ',', '.') }}
                                    {{ $lottoSelezionato->prodotto->unita_misura->abbreviation() }}
                                </p>
                            </div>
                        </div>

                        @if($lottoSelezionato->dimensioni)
                            <div class="mt-2 text-sm">
                                <span class="text-muted-foreground">Dimensioni:</span>
                                <span class="font-medium">{{ $lottoSelezionato->dimensioni }}</span>
                            </div>
                        @endif

                        @if($lottoSelezionato->prodotto->soggetto_fitok)
                            <div class="mt-2">
                                <span class="badge badge-primary">FITOK</span>
                                @if($lottoSelezionato->fitok_certificato)
                                    <span class="text-sm text-muted-foreground ml-2">
                                        Cert: {{ $lottoSelezionato->fitok_certificato }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Dati Scarico -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dati Scarico</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>Tipo movimento *</span>
                        <x-help-tooltip text="Per rettifica negativa è obbligatoria la causale strutturata per supportare l'analisi anomalie." />
                    </label>
                    <select
                        wire:model.live="tipo_movimento"
                        class="form-select @error('tipo_movimento') form-input-error @enderror"
                    >
                        <option value="scarico">Scarico verso produzione/uso</option>
                        <option value="rettifica_negativa">Rettifica negativa inventario</option>
                    </select>
                    @error('tipo_movimento')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Quantita -->
                    <div>
                        <label for="quantita" class="form-label">
                            {{ $quantitaLabel }}
                        </label>
                        <div class="flex gap-2">
                            <input
                                wire:model="quantita"
                                type="number"
                                step="0.0001"
                                min="0"
                                max="{{ $giacenzaDisponibile }}"
                                id="quantita"
                                class="form-input flex-1 @error('quantita') form-input-error @enderror"
                                placeholder="0.00"
                            >
                            @if($lottoSelezionato)
                                <button type="button" wire:click="setFullQuantity" class="btn-secondary" title="Scarica tutto">
                                    Tutto
                                </button>
                            @endif
                        </div>
                        @if($lottoSelezionato)
                            <p class="text-xs text-muted-foreground mt-1">
                                Massimo: {{ number_format($giacenzaDisponibile, 4, ',', '.') }} {{ $lottoSelezionato->prodotto->unita_misura->abbreviation() }}
                            </p>
                        @endif
                        @error('quantita')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($tipo_movimento !== 'rettifica_negativa')
                        <!-- Lotto Produzione (opzionale) -->
                        <div>
                            <label for="lotto_produzione_id" class="form-label">Lotto Produzione</label>
                            <select
                                wire:model="lotto_produzione_id"
                                id="lotto_produzione_id"
                                class="form-select @error('lotto_produzione_id') form-input-error @enderror"
                            >
                                <option value="">Nessuno (scarico manuale)</option>
                                @foreach($lottiProduzione as $lp)
                                <option value="{{ $lp->id }}">
                                        {{ $lp->codice_lotto }} - {{ $lp->descrizione }}
                                </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-muted-foreground mt-1">
                                Collega lo scarico a un lotto di produzione
                            </p>
                            @error('lotto_produzione_id')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>

                <!-- Causale -->
                <div>
                    <label for="causale" class="form-label">Causale *</label>
                    <input
                        wire:model="causale"
                        type="text"
                        id="causale"
                        class="form-input @error('causale') form-input-error @enderror"
                        placeholder="es. Utilizzo per commessa XYZ"
                    >
                    @error('causale')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                @if($tipo_movimento === 'rettifica_negativa')
                    <div>
                        <label class="form-label flex items-center gap-1">
                            <span>Codice causale rettifica negativa *</span>
                            <x-help-tooltip text="Classifica la rettifica negativa per analisi inventariali: errore conteggio, danno, scarto non registrato o sospetto ammanco." />
                        </label>
                        <select
                            wire:model="causale_codice"
                            class="form-select @error('causale_codice') form-input-error @enderror"
                        >
                            <option value="">Seleziona causale strutturata...</option>
                            @foreach($causaliCodiceRettificaNegativa as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('causale_codice')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        <!-- Azioni -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('magazzino.index') }}" class="btn-secondary">
                Annulla
            </a>
            <button
                type="submit"
                class="btn-primary"
                wire:loading.attr="disabled"
                @if(!$lottoSelezionato) disabled @endif
            >
                <span wire:loading.remove>
                    <svg class="w-4 h-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                    </svg>
                    {{ $tipo_movimento === 'rettifica_negativa' ? 'Registra Rettifica Negativa' : 'Registra Scarico' }}
                </span>
                <span wire:loading>
                    <svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Salvataggio...
                </span>
            </button>
        </div>
    </form>
</div>
