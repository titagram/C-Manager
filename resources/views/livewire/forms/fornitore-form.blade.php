<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Dati Identificativi -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dati Identificativi</h3>
            </div>
            <div class="card-body space-y-4">
                <!-- Codice e Ragione Sociale -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="codice" class="form-label">Codice *</label>
                        <input
                            wire:model="codice"
                            type="text"
                            id="codice"
                            class="form-input uppercase @error('codice') form-input-error @enderror"
                            placeholder="SPARBER"
                            maxlength="20"
                        >
                        @error('codice')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-3">
                        <label for="ragione_sociale" class="form-label">Ragione Sociale *</label>
                        <input
                            wire:model="ragione_sociale"
                            type="text"
                            id="ragione_sociale"
                            class="form-input @error('ragione_sociale') form-input-error @enderror"
                            placeholder="Sparber Holz GmbH"
                        >
                        @error('ragione_sociale')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- P.IVA, C.F. e Nazione -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="partita_iva" class="form-label">Partita IVA</label>
                        <input
                            wire:model="partita_iva"
                            type="text"
                            id="partita_iva"
                            class="form-input font-mono @error('partita_iva') form-input-error @enderror"
                            placeholder="ATU12345678"
                            maxlength="20"
                        >
                        @error('partita_iva')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="codice_fiscale" class="form-label">Codice Fiscale</label>
                        <input
                            wire:model="codice_fiscale"
                            type="text"
                            id="codice_fiscale"
                            class="form-input font-mono uppercase @error('codice_fiscale') form-input-error @enderror"
                            placeholder="12345678901"
                            maxlength="20"
                        >
                        @error('codice_fiscale')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="nazione" class="form-label">Nazione *</label>
                        <select
                            wire:model="nazione"
                            id="nazione"
                            class="form-input @error('nazione') form-input-error @enderror"
                        >
                            <option value="IT">Italia (IT)</option>
                            <option value="AT">Austria (AT)</option>
                            <option value="DE">Germania (DE)</option>
                            <option value="SI">Slovenia (SI)</option>
                            <option value="CH">Svizzera (CH)</option>
                            <option value="FR">Francia (FR)</option>
                            <option value="HR">Croazia (HR)</option>
                            <option value="CZ">Rep. Ceca (CZ)</option>
                            <option value="PL">Polonia (PL)</option>
                            <option value="SK">Slovacchia (SK)</option>
                            <option value="HU">Ungheria (HU)</option>
                            <option value="RO">Romania (RO)</option>
                        </select>
                        @error('nazione')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Indirizzo -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sede</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <label for="indirizzo" class="form-label">Indirizzo</label>
                    <input
                        wire:model="indirizzo"
                        type="text"
                        id="indirizzo"
                        class="form-input @error('indirizzo') form-input-error @enderror"
                        placeholder="Industriestraße 10"
                    >
                    @error('indirizzo')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label for="cap" class="form-label">CAP</label>
                        <input
                            wire:model="cap"
                            type="text"
                            id="cap"
                            class="form-input @error('cap') form-input-error @enderror"
                            placeholder="6020"
                            maxlength="10"
                        >
                        @error('cap')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="col-span-2">
                        <label for="citta" class="form-label">Città</label>
                        <input
                            wire:model="citta"
                            type="text"
                            id="citta"
                            class="form-input @error('citta') form-input-error @enderror"
                            placeholder="Innsbruck"
                        >
                        @error('citta')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="provincia" class="form-label">Prov.</label>
                        <input
                            wire:model="provincia"
                            type="text"
                            id="provincia"
                            class="form-input uppercase @error('provincia') form-input-error @enderror"
                            placeholder="T"
                            maxlength="5"
                        >
                        @error('provincia')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Contatti -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Contatti</h3>
            </div>
            <div class="card-body space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="telefono" class="form-label">Telefono</label>
                        <input
                            wire:model="telefono"
                            type="text"
                            id="telefono"
                            class="form-input @error('telefono') form-input-error @enderror"
                            placeholder="+43 512 123456"
                        >
                        @error('telefono')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input
                            wire:model="email"
                            type="email"
                            id="email"
                            class="form-input @error('email') form-input-error @enderror"
                            placeholder="info@sparber.at"
                        >
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="note" class="form-label">Note</label>
                    <textarea
                        wire:model="note"
                        id="note"
                        rows="3"
                        class="form-input @error('note') form-input-error @enderror"
                        placeholder="Note sul fornitore, condizioni di fornitura, certificazioni..."
                    ></textarea>
                    @error('note')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Opzioni -->
        <div class="card">
            <div class="card-body">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input
                        wire:model="is_active"
                        type="checkbox"
                        class="form-checkbox"
                    >
                    <div>
                        <span class="font-medium">Fornitore attivo</span>
                        <p class="text-sm text-muted-foreground">
                            I fornitori inattivi non appariranno nelle selezioni
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Azioni -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('fornitori.index') }}" class="btn-secondary">
                Annulla
            </a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    {{ $isEditing ? 'Aggiorna Fornitore' : 'Crea Fornitore' }}
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
