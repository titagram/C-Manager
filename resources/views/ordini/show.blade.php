<x-layouts.app title="Dettaglio Ordine">
    @php
        $ordineModel = ($ordine instanceof App\Models\Ordine
            ? $ordine
            : App\Models\Ordine::findOrFail($ordine))
            ->load(['cliente', 'preventivo', 'createdBy', 'righe.prodotto']);
    @endphp

    <x-page-header title="Ordine {{ $ordineModel->numero }}" description="Dettaglio ordine cliente">
        <div class="flex gap-2">
            @if($ordineModel->canBeEdited())
                <a href="{{ route('ordini.edit', $ordineModel) }}" class="btn-primary">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Modifica
                </a>
            @endif
            <a href="{{ route('ordini.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Torna alla lista
            </a>
        </div>
    </x-page-header>

    @php
        $flow = app(\App\Support\ProductionFlowStepper::class)->forOrdine($ordineModel);
        $timeline = app(\App\Support\OperationalTimeline::class)->forOrdine($ordineModel);
        $nextAction = app(\App\Support\NextActionAdvisor::class)->forOrdine($ordineModel);
    @endphp
    <x-production-flow-stepper
        :steps="$flow['steps']"
        :context-label="$flow['context_label']"
    />

    <x-next-action-advice :advice="$nextAction" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Info Ordine -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Dettagli Principali -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Dettagli Ordine</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Numero</dt>
                            <dd class="mt-1 text-sm font-mono">{{ $ordineModel->numero }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Stato</dt>
                            <dd class="mt-1">
                                <span class="badge" style="background-color: {{ $ordineModel->stato->color() }}20; color: {{ $ordineModel->stato->color() }};">
                                    {{ $ordineModel->stato->label() }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Data Ordine</dt>
                            <dd class="mt-1 text-sm">{{ $ordineModel->data_ordine->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Consegna Prevista</dt>
                            <dd class="mt-1 text-sm">{{ $ordineModel->data_consegna_prevista?->format('d/m/Y') ?? '-' }}</dd>
                        </div>
                        @if($ordineModel->data_consegna_effettiva)
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">Consegna Effettiva</dt>
                                <dd class="mt-1 text-sm">{{ $ordineModel->data_consegna_effettiva->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        @if($ordineModel->preventivo)
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">Da Preventivo</dt>
                                <dd class="mt-1 text-sm">
                                    <a href="{{ route('preventivi.show', $ordineModel->preventivo) }}" class="text-primary hover:underline">
                                        {{ $ordineModel->preventivo->numero }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                    @if($ordineModel->descrizione)
                        <div class="mt-4 pt-4 border-t border-border">
                            <dt class="text-sm font-medium text-muted-foreground">Descrizione</dt>
                            <dd class="mt-1 text-sm">{{ $ordineModel->descrizione }}</dd>
                        </div>
                    @endif
                    @if($ordineModel->note)
                        <div class="mt-4 pt-4 border-t border-border">
                            <dt class="text-sm font-medium text-muted-foreground">Note</dt>
                            <dd class="mt-1 text-sm">{{ $ordineModel->note }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Righe Ordine -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Righe Ordine</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Prodotto</th>
                                <th class="text-right">Dimensioni (mm)</th>
                                <th class="text-right">Pezzi</th>
                                <th class="text-right">Volume m³</th>
                                <th class="text-right">Prezzo/m³</th>
                                <th class="text-right">Totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ordineModel->righe as $riga)
                                <tr>
                                    <td>{{ $riga->ordine }}</td>
                                    <td>
                                        <div class="font-medium">{{ $riga->prodotto?->nome ?? '-' }}</div>
                                        @if($riga->descrizione)
                                            <div class="text-xs text-muted-foreground">{{ $riga->descrizione }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right font-mono text-sm">
                                        {{ $riga->larghezza_mm }} x {{ $riga->profondita_mm }} x {{ $riga->altezza_mm }}
                                    </td>
                                    <td class="text-right">{{ $riga->quantita }}</td>
                                    <td class="text-right font-mono text-sm">{{ number_format($riga->volume_mc_finale, 4, ',', '.') }}</td>
                                    <td class="text-right font-mono">{{ number_format($riga->prezzo_mc, 2, ',', '.') }} &euro;</td>
                                    <td class="text-right font-mono font-medium">{{ number_format($riga->totale_riga, 2, ',', '.') }} &euro;</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-muted-foreground">
                                        Nessuna riga presente
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($ordineModel->righe->count() > 0)
                            <tfoot>
                                <tr class="bg-muted/50">
                                    <td colspan="6" class="text-right font-medium">Totale Ordine</td>
                                    <td class="text-right font-mono font-bold text-lg">{{ number_format($ordineModel->totale, 2, ',', '.') }} &euro;</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Cliente -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cliente</h3>
                </div>
                <div class="card-body">
                    @if($ordineModel->cliente)
                        <div class="space-y-2">
                            <p class="font-medium">{{ $ordineModel->cliente->ragione_sociale }}</p>
                            @if($ordineModel->cliente->email)
                                <p class="text-sm text-muted-foreground">{{ $ordineModel->cliente->email }}</p>
                            @endif
                            @if($ordineModel->cliente->telefono)
                                <p class="text-sm text-muted-foreground">{{ $ordineModel->cliente->telefono }}</p>
                            @endif
                            <a href="{{ route('clienti.show', $ordineModel->cliente) }}" class="btn-link text-sm">
                                Visualizza scheda cliente &rarr;
                            </a>
                        </div>
                    @else
                        <p class="text-muted-foreground">Cliente non disponibile</p>
                    @endif
                </div>
            </div>

            <!-- Info Creazione -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informazioni</h3>
                </div>
                <div class="card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Creato da</span>
                        <span>{{ $ordineModel->createdBy?->name ?? 'Sistema' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Creato il</span>
                        <span>{{ $ordineModel->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($ordineModel->updated_at->ne($ordineModel->created_at))
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Aggiornato il</span>
                            <span>{{ $ordineModel->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <x-operational-timeline
                title="Timeline audit ordine"
                :events="$timeline"
            />
        </div>
    </div>
</x-layouts.app>
