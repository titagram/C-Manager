<div>
    @error('action')
        <div class="alert alert-error mb-4">
            {{ $message }}
        </div>
    @enderror

    <!-- Filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Cerca per codice, prodotto, cliente..."
                            class="form-input pl-10 w-full"
                        >
                    </div>
                </div>

                <!-- Stato Filter -->
                <div class="w-full md:w-44">
                    <select wire:model.live="stato" class="form-select w-full">
                        <option value="">Tutti gli stati</option>
                        @foreach($stati as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Cliente Filter -->
                <div class="w-full md:w-56">
                    <select wire:model.live="cliente" class="form-select w-full">
                        <option value="">Tutti i clienti</option>
                        @foreach($clienti as $c)
                            <option value="{{ $c->id }}">{{ $c->ragione_sociale }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Trashed Filter -->
                <div class="flex items-center">
                    <label class="flex items-center space-x-2 cursor-pointer border border-input rounded-md px-3 py-2 bg-background hover:bg-accent hover:text-accent-foreground transition-colors {{ $trashed ? 'bg-destructive/10 border-destructive/20 text-destructive' : '' }}">
                        <input wire:model.live="trashed" type="checkbox" class="form-checkbox h-4 w-4 text-destructive border-input rounded focus:ring-destructive">
                        <span class="text-sm font-medium">Cestino</span>
                    </label>
                </div>

                <!-- Reset -->
                @if($search || $stato || $cliente)
                    <button wire:click="resetFilters" class="btn-ghost">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="card">
        <div class="hidden md:block overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('codice_lotto')">
                            <div class="flex items-center gap-2">
                                Codice
                                @if($sortField === 'codice_lotto')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>Prodotto Finale</th>
                        <th>Cliente</th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-2">
                                Date
                                @if($sortField === 'created_at')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="text-center">Mov.</th>
                        <th class="text-center">
                            <span class="inline-flex items-center gap-1">
                                <span>Preparazione avvio</span>
                                <x-help-tooltip
                                    text="Indica se il lotto ha tutti i dati necessari per essere avviato in produzione (materiali calcolati e componenti manuali completi)."
                                    placement="bottom"
                                />
                            </span>
                        </th>
                        <th class="text-center">Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lotti as $lotto)
                        <tr wire:key="lotto-{{ $lotto->id }}">
                            <td class="font-mono font-medium">{{ $lotto->codice_lotto }}</td>
                            <td>
                                <div>
                                    <div class="font-medium">{{ $lotto->prodotto_finale }}</div>
                                    @if($lotto->descrizione)
                                        <div class="text-xs text-muted-foreground truncate max-w-xs">{{ $lotto->descrizione }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($lotto->cliente || $lotto->preventivo?->cliente)
                                    {{ $lotto->cliente?->ragione_sociale ?? $lotto->preventivo?->cliente?->ragione_sociale }}
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                <div>
                                    <span class="text-muted-foreground">Creato:</span>
                                    {{ $lotto->created_at->format('d/m/Y') }}
                                </div>
                                @if($lotto->data_inizio)
                                    <div>
                                        <span class="text-muted-foreground">Inizio:</span>
                                        {{ $lotto->data_inizio->format('d/m/Y') }}
                                    </div>
                                @endif
                                @if($lotto->data_fine)
                                    <div>
                                        <span class="text-muted-foreground">Fine:</span>
                                        {{ $lotto->data_fine->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ $lotto->movimenti_count }}</span>
                            </td>
                            <td class="text-center">
                                @php $readiness = $readinessByLotto[$lotto->id] ?? null; @endphp
                                @if(in_array($lotto->stato->value, ['in_lavorazione', 'completato', 'annullato'], true))
                                    <span class="badge badge-muted" title="Indicatore valido solo prima dell'avvio produzione">Non rilevante</span>
                                @elseif(!$readiness)
                                    <span class="badge badge-muted">N/D</span>
                                @elseif($readiness['ready'])
                                    <span class="badge badge-success" title="Lotto pronto per avvio">Pronto</span>
                                @else
                                    <span class="badge badge-warning" title="{{ $readiness['message'] }}">Da completare</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeClass = match($lotto->stato) {
                                        \App\Enums\StatoLottoProduzione::BOZZA => 'badge-muted',
                                        \App\Enums\StatoLottoProduzione::CONFERMATO => 'badge-info',
                                        \App\Enums\StatoLottoProduzione::IN_LAVORAZIONE => 'badge-primary',
                                        \App\Enums\StatoLottoProduzione::COMPLETATO => 'badge-success',
                                        \App\Enums\StatoLottoProduzione::ANNULLATO => 'badge-destructive',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $lotto->stato->label() }}</span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($lotto->trashed())
                                        @can('restore', $lotto)
                                            <button
                                                wire:click="restore({{ $lotto->id }})"
                                                wire:confirm="Ripristinare questo lotto?"
                                                class="btn-icon text-success hover:bg-success/10"
                                                title="Ripristina"
                                            >
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                        @endcan

                                        @can('forceDelete', $lotto)
                                            <button
                                                wire:click="forceDelete({{ $lotto->id }})"
                                                wire:confirm="Eliminare DEFINITIVAMENTE questo lotto? Questa azione è irreversibile."
                                                class="btn-icon text-destructive hover:bg-destructive/10"
                                                title="Elimina definitivamente"
                                            >
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        @endcan
                                    @else
                                        @can('start', $lotto)
                                            <button
                                                wire:click="avvia({{ $lotto->id }})"
                                                wire:confirm="Avviare la lavorazione?"
                                                class="btn-icon text-primary hover:bg-primary/10"
                                                title="Avvia lavorazione"
                                            >
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                                </svg>
                                            </button>
                                        @endcan

                                        @can('complete', $lotto)
                                            <button
                                                wire:click="completa({{ $lotto->id }})"
                                                wire:confirm="Completare la lavorazione?"
                                                class="btn-icon text-success hover:bg-success/10"
                                                title="Completa"
                                            >
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        @endcan

                                        <a href="{{ route('lotti.show', $lotto) }}" class="btn-icon" title="Dettagli">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>

                                        @can('cancel', $lotto)
                                            <button
                                                wire:click="annulla({{ $lotto->id }})"
                                                wire:confirm="Annullare questo lotto?"
                                                class="btn-icon text-destructive hover:bg-destructive/10"
                                                title="Annulla"
                                            >
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endcan

                                        @can('delete', $lotto)
                                            <button
                                                wire:click="delete({{ $lotto->id }})"
                                                wire:confirm="Eliminare questo lotto?"
                                                class="btn-icon text-destructive hover:bg-destructive/10"
                                                title="Elimina"
                                            >
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                    <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" />
                                    </svg>
                                    <p>Nessun lotto di produzione trovato</p>
                                    @if($search || $stato || $cliente)
                                        <button wire:click="resetFilters" class="btn-link text-sm">
                                            Rimuovi filtri
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="lotti-mobile-list" class="md:hidden divide-y divide-border">
            @forelse($lotti as $lotto)
                <div class="p-4 space-y-3" wire:key="lotto-mobile-{{ $lotto->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-mono font-semibold">{{ $lotto->codice_lotto }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ $lotto->cliente?->ragione_sociale ?? $lotto->preventivo?->cliente?->ragione_sociale ?? '-' }}
                            </div>
                        </div>
                        <div class="text-right">
                            @php
                                $badgeClass = match($lotto->stato) {
                                    \App\Enums\StatoLottoProduzione::BOZZA => 'badge-muted',
                                    \App\Enums\StatoLottoProduzione::CONFERMATO => 'badge-info',
                                    \App\Enums\StatoLottoProduzione::IN_LAVORAZIONE => 'badge-primary',
                                    \App\Enums\StatoLottoProduzione::COMPLETATO => 'badge-success',
                                    \App\Enums\StatoLottoProduzione::ANNULLATO => 'badge-destructive',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $lotto->stato->label() }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="font-medium">{{ $lotto->prodotto_finale }}</div>
                        @if($lotto->descrizione)
                            <div class="text-xs text-muted-foreground">{{ $lotto->descrizione }}</div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-muted-foreground">Creato:</span>
                            {{ $lotto->created_at->format('d/m/Y') }}
                        </div>
                        <div>
                            <span class="text-muted-foreground">Movimenti:</span>
                            {{ $lotto->movimenti_count }}
                        </div>
                        @php $readiness = $readinessByLotto[$lotto->id] ?? null; @endphp
                        <div class="col-span-2">
                            <span class="text-muted-foreground">Preparazione avvio:</span>
                            @if(in_array($lotto->stato->value, ['in_lavorazione', 'completato', 'annullato'], true))
                                <span class="badge badge-muted">Non rilevante</span>
                            @elseif(!$readiness)
                                <span class="badge badge-muted">N/D</span>
                            @elseif($readiness['ready'])
                                <span class="badge badge-success">Pronto</span>
                            @else
                                <span class="badge badge-warning">Da completare</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        @if($lotto->trashed())
                            @can('restore', $lotto)
                                <button
                                    wire:click="restore({{ $lotto->id }})"
                                    wire:confirm="Ripristinare questo lotto?"
                                    class="btn-sm bg-green-600 text-white hover:bg-green-700"
                                >
                                    Ripristina
                                </button>
                            @endcan
                            @can('forceDelete', $lotto)
                                <button
                                    wire:click="forceDelete({{ $lotto->id }})"
                                    wire:confirm="Eliminare DEFINITIVAMENTE questo lotto? Questa azione è irreversibile."
                                    class="btn-sm btn-danger"
                                >
                                    Elimina definitiva
                                </button>
                            @endcan
                        @else
                            @can('start', $lotto)
                                <button
                                    wire:click="avvia({{ $lotto->id }})"
                                    wire:confirm="Avviare la lavorazione?"
                                    class="btn-sm btn-primary"
                                >
                                    Avvia
                                </button>
                            @endcan

                            @can('complete', $lotto)
                                <button
                                    wire:click="completa({{ $lotto->id }})"
                                    wire:confirm="Completare la lavorazione?"
                                    class="btn-sm bg-green-600 text-white hover:bg-green-700"
                                >
                                    Completa
                                </button>
                            @endcan

                            <a href="{{ route('lotti.show', $lotto) }}" class="btn-sm btn-secondary">
                                Dettagli
                            </a>

                            @can('cancel', $lotto)
                                <button
                                    wire:click="annulla({{ $lotto->id }})"
                                    wire:confirm="Annullare questo lotto?"
                                    class="btn-sm btn-danger"
                                >
                                    Annulla
                                </button>
                            @endcan

                            @can('delete', $lotto)
                                <button
                                    wire:click="delete({{ $lotto->id }})"
                                    wire:confirm="Eliminare questo lotto?"
                                    class="btn-sm btn-danger"
                                >
                                    Elimina
                                </button>
                            @endcan
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-muted-foreground">
                    Nessun lotto di produzione trovato
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($lotti->hasPages())
            <div class="card-footer">
                {{ $lotti->links() }}
            </div>
        @endif
    </div>
</div>
