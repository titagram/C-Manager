<div>
    @error('stato')
        <div class="alert alert-error mb-4">
            {{ $message }}
        </div>
    @enderror

    <!-- Filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Ricerca -->
                <div class="md:col-span-2">
                    <label class="form-label">Cerca</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="form-input"
                        placeholder="Numero, descrizione, cliente..."
                    >
                </div>

                <!-- Filtro Stato -->
                <div>
                    <label class="form-label">Stato</label>
                    <select wire:model.live="stato" class="form-select">
                        <option value="">Tutti gli stati</option>
                        @foreach($stati as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset -->
                <div class="flex items-end">
                    <button wire:click="resetFilters" class="btn-secondary w-full">
                        Reset Filtri
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="cursor-pointer" wire:click="sortBy('numero')">
                            Numero
                            @if($sortField === 'numero')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="cursor-pointer" wire:click="sortBy('data_ordine')">
                            Data
                            @if($sortField === 'data_ordine')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th>Cliente</th>
                        <th>Descrizione</th>
                        <th class="cursor-pointer text-right" wire:click="sortBy('totale')">
                            Totale
                            @if($sortField === 'totale')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th>
                            <span class="inline-flex items-center gap-1">
                                <span>Preparazione avvio</span>
                                <x-help-tooltip
                                    text="Indica se tutti i lotti ordine sono pronti per l'avvio della produzione. Non coincide con lo stato ordine &quot;Pronto&quot;."
                                    placement="bottom"
                                />
                            </span>
                        </th>
                        <th>Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordini as $ordine)
                        <tr>
                            <td class="font-medium">{{ $ordine->numero }}</td>
                            <td>{{ $ordine->data_ordine->format('d/m/Y') }}</td>
                            <td>{{ $ordine->cliente?->ragione_sociale ?? 'N/D' }}</td>
                            <td class="max-w-xs truncate">{{ $ordine->descrizione ?? '-' }}</td>
                            <td class="text-right font-medium">{{ number_format($ordine->totale, 2, ',', '.') }} &euro;</td>
                            <td>
                                @php $readiness = $readinessByOrdine[$ordine->id] ?? null; @endphp
                                @if(! in_array($ordine->stato->value, ['confermato'], true))
                                    <span class="badge badge-muted">Non rilevante</span>
                                @elseif(!$readiness || $readiness['total_lotti'] === 0)
                                    <span class="badge badge-muted">Nessun lotto</span>
                                @elseif($readiness['ready'])
                                    <span class="badge badge-success">Preparato ({{ $readiness['lotti_pronti'] }}/{{ $readiness['total_lotti'] }})</span>
                                @else
                                    <span class="badge badge-warning" title="{{ $readiness['issues'][0]['message'] ?? 'Lotti non pronti' }}">
                                        Da completare ({{ $readiness['lotti_pronti'] }}/{{ $readiness['total_lotti'] }})
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $ordine->stato->color() }}">
                                    {{ $ordine->stato->label() }}
                                </span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <!-- Visualizza/Modifica -->
                                    <a href="{{ route('ordini.show', $ordine->id) }}" class="btn-sm btn-secondary" title="Visualizza">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>

                                    <!-- Cambio Stato Buttons -->
                                    @if($ordine->stato->value === 'confermato')
                                        <button
                                            wire:click="cambiaStato({{ $ordine->id }}, 'in_produzione')"
                                            wire:loading.attr="disabled"
                                            @disabled(($readinessByOrdine[$ordine->id]['ready'] ?? false) === false)
                                            class="btn-sm btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="{{ ($readinessByOrdine[$ordine->id]['ready'] ?? false) ? 'Avvia Produzione' : (($readinessByOrdine[$ordine->id]['issues'][0]['message'] ?? 'Ordine non pronto')) }}"
                                        >
                                            <svg wire:loading.remove wire:target="cambiaStato({{ $ordine->id }}, 'in_produzione')" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                            </svg>
                                            <span wire:loading wire:target="cambiaStato({{ $ordine->id }}, 'in_produzione')" class="loading loading-spinner loading-xs"></span>
                                        </button>
                                    @endif

                                    @if($ordine->stato->value === 'in_produzione')
                                        <button wire:click="cambiaStato({{ $ordine->id }}, 'pronto')" wire:loading.attr="disabled" class="btn-sm bg-green-600 text-white hover:bg-green-700" title="Segna come Pronto">
                                            <svg wire:loading.remove wire:target="cambiaStato({{ $ordine->id }}, 'pronto')" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            <span wire:loading wire:target="cambiaStato({{ $ordine->id }}, 'pronto')" class="loading loading-spinner loading-xs"></span>
                                        </button>
                                    @endif

                                    @if($ordine->stato->value === 'pronto')
                                        <button wire:click="cambiaStato({{ $ordine->id }}, 'consegnato')" wire:loading.attr="disabled" class="btn-sm bg-teal-600 text-white hover:bg-teal-700" title="Segna come Consegnato">
                                            <svg wire:loading.remove wire:target="cambiaStato({{ $ordine->id }}, 'consegnato')" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                            </svg>
                                            <span wire:loading wire:target="cambiaStato({{ $ordine->id }}, 'consegnato')" class="loading loading-spinner loading-xs"></span>
                                        </button>
                                    @endif

                                    @if($ordine->stato->value === 'consegnato')
                                        <button wire:click="cambiaStato({{ $ordine->id }}, 'fatturato')" wire:loading.attr="disabled" class="btn-sm bg-gray-600 text-white hover:bg-gray-700" title="Segna come Fatturato">
                                            <svg wire:loading.remove wire:target="cambiaStato({{ $ordine->id }}, 'fatturato')" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span wire:loading wire:target="cambiaStato({{ $ordine->id }}, 'fatturato')" class="loading loading-spinner loading-xs"></span>
                                        </button>
                                    @endif

                                    <!-- Annulla (solo se editable) -->
                                    @if($ordine->canBeEdited())
                                        <button
                                            wire:click="cambiaStato({{ $ordine->id }}, 'annullato')"
                                            wire:confirm="Sei sicuro di voler annullare questo ordine?"
                                            wire:loading.attr="disabled"
                                            class="btn-sm btn-danger"
                                            title="Annulla Ordine"
                                        >
                                            <svg wire:loading.remove wire:target="cambiaStato({{ $ordine->id }}, 'annullato')" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                            <span wire:loading wire:target="cambiaStato({{ $ordine->id }}, 'annullato')" class="loading loading-spinner loading-xs"></span>
                                        </button>
                                    @endif

                                    <!-- Elimina (solo se editable) -->
                                    @if($ordine->canBeEdited())
                                        <button
                                            wire:click="delete({{ $ordine->id }})"
                                            wire:confirm="Sei sicuro di voler eliminare questo ordine?"
                                            wire:loading.attr="disabled"
                                            class="btn-sm btn-danger"
                                            title="Elimina"
                                        >
                                            <svg wire:loading.remove wire:target="delete({{ $ordine->id }})" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            <span wire:loading wire:target="delete({{ $ordine->id }})" class="loading loading-spinner loading-xs"></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted-foreground py-8">
                                Nessun ordine trovato
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ordini->hasPages())
            <div class="card-footer">
                {{ $ordini->links() }}
            </div>
        @endif
    </div>
</div>
