<div>
    <!-- Filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Ricerca -->
                <div class="md:col-span-2">
                    <label class="form-label">Cerca</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-input"
                        placeholder="Numero, descrizione, cliente...">
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
                        <th class="cursor-pointer" wire:click="sortBy('data')">
                            Data
                            @if($sortField === 'data')
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
                        <th>Stato</th>
                        <th>Validità</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preventivi as $preventivo)
                    <tr>
                        <td class="font-medium">{{ $preventivo->numero }}</td>
                        <td>{{ $preventivo->data->format('d/m/Y') }}</td>
                        <td>{{ $preventivo->cliente?->ragione_sociale ?? 'N/D' }}</td>
                        <td class="max-w-xs truncate">{{ $preventivo->descrizione ?? '-' }}</td>
                        <td class="text-right font-medium">€ {{ number_format($preventivo->totale, 2, ',', '.') }}</td>
                        <td>
                            <span class="badge badge-{{ $preventivo->stato->color() }}">
                                {{ $preventivo->stato->label() }}
                            </span>
                        </td>
                        <td>
                            @if($preventivo->validita_fino)
                            <span class="{{ $preventivo->isScaduto() ? 'text-red-600' : '' }}">
                                {{ $preventivo->validita_fino->format('d/m/Y') }}
                            </span>
                            @else
                            -
                            @endif
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <!-- Visualizza/Modifica -->
                                <a href="{{ route('preventivi.show', $preventivo->id) }}" class="btn-sm btn-secondary"
                                    title="Modifica">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </a>

                                <!-- Cambio Stato Dropdown -->
                                @if($preventivo->stato->value === 'bozza')
                                <button wire:click="cambiaStato({{ $preventivo->id }}, 'inviato')"
                                    wire:confirm="Confermi di segnare il preventivo come Inviato?"
                                    wire:loading.attr="disabled" class="btn-sm btn-primary" title="Segna come inviato">
                                    <svg wire:loading.remove wire:target="cambiaStato({{ $preventivo->id }}, 'inviato')"
                                        class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    <span wire:loading wire:target="cambiaStato({{ $preventivo->id }}, 'inviato')"
                                        class="loading loading-spinner loading-xs"></span>
                                </button>
                                @endif

                                @if($preventivo->stato->value === 'inviato')
                                <button wire:click="cambiaStato({{ $preventivo->id }}, 'accettato')"
                                    wire:confirm="Confermi di accettare il preventivo? Sarà possibile convertirlo in ordine."
                                    wire:loading.attr="disabled"
                                    class="btn-sm bg-green-600 text-white hover:bg-green-700" title="Accettato">
                                    <svg wire:loading.remove
                                        wire:target="cambiaStato({{ $preventivo->id }}, 'accettato')" class="w-4 h-4"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    <span wire:loading wire:target="cambiaStato({{ $preventivo->id }}, 'accettato')"
                                        class="loading loading-spinner loading-xs"></span>
                                </button>
                                <button wire:click="cambiaStato({{ $preventivo->id }}, 'rifiutato')"
                                    wire:confirm="Confermi di rifiutare il preventivo? Non sarà più utilizzabile."
                                    wire:loading.attr="disabled" class="btn-sm bg-red-600 text-white hover:bg-red-700"
                                    title="Rifiutato">
                                    <svg wire:loading.remove
                                        wire:target="cambiaStato({{ $preventivo->id }}, 'rifiutato')" class="w-4 h-4"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                    <span wire:loading wire:target="cambiaStato({{ $preventivo->id }}, 'rifiutato')"
                                        class="loading loading-spinner loading-xs"></span>
                                </button>
                                @endif

                                <!-- Converti in Ordine -->
                                @if($preventivo->stato === \App\Enums\StatoPreventivo::ACCETTATO &&
                                !($preventivo->ordine_exists ?? false))
                                <button wire:click="convertiInOrdine({{ $preventivo->id }})"
                                    wire:confirm="Vuoi creare un ordine da questo preventivo?"
                                    wire:loading.attr="disabled"
                                    class="btn-sm bg-primary text-white hover:bg-primary/90" title="Converti in Ordine">
                                    <svg wire:loading.remove wire:target="convertiInOrdine({{ $preventivo->id }})"
                                        class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                    <span wire:loading wire:target="convertiInOrdine({{ $preventivo->id }})"
                                        class="loading loading-spinner loading-xs"></span>
                                </button>
                                @endif

                                <!-- Duplica -->
                                <button wire:click="duplica({{ $preventivo->id }})" wire:loading.attr="disabled"
                                    class="btn-sm btn-secondary" title="Duplica">
                                    <svg wire:loading.remove wire:target="duplica({{ $preventivo->id }})"
                                        class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                    </svg>
                                    <span wire:loading wire:target="duplica({{ $preventivo->id }})"
                                        class="loading loading-spinner loading-xs"></span>
                                </button>

                                <!-- PDF -->
                                <a href="{{ route('preventivi.pdf', $preventivo->id) }}" class="btn-sm btn-secondary"
                                    title="Scarica PDF" target="_blank">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </a>

                                <!-- Elimina (solo bozze) -->
                                @if($preventivo->stato->value === 'bozza')
                                <button wire:click="delete({{ $preventivo->id }})"
                                    wire:confirm="Sei sicuro di voler eliminare questo preventivo?"
                                    wire:loading.attr="disabled" class="btn-sm btn-danger" title="Elimina">
                                    <svg wire:loading.remove wire:target="delete({{ $preventivo->id }})" class="w-4 h-4"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    <span wire:loading wire:target="delete({{ $preventivo->id }})"
                                        class="loading loading-spinner loading-xs"></span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted-foreground py-8">
                            Nessun preventivo trovato
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($preventivi->hasPages())
        <div class="card-footer">
            {{ $preventivi->links() }}
        </div>
        @endif
    </div>
</div>