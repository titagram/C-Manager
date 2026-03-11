<div>
    <form wire:submit="save">
        <!-- Header -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Dati Ordine</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Cliente -->
                    <div>
                        <label class="form-label required">Cliente</label>
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

                    <!-- Data Consegna Prevista -->
                    <div>
                        <label class="form-label">Data Consegna Prevista</label>
                        <input type="date" wire:model="data_consegna_prevista" class="form-input">
                    </div>

                    <!-- Descrizione -->
                    <div>
                        <label class="form-label">Descrizione</label>
                        <input type="text" wire:model="descrizione" class="form-input" placeholder="Descrizione ordine...">
                    </div>
                </div>

                <!-- Note -->
                <div class="mt-4">
                    <label class="form-label">Note</label>
                    <textarea wire:model="note" class="form-input" rows="2" placeholder="Note aggiuntive..."></textarea>
                </div>
            </div>
        </div>

        <!-- Righe -->
        <div class="card mb-6">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">Righe Ordine</h3>
                <button type="button" wire:click="aggiungiRiga" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Aggiungi Riga
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
                                <th>Descrizione</th>
                                <th class="w-24">L (mm)</th>
                                <th class="w-24">P (mm)</th>
                                <th class="w-24">H (mm)</th>
                                <th class="w-20">Qta</th>
                                <th class="w-24 text-right">MC</th>
                                <th class="w-24">EUR/MC</th>
                                <th class="w-28 text-right">Totale</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($righe as $index => $riga)
                                <tr wire:key="riga-{{ $index }}">
                                    <td class="text-center text-muted-foreground">{{ $index + 1 }}</td>
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
                                            wire:model.blur="righe.{{ $index }}.larghezza_mm"
                                            class="form-input form-input-sm text-right @error('righe.'.$index.'.larghezza_mm') is-invalid @enderror"
                                            step="1"
                                            min="1"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            wire:model.blur="righe.{{ $index }}.profondita_mm"
                                            class="form-input form-input-sm text-right @error('righe.'.$index.'.profondita_mm') is-invalid @enderror"
                                            step="1"
                                            min="1"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            wire:model.blur="righe.{{ $index }}.altezza_mm"
                                            class="form-input form-input-sm text-right @error('righe.'.$index.'.altezza_mm') is-invalid @enderror"
                                            step="1"
                                            min="1"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            wire:model.blur="righe.{{ $index }}.quantita"
                                            class="form-input form-input-sm text-right @error('righe.'.$index.'.quantita') is-invalid @enderror"
                                            min="1"
                                        >
                                    </td>
                                    <td class="text-right font-mono">
                                        {{ number_format($riga['volume_mc'] ?? 0, 4, ',', '.') }}
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            wire:model.blur="righe.{{ $index }}.prezzo_mc"
                                            class="form-input form-input-sm text-right @error('righe.'.$index.'.prezzo_mc') is-invalid @enderror"
                                            step="0.01"
                                            min="0"
                                        >
                                    </td>
                                    <td class="text-right font-medium">
                                        EUR {{ number_format($riga['totale_riga'] ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        @if(count($righe) > 1)
                                            <button
                                                type="button"
                                                wire:click="rimuoviRiga({{ $index }})"
                                                class="text-red-600 hover:text-red-800"
                                                title="Rimuovi riga"
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

        <!-- Totale -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="flex justify-end">
                    <div class="w-80 space-y-2">
                        <div class="flex justify-between py-3 text-lg">
                            <span class="font-semibold">TOTALE:</span>
                            <span class="font-bold text-primary">EUR {{ number_format($totale, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('ordini.index') }}" class="btn-secondary">
                Annulla
            </a>
            <button type="submit" class="btn-primary">
                {{ $isEditing ? 'Aggiorna Ordine' : 'Crea Ordine' }}
            </button>
        </div>
    </form>
</div>
