<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Dati Principali -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dati Lotto</h3>
            </div>
            <div class="card-body space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Codice Lotto -->
                    <div>
                        <label for="codice_lotto" class="form-label">Codice Lotto *</label>
                        <div class="flex gap-2">
                            <input
                                wire:model="codice_lotto"
                                type="text"
                                id="codice_lotto"
                                class="form-input flex-1 @error('codice_lotto') form-input-error @enderror"
                            >
                            <button type="button" wire:click="generateCodiceLotto" class="btn-secondary" title="Genera nuovo codice">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </button>
                        </div>
                        @error('codice_lotto')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prodotto -->
                    <div>
                        <label for="prodotto_id" class="form-label">Prodotto *</label>
                        <select
                            wire:model.live="prodotto_id"
                            id="prodotto_id"
                            class="form-select @error('prodotto_id') form-input-error @enderror"
                        >
                            <option value="">Seleziona prodotto...</option>
                            @foreach($prodotti as $prodotto)
                                <option value="{{ $prodotto->id }}">
                                    {{ $prodotto->nome }}
                                    ({{ $prodotto->unita_misura->abbreviation() }})
                                    @if($prodotto->soggetto_fitok) - FITOK @endif
                                </option>
                            @endforeach
                        </select>
                        @error('prodotto_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Data Arrivo -->
                    <div>
                        <label for="data_arrivo" class="form-label">Data Arrivo *</label>
                        <input
                            wire:model="data_arrivo"
                            type="date"
                            id="data_arrivo"
                            class="form-input @error('data_arrivo') form-input-error @enderror"
                        >
                        @error('data_arrivo')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Toggle FITOK -->
                    <div class="flex items-center h-full pt-6">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <div class="relative inline-flex items-center">
                                <input type="checkbox" wire:model.live="showFitok" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-300">Tracciabilità FITOK</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Fornitore -->
                    <div>
                        <label for="fornitore_id" class="form-label">Fornitore *</label>
                        <select
                            wire:model.live="fornitore_id"
                            id="fornitore_id"
                            class="form-select @error('fornitore_id') form-input-error @enderror"
                        >
                            <option value="">Seleziona fornitore...</option>
                            @foreach($fornitori as $fornitore)
                                <option value="{{ $fornitore->id }}">
                                    {{ $fornitore->ragione_sociale }}
                                    @if($fornitore->nazione !== 'IT') ({{ $fornitore->nazione }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('fornitore_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Numero DDT -->
                    <div>
                        <label for="numero_ddt" class="form-label">Numero DDT</label>
                        <input
                            wire:model="numero_ddt"
                            type="text"
                            id="numero_ddt"
                            class="form-input @error('numero_ddt') form-input-error @enderror"
                            placeholder="es. DDT-2026/001"
                        >
                        @error('numero_ddt')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Quantita -->
                    <div>
                        <label for="quantita" class="form-label">{{ $quantitaLabel }}</label>
                        <input
                            wire:model="quantita"
                            type="number"
                            step="0.0001"
                            min="0"
                            id="quantita"
                            class="form-input @error('quantita') form-input-error @enderror"
                            placeholder="0.00"
                        >
                        @error('quantita')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Causale -->
                <div>
                    <label for="causale" class="form-label">Causale</label>
                    <input
                        wire:model="causale"
                        type="text"
                        id="causale"
                        class="form-input @error('causale') form-input-error @enderror"
                        placeholder="Causale del carico (opzionale)"
                    >
                    @error('causale')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Dati FITOK -->
        @if($showFitok)
        <div class="card border-primary/50">
            <div class="card-header bg-primary/5">
                <h3 class="card-title flex items-center gap-2">
                    <span class="badge badge-primary">FITOK</span>
                    Dati Fitosanitari
                </h3>
            </div>
            <div class="card-body space-y-4">
                <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                    <strong>Attenzione:</strong> Questo prodotto richiede tracciabilita FITOK. Compilare i dati fitosanitari.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="fitok_certificato" class="form-label">Numero Certificato</label>
                        <input
                            wire:model="fitok_certificato"
                            type="text"
                            id="fitok_certificato"
                            class="form-input @error('fitok_certificato') form-input-error @enderror"
                            placeholder="es. FITOK-IT-2026-001"
                        >
                        @error('fitok_certificato')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="fitok_data_trattamento" class="form-label">Data Trattamento</label>
                        <input
                            wire:model="fitok_data_trattamento"
                            type="date"
                            id="fitok_data_trattamento"
                            class="form-input @error('fitok_data_trattamento') form-input-error @enderror"
                        >
                        @error('fitok_data_trattamento')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="fitok_tipo_trattamento" class="form-label">Tipo Trattamento</label>
                        <select
                            wire:model="fitok_tipo_trattamento"
                            id="fitok_tipo_trattamento"
                            class="form-select @error('fitok_tipo_trattamento') form-input-error @enderror"
                        >
                            <option value="">Seleziona...</option>
                            <option value="HT">HT - Trattamento termico</option>
                            <option value="MB">MB - Bromuro di metile</option>
                            <option value="KD">KD - Essiccazione in forno</option>
                            <option value="DH">DH - Riscaldamento dielettrico</option>
                        </select>
                        @error('fitok_tipo_trattamento')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="fitok_paese_origine" class="form-label">Paese di Origine</label>
                        <input
                            wire:model="fitok_paese_origine"
                            type="text"
                            id="fitok_paese_origine"
                            class="form-input @error('fitok_paese_origine') form-input-error @enderror"
                            placeholder="es. Italia, Austria, Germania"
                        >
                        @error('fitok_paese_origine')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Note -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Note</h3>
            </div>
            <div class="card-body">
                <textarea
                    wire:model="note"
                    rows="3"
                    class="form-input @error('note') form-input-error @enderror"
                    placeholder="Note aggiuntive sul lotto..."
                ></textarea>
                @error('note')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Azioni -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('magazzino.index') }}" class="btn-secondary">
                Annulla
            </a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    <svg class="w-4 h-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Registra Carico
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
