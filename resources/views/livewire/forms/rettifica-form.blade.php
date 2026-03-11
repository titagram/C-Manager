<div>
    <!-- Modal Backdrop -->
    @if($showModal)
        <div
            class="fixed inset-0 bg-foreground/50 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            <!-- Modal Content -->
            <div
                class="bg-background rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-auto"
                @click.outside="$wire.closeModal()"
            >
                <form wire:submit="save">
                    <!-- Header -->
                    <div class="p-4 border-b border-border flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Rettifica Giacenza</h3>
                        <button type="button" wire:click="closeModal" class="btn-icon">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-4 space-y-4">
                        @if($lotto)
                            <!-- Info Lotto -->
                            <div class="p-3 bg-muted/30 rounded-lg">
                                <div class="text-sm space-y-1">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Lotto:</span>
                                        <span class="font-mono font-medium">{{ $lotto->codice_lotto }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Prodotto:</span>
                                        <span class="font-medium">{{ $lotto->prodotto->nome }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Giacenza attuale:</span>
                                        <span class="font-medium text-primary">
                                            {{ number_format($giacenzaAttuale, 2, ',', '.') }}
                                            {{ $lotto->prodotto->unita_misura->abbreviation() }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo Rettifica -->
                            <div>
                                <label class="form-label">Tipo Rettifica *</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition-colors {{ $tipo === 'positiva' ? 'border-success bg-success/10' : 'border-border' }}">
                                        <input
                                            wire:model.live="tipo"
                                            type="radio"
                                            value="positiva"
                                            class="form-radio text-success"
                                        >
                                        <div>
                                            <span class="font-medium text-success">+ Positiva</span>
                                            <p class="text-xs text-muted-foreground">Aggiunge quantita</p>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition-colors {{ $tipo === 'negativa' ? 'border-destructive bg-destructive/10' : 'border-border' }}">
                                        <input
                                            wire:model.live="tipo"
                                            type="radio"
                                            value="negativa"
                                            class="form-radio text-destructive"
                                        >
                                        <div>
                                            <span class="font-medium text-destructive">- Negativa</span>
                                            <p class="text-xs text-muted-foreground">Rimuove quantita</p>
                                        </div>
                                    </label>
                                </div>
                                @error('tipo')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Quantita -->
                            <div>
                                <label for="rett_quantita" class="form-label">Quantita *</label>
                                <input
                                    wire:model.live="quantita"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    @if($tipo === 'negativa') max="{{ $giacenzaAttuale }}" @endif
                                    id="rett_quantita"
                                    class="form-input @error('quantita') form-input-error @enderror"
                                    placeholder="0.00"
                                >
                                @if($tipo === 'negativa')
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Massimo: {{ number_format($giacenzaAttuale, 4, ',', '.') }} {{ $lotto->prodotto->unita_misura->abbreviation() }}
                                    </p>
                                @endif
                                @error('quantita')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Anteprima -->
                            @if($quantita)
                                <div class="p-3 border rounded-lg {{ $tipo === 'positiva' ? 'border-success/50 bg-success/5' : 'border-destructive/50 bg-destructive/5' }}">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Giacenza dopo rettifica:</span>
                                        <span class="font-bold {{ $tipo === 'positiva' ? 'text-success' : 'text-destructive' }}">
                                            {{ number_format($this->giacenzaDopoRettifica, 2, ',', '.') }}
                                            {{ $lotto->prodotto->unita_misura->abbreviation() }}
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <!-- Causale -->
                            <div>
                                <label for="rett_causale" class="form-label">Causale *</label>
                                <input
                                    wire:model="causale"
                                    type="text"
                                    id="rett_causale"
                                    class="form-input @error('causale') form-input-error @enderror"
                                    placeholder="Motivazione della rettifica (min. 5 caratteri)"
                                >
                                @error('causale')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            @if($tipo === 'negativa')
                                <div>
                                    <label class="form-label flex items-center gap-1">
                                        <span>Codice causale rettifica negativa *</span>
                                        <x-help-tooltip text="Classificazione obbligatoria per distinguere errore conteggio, danno, scarto non registrato o sospetto ammanco." />
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
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="p-4 border-t border-border flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="btn-secondary">
                            Annulla
                        </button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                Applica Rettifica
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
        </div>
    @endif
</div>
