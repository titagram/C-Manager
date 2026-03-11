<x-layouts.app title="Dettaglio Distinta Base">
    @php
        $bomModel = App\Models\Bom::with(['prodotto', 'createdBy', 'righe.prodotto'])->findOrFail($bom->id);
        $isTemplate = $bomModel->source === 'template';
    @endphp

    <x-page-header title="Distinta Base {{ $bomModel->codice }}" description="Dettaglio della distinta base">
        <div class="flex gap-2">
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('bom.edit', $bomModel) }}" class="btn-primary">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Modifica
                </a>
            @endif
            <a href="{{ route('bom.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Torna alla lista
            </a>
        </div>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Info BOM -->
        <div class="lg:col-span-2 space-y-6">
            @if($isTemplate)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Distinta Base Template:</strong> Le quantita indicate sono valori di riferimento. Quando questa distinta viene utilizzata in un lotto di produzione, le quantita effettive verranno calcolate automaticamente dal CuttingOptimizer in base alle dimensioni reali del prodotto.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800">
                                <strong>Distinta Materiali Generata:</strong> questa distinta contiene il fabbisogno aggregato dei materiali per l'ordine avviato in produzione.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Dettagli Principali -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Dettagli Distinta Base</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Codice</dt>
                            <dd class="mt-1 text-sm font-mono">{{ $bomModel->codice }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Stato</dt>
                            <dd class="mt-1">
                                @if($bomModel->is_active)
                                    <span class="badge" style="background-color: #22c55e20; color: #22c55e;">
                                        Attiva
                                    </span>
                                @else
                                    <span class="badge" style="background-color: #ef444420; color: #ef4444;">
                                        Non Attiva
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground">Nome</dt>
                            <dd class="mt-1 text-sm">{{ $bomModel->nome }}</dd>
                        </div>
                        @if($isTemplate)
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">Versione</dt>
                                <dd class="mt-1 text-sm font-mono">{{ $bomModel->versione }}</dd>
                            </div>
                        @endif
                        @if($bomModel->prodotto)
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">Prodotto Output</dt>
                                <dd class="mt-1 text-sm">
                                    @if(auth()->user()?->isAdmin())
                                        <a href="{{ route('prodotti.show', $bomModel->prodotto) }}" class="text-primary hover:underline">
                                            {{ $bomModel->prodotto->nome }}
                                        </a>
                                    @else
                                        {{ $bomModel->prodotto->nome }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if($bomModel->categoria_output)
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">Categoria Output</dt>
                                <dd class="mt-1 text-sm">{{ $bomModel->categoria_output }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if($bomModel->note)
                        <div class="mt-4 pt-4 border-t border-border">
                            <dt class="text-sm font-medium text-muted-foreground">Note</dt>
                            <dd class="mt-1 text-sm">{{ $bomModel->note }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Componenti BOM -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Componenti</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Materiale</th>
                                <th class="text-right">
                                    Quantita
                                    <span class="block text-xs font-normal text-muted-foreground">(riferimento)</span>
                                </th>
                                <th class="text-center">U.M.</th>
                                <th class="text-right">Scarto %</th>
                                <th class="text-right">
                                    Quantita Totale
                                    <span class="block text-xs font-normal text-muted-foreground">(con scarto)</span>
                                </th>
                                <th class="text-center">FITOK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bomModel->righe as $riga)
                                <tr>
                                    <td>{{ $riga->ordine }}</td>
                                    <td>
                                        @if($riga->prodotto)
                                            <div class="font-medium">{{ $riga->prodotto->nome }}</div>
                                            @if($riga->prodotto->codice)
                                                <div class="text-xs text-muted-foreground font-mono">{{ $riga->prodotto->codice }}</div>
                                            @endif
                                        @endif
                                        @if($riga->descrizione)
                                            <div class="text-xs text-muted-foreground">{{ $riga->descrizione }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right font-mono text-sm">{{ number_format($riga->quantita, 4, ',', '.') }}</td>
                                    <td class="text-center text-sm">{{ $riga->unita_misura?->abbreviation() ?? '-' }}</td>
                                    <td class="text-right font-mono text-sm">{{ number_format($riga->coefficiente_scarto * 100, 2, ',', '.') }}%</td>
                                    <td class="text-right font-mono text-sm font-medium">{{ number_format($riga->quantitaConScarto(), 4, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($riga->is_fitok_required)
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700">
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-muted-foreground">
                                        Nessun componente presente
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($bomModel->righe->count() > 0)
                            <tfoot>
                                <tr class="bg-muted/50">
                                    <td colspan="5" class="text-right font-medium">
                                        Quantita Totale (riferimento)
                                        <div class="text-xs font-normal text-muted-foreground">Le quantita effettive saranno calcolate dal CuttingOptimizer</div>
                                    </td>
                                    <td class="text-right font-mono font-bold">{{ number_format($bomModel->calcolaQuantitaTotale(), 4, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Riepilogo -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riepilogo</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground">Componenti</span>
                        <span class="font-medium">{{ $bomModel->righe->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground">Componenti FITOK</span>
                        <span class="font-medium">{{ $bomModel->righe->where('is_fitok_required', true)->count() }}</span>
                    </div>
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
                        <span>{{ $bomModel->createdBy?->name ?? 'Sistema' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Creato il</span>
                        <span>{{ $bomModel->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($bomModel->updated_at->ne($bomModel->created_at))
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Aggiornato il</span>
                            <span>{{ $bomModel->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
