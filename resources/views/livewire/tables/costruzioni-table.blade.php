<div>
    <!-- Filtri -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cerca costruzione..."
                            class="form-input pl-10 w-full">
                    </div>
                </div>

                <!-- Categoria Filter -->
                <div class="w-full md:w-48">
                    <select wire:model.live="categoria" class="form-select w-full">
                        <option value="">Tutte le categorie</option>
                        @foreach ($tipiCostruzione as $tipo)
                            <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Stato Filter -->
                <div class="w-full md:w-40">
                    <select wire:model.live="stato" class="form-select w-full">
                        <option value="">Tutti</option>
                        <option value="attivi">Solo attivi</option>
                        <option value="inattivi">Solo inattivi</option>
                    </select>
                </div>

                <!-- Reset -->
                @if ($search || $categoria || $stato)
                    <button wire:click="resetFilters" class="btn-ghost">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
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
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('nome')">
                            <div class="flex items-center gap-2">
                                Nome
                                @if ($sortField === 'nome')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer hover:bg-muted/50" wire:click="sortBy('categoria')">
                            <div class="flex items-center gap-2">
                                Categoria
                                @if ($sortField === 'categoria')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th>Descrizione</th>
                        <th class="text-center">Stato</th>
                        <th class="text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costruzioni as $costruzione)
                        <tr wire:key="costruzione-{{ $costruzione->id }}">
                            <td>
                                <div class="font-medium">{{ $costruzione->nome }}</div>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ \App\Enums\TipoCostruzione::from($costruzione->categoria)->label() }}
                                </span>
                            </td>
                            <td>
                                @if ($costruzione->descrizione)
                                    <div class="text-sm text-muted-foreground truncate max-w-md">
                                        {{ $costruzione->descrizione }}
                                    </div>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button wire:click="toggleActive({{ $costruzione->id }})" wire:loading.attr="disabled"
                                    class="inline-flex" title="{{ $costruzione->is_active ? 'Disattiva' : 'Attiva' }}">
                                    @if ($costruzione->is_active)
                                        <span class="badge badge-success">Attiva</span>
                                    @else
                                        <span class="badge badge-secondary">Inattiva</span>
                                    @endif
                                </button>
                            </td>
                            <td class="text-right">
                                <div class="flex gap-2 justify-end">
                                    @can('update', $costruzione)
                                        <a href="{{ route('costruzioni.edit', $costruzione) }}" class="btn-icon"
                                            title="Modifica">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('create', \App\Models\Costruzione::class)
                                        <button
                                            wire:click="duplica({{ $costruzione->id }})"
                                            wire:loading.attr="disabled"
                                            class="btn-ghost btn-sm"
                                            title="Duplica"
                                        >
                                            Duplica
                                        </button>
                                    @endcan

                                    @can('delete', $costruzione)
                                        <button wire:click="delete({{ $costruzione->id }})"
                                            wire:confirm="Sei sicuro di voler eliminare questa costruzione?"
                                            class="btn-ghost btn-sm text-destructive hover:bg-destructive/10"
                                            title="Elimina">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted-foreground py-8">
                                Nessuna costruzione trovata.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($costruzioni->hasPages())
            <div class="card-footer">
                {{ $costruzioni->links() }}
            </div>
        @endif
    </div>
</div>
