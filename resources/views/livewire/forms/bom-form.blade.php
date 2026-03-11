<div>
    <form wire:submit="save">
        <!-- Header -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Dati Distinta Base</h3>
            </div>
            <div class="card-body">
                <!-- Template Info Box -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Distinta Base Template:</strong> Questa distinta base e un template per suggerire i tipi di materiali da utilizzare. Le quantita indicate sono di riferimento e verranno ricalcolate automaticamente in base alle dimensioni effettive del lotto di produzione tramite il CuttingOptimizer.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Nome -->
                    <div class="md:col-span-2">
                        <label class="form-label required">Nome</label>
                        <input type="text" wire:model="nome" class="form-input @error('nome') is-invalid @enderror" placeholder="Es. Cassa Standard 80x80">
                        @error('nome')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Versione -->
                    <div>
                        <label class="form-label required">Versione</label>
                        <input type="text" wire:model="versione" class="form-input @error('versione') is-invalid @enderror" placeholder="1.0">
                        @error('versione')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <!-- Prodotto Output -->
                    <div>
                        <label class="form-label">Prodotto Output</label>
                        <select wire:model="prodotto_id" class="form-select @error('prodotto_id') is-invalid @enderror">
                            <option value="">Nessun prodotto...</option>
                            @foreach($prodotti as $prodotto)
                                <option value="{{ $prodotto->id }}">{{ $prodotto->codice }} - {{ $prodotto->nome }}</option>
                            @endforeach
                        </select>
                        @error('prodotto_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Categoria Output -->
                    <div>
                        <label class="form-label">Categoria Output</label>
                        <select wire:model="categoria_output" class="form-select @error('categoria_output') is-invalid @enderror">
                            <option value="">Seleziona categoria...</option>
                            @foreach($categorieOutput as $categoria)
                                <option value="{{ $categoria->value }}">{{ $categoria->label() }}</option>
                            @endforeach
                        </select>
                        @error('categoria_output')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Stato -->
                    <div>
                        <label class="form-label">Stato</label>
                        <div class="flex items-center mt-2">
                            <input type="checkbox" wire:model="is_active" id="is_active" class="form-checkbox">
                            <label for="is_active" class="ml-2 text-sm">Distinta base attiva</label>
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div class="mt-4">
                    <label class="form-label">Note</label>
                    <textarea wire:model="note" class="form-input" rows="2" placeholder="Note aggiuntive..."></textarea>
                </div>
            </div>
        </div>

        <!-- Righe (Componenti) -->
        <div class="card mb-6">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">Componenti (Materie Prime)</h3>
                <button type="button" wire:click="aggiungiRiga" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Aggiungi Componente
                </button>
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
                                <th class="w-64">Materia Prima</th>
                                <th>Descrizione</th>
                                <th class="w-24">
                                    Quantita
                                    <span class="block text-xs font-normal text-muted-foreground">(riferimento)</span>
                                </th>
                                <th class="w-24">UM</th>
                                <th class="w-24">Scarto %</th>
                                <th class="w-20 text-center">FITOK</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($righe as $index => $riga)
                                <tr wire:key="riga-{{ $index }}">
                                    <td class="text-center text-muted-foreground">{{ $index + 1 }}</td>
                                    <td>
                                        <select
                                            wire:model="righe.{{ $index }}.prodotto_id"
                                            wire:change="selezionaProdotto({{ $index }}, $event.target.value)"
                                            class="form-select form-select-sm @error('righe.'.$index.'.prodotto_id') is-invalid @enderror"
                                        >
                                            <option value="">Seleziona...</option>
                                            @foreach($materiePrime as $materiaPrima)
                                                <option value="{{ $materiaPrima->id }}">{{ $materiaPrima->codice }} - {{ $materiaPrima->nome }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            wire:model="righe.{{ $index }}.descrizione"
                                            class="form-input form-input-sm @error('righe.'.$index.'.descrizione') is-invalid @enderror"
                                            placeholder="Descrizione..."
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            wire:model="righe.{{ $index }}.quantita"
                                            class="form-input form-input-sm text-right @error('righe.'.$index.'.quantita') is-invalid @enderror"
                                            step="0.0001"
                                            min="0.0001"
                                        >
                                    </td>
                                    <td>
                                        <select
                                            wire:model="righe.{{ $index }}.unita_misura"
                                            class="form-select form-select-sm @error('righe.'.$index.'.unita_misura') is-invalid @enderror"
                                        >
                                            @foreach($unitaMisura as $um)
                                                <option value="{{ $um->value }}">{{ $um->abbreviation() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <input
                                                type="number"
                                                wire:model="righe.{{ $index }}.coefficiente_scarto"
                                                class="form-input form-input-sm text-right w-16 @error('righe.'.$index.'.coefficiente_scarto') is-invalid @enderror"
                                                step="0.01"
                                                min="0"
                                                max="1"
                                            >
                                            <span class="ml-1 text-muted-foreground text-xs">
                                                ({{ number_format(($riga['coefficiente_scarto'] ?? 0) * 100, 0) }}%)
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input
                                            type="checkbox"
                                            wire:model="righe.{{ $index }}.is_fitok_required"
                                            class="form-checkbox"
                                        >
                                    </td>
                                    <td>
                                        @if(count($righe) > 1)
                                            <button
                                                type="button"
                                                wire:click="rimuoviRiga({{ $index }})"
                                                class="text-red-600 hover:text-red-800"
                                                title="Rimuovi componente"
                                            >
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
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

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('bom.index') }}" class="btn-secondary">
                Annulla
            </a>
            <button type="submit" class="btn-primary">
                {{ $isEditing ? 'Aggiorna Distinta Base' : 'Crea Distinta Base' }}
            </button>
        </div>
    </form>
</div>
