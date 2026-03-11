<div>
    <form wire:submit="save" class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informazioni Prodotto</h3>
            </div>
            <div class="card-body space-y-4">
                <!-- Codice e Nome -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="codice" class="form-label">Codice *</label>
                        <input
                            wire:model="codice"
                            type="text"
                            id="codice"
                            class="form-input @error('codice') form-input-error @enderror"
                            placeholder="es. LEG-001"
                        >
                        @error('codice')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="nome" class="form-label">Nome *</label>
                        <input
                            wire:model="nome"
                            type="text"
                            id="nome"
                            class="form-input @error('nome') form-input-error @enderror"
                            placeholder="es. Tavola Abete 25x200"
                        >
                        @error('nome')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Descrizione -->
                <div>
                    <label for="descrizione" class="form-label">Descrizione</label>
                    <textarea
                        wire:model="descrizione"
                        id="descrizione"
                        rows="3"
                        class="form-input @error('descrizione') form-input-error @enderror"
                        placeholder="Descrizione opzionale del prodotto..."
                    ></textarea>
                    @error('descrizione')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Categoria e Unita Misura -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="categoria" class="form-label">Categoria *</label>
                        <select
                            wire:model="categoria"
                            id="categoria"
                            class="form-select @error('categoria') form-input-error @enderror"
                        >
                            @foreach($categorie as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                        @error('categoria')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="unita_misura" class="form-label">Unita di Misura *</label>
                        <select
                            wire:model.live="unita_misura"
                            id="unita_misura"
                            class="form-select @error('unita_misura') form-input-error @enderror"
                        >
                            @foreach($unitaMisura as $um)
                                <option value="{{ $um->value }}">{{ $um->label() }} ({{ $um->abbreviation() }})</option>
                            @endforeach
                        </select>
                        @error('unita_misura')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Prezzi, Costo e Coefficiente Scarto -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label for="prezzo_unitario" class="form-label">
                            @php
                                $umAbbr = \App\Enums\UnitaMisura::tryFrom($unita_misura)?->abbreviation();
                            @endphp
                            <span class="inline-flex items-center gap-1">
                                <span>Prezzo listino {{ $umAbbr ? "al $umAbbr" : 'unitario' }}</span>
                                <x-help-tooltip text="Prezzo base del prodotto per l'unità di misura selezionata. Per i prodotti a m³ resta il fallback quando il prezzo dedicato m³ non è valorizzato." />
                            </span>
                        </label>
                        <div class="relative">
                            <input
                                wire:model="prezzo_unitario"
                                type="number"
                                step="0.0001"
                                min="0"
                                id="prezzo_unitario"
                                class="form-input pr-10 @error('prezzo_unitario') form-input-error @enderror"
                                placeholder="0.00"
                            >
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">&euro;</span>
                        </div>
                        @error('prezzo_unitario')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    @if($showPrezzoMcInput)
                        <div>
                            <label for="prezzo_mc" class="form-label">
                                <span class="inline-flex items-center gap-1">
                                    <span>Prezzo dedicato al m³</span>
                                    <x-help-tooltip text="Override del prezzo listino solo per unità m³. Se valorizzato, è il prezzo usato nei calcoli dei materiali volumetrici." />
                                </span>
                            </label>
                            <div class="relative">
                                <input
                                    wire:model="prezzo_mc"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    id="prezzo_mc"
                                    class="form-input pr-10 @error('prezzo_mc') form-input-error @enderror"
                                    placeholder="0.00"
                                >
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">&euro;</span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Usato con U.M. <code>mc</code>; se vuoto, fallback al prezzo listino.
                            </p>
                            @error('prezzo_mc')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="rounded border border-dashed border-border bg-muted/20 px-3 py-2">
                            <p class="text-xs text-muted-foreground">
                                Il campo "Prezzo dedicato al m³" è nascosto dalla configurazione.
                            </p>
                        </div>
                    @endif
                    <div>
                        <label for="costo_unitario" class="form-label">
                            Costo {{ $umAbbr ? "al $umAbbr" : 'Unitario' }}
                        </label>
                        <div class="relative">
                            <input
                                wire:model="costo_unitario"
                                type="number"
                                step="0.0001"
                                min="0"
                                id="costo_unitario"
                                class="form-input pr-10 @error('costo_unitario') form-input-error @enderror"
                                placeholder="0.00"
                            >
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">&euro;</span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">Costo di acquisto</p>
                        @error('costo_unitario')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="coefficiente_scarto" class="form-label">Coefficiente Scarto</label>
                        <div class="relative">
                            <input
                                wire:model="coefficiente_scarto"
                                type="number"
                                step="0.01"
                                min="0"
                                max="1"
                                id="coefficiente_scarto"
                                class="form-input pr-10 @error('coefficiente_scarto') form-input-error @enderror"
                                placeholder="0.10"
                            >
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">x</span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">Percentuale di scarto (es. 0.10 = 10%)</p>
                        @error('coefficiente_scarto')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="peso_specifico_kg_mc" class="form-label">Peso specifico (kg/m³)</label>
                        <input
                            wire:model="peso_specifico_kg_mc"
                            type="number"
                            step="0.001"
                            min="0"
                            id="peso_specifico_kg_mc"
                            class="form-input @error('peso_specifico_kg_mc') form-input-error @enderror"
                            placeholder="es. 360"
                        >
                        <p class="text-xs text-muted-foreground mt-1">
                            Usato per calcolare il peso dei carichi con U.M. m³.
                        </p>
                        @error('peso_specifico_kg_mc')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded border border-border bg-muted/20 px-4 py-3">
                    <div class="flex items-center gap-1 text-sm font-medium">
                        <span>Prezzo effettivo nei calcoli</span>
                        <x-help-tooltip text="Indica il prezzo che il sistema userà nei calcoli automatici: per U.M. m³ ha priorità il prezzo dedicato m³, altrimenti usa il prezzo listino." />
                    </div>
                    @php
                        $uomAbbrEffettivo = \App\Enums\UnitaMisura::tryFrom($unita_misura)?->abbreviation() ?? 'u.m.';
                    @endphp
                    <div class="mt-1 text-lg font-semibold">
                        € {{ number_format((float) $prezzoEffettivo, 4, ',', '.') }}
                        <span class="text-sm font-normal text-muted-foreground">/ {{ $uomAbbrEffettivo }}</span>
                    </div>
                    <div class="text-xs text-muted-foreground mt-1">
                        Fonte: {{ $prezzoEffettivoFonte }}
                    </div>
                </div>

                <!-- Dimensioni -->
                <div class="card bg-gray-50/50 border">
                    <div class="card-header flex flex-row items-center justify-between py-3">
                        <h3 class="card-title text-base">Dimensioni</h3>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model.live="usa_dimensioni"
                                class="form-checkbox h-4 w-4"
                            >
                            <span class="text-sm text-muted-foreground select-none">Gestisci dimensioni</span>
                        </label>
                    </div>

                    @if($usa_dimensioni)
                        <div class="card-body pt-0 pb-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="lunghezza_mm" class="form-label">Lunghezza</label>
                                    <div class="relative">
                                        <input
                                            wire:model="lunghezza_mm"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            id="lunghezza_mm"
                                            class="form-input pr-12 @error('lunghezza_mm') form-input-error @enderror"
                                            placeholder="0"
                                        >
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">mm</span>
                                    </div>
                                    @error('lunghezza_mm')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="larghezza_mm" class="form-label">Larghezza</label>
                                    <div class="relative">
                                        <input
                                            wire:model="larghezza_mm"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            id="larghezza_mm"
                                            class="form-input pr-12 @error('larghezza_mm') form-input-error @enderror"
                                            placeholder="0"
                                        >
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">mm</span>
                                    </div>
                                    @error('larghezza_mm')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="spessore_mm" class="form-label">Spessore</label>
                                    <div class="relative">
                                        <input
                                            wire:model="spessore_mm"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            id="spessore_mm"
                                            class="form-input pr-12 @error('spessore_mm') form-input-error @enderror"
                                            placeholder="0"
                                        >
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">mm</span>
                                    </div>
                                    @error('spessore_mm')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Opzioni -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Opzioni</h3>
            </div>
            <div class="card-body space-y-4">
                <!-- FITOK -->
                <label class="flex items-center gap-3 cursor-pointer">
                    <input
                        wire:model="soggetto_fitok"
                        type="checkbox"
                        class="form-checkbox"
                    >
                    <div>
                        <span class="font-medium">Soggetto a normativa FITOK</span>
                        <p class="text-sm text-muted-foreground">
                            Attiva per materiali soggetti a tracciabilita fitosanitaria
                        </p>
                    </div>
                </label>

                <!-- Attivo -->
                <label class="flex items-center gap-3 cursor-pointer">
                    <input
                        wire:model="is_active"
                        type="checkbox"
                        class="form-checkbox"
                    >
                    <div>
                        <span class="font-medium">Prodotto attivo</span>
                        <p class="text-sm text-muted-foreground">
                            I prodotti inattivi non appariranno nelle selezioni
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Azioni -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('prodotti.index') }}" class="btn-secondary">
                Annulla
            </a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    {{ $isEditing ? 'Aggiorna Prodotto' : 'Crea Prodotto' }}
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
