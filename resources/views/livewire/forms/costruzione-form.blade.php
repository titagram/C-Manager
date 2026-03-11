<div>
    <form wire:submit="save" class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informazioni Costruzione</h3>
            </div>
            <div class="card-body space-y-4">
                <!-- Tipo e Nome -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="categoria" class="form-label">Categoria *</label>
                        <select
                            wire:model="categoria"
                            id="categoria"
                            class="form-select @error('categoria') form-input-error @enderror"
                        >
                            @foreach($tipiCostruzione as $tipoCostruzione)
                                <option value="{{ $tipoCostruzione->value }}">{{ $tipoCostruzione->label() }}</option>
                            @endforeach
                        </select>
                        @error('categoria')
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
                            placeholder="es. Cassa Standard"
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
                        placeholder="Descrizione opzionale della costruzione..."
                    ></textarea>
                    @error('descrizione')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stato -->
                <div class="flex items-center gap-2">
                    <input
                        wire:model="is_active"
                        type="checkbox"
                        id="is_active"
                        class="form-checkbox"
                    >
                    <label for="is_active" class="text-sm font-medium text-foreground cursor-pointer">
                        Costruzione attiva
                    </label>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        wire:model="show_weight_in_quote"
                        type="checkbox"
                        id="show_weight_in_quote"
                        class="form-checkbox"
                    >
                    <label for="show_weight_in_quote" class="inline-flex items-center gap-1 text-sm font-medium text-foreground cursor-pointer">
                        <span>Mostra peso nel preventivo</span>
                        <x-help-tooltip text="Se attivo, per i lotti di questa costruzione verrà mostrato il peso totale (kg) nelle righe preventivo e nel PDF." />
                    </label>
                </div>

                @if ($categoria === 'cassa')
                    <div>
                        <label for="cassa_optimizer_key" class="form-label">Modalità cassa</label>
                        <select
                            wire:model="cassa_optimizer_key"
                            id="cassa_optimizer_key"
                            class="form-select @error('cassa_optimizer_key') form-input-error @enderror"
                        >
                            @foreach($cassaModeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Geometrica usa i componenti della costruzione. Le modalità Excel usano il builder legacy dedicato.
                        </p>
                        @error('cassa_optimizer_key')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-end">
            <a href="{{ route('costruzioni.index') }}" class="btn-secondary">
                Annulla
            </a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    {{ $costruzione ? 'Aggiorna Costruzione' : 'Crea Costruzione' }}
                </span>
                <span wire:loading>
                    Salvataggio...
                </span>
            </button>
        </div>
    </form>
</div>
