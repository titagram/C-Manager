<x-layouts.app title="Movimenti Lotto Materiale">
    <x-page-header
        title="Movimenti Lotto Materiale"
        description="Storico dei movimenti in uscita del lotto {{ $lotto->codice_lotto }}"
    >
        <a href="{{ route('magazzino.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Torna al magazzino
        </a>
        @if(auth()->user()?->isAdmin())
            <a href="{{ route('magazzino.scarico', ['lotto' => $lotto->id]) }}" class="btn-primary">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                </svg>
                Nuovo scarico
            </a>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <div class="card-body">
                <div class="text-sm text-muted-foreground">Lotto materiale</div>
                <div class="text-xl font-semibold font-mono">{{ $lotto->codice_lotto }}</div>
                <div class="text-sm text-muted-foreground mt-1">{{ $lotto->prodotto?->nome ?? 'Prodotto non disponibile' }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="text-sm text-muted-foreground">Totale uscite registrate</div>
                <div class="text-xl font-semibold">
                    {{ number_format((float) ($summary['totale_quantita'] ?? 0), 4, ',', '.') }}
                    {{ $lotto->prodotto?->unita_misura?->abbreviation() ?? '' }}
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="text-sm text-muted-foreground">Scarichi manuali</div>
                <div class="text-xl font-semibold">{{ $summary['manuali'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="text-sm text-muted-foreground">Consumi / rettifiche</div>
                <div class="text-xl font-semibold">
                    {{ ($summary['consumi'] ?? 0) + ($summary['rettifiche_negative'] ?? 0) }}
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Movimenti in uscita</h3>
                <p class="text-sm text-muted-foreground mt-1">
                    In questa vista sono mostrati gli scarichi manuali, gli scarichi derivati dai consumi di produzione e le rettifiche negative del lotto.
                </p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Origine</th>
                            <th class="text-right">Quantità</th>
                            <th>Causale</th>
                            <th>Documento</th>
                            <th>Operatore</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movimenti as $movimento)
                            @php
                                $origineLabel = match (true) {
                                    $movimento->tipo === \App\Enums\TipoMovimento::RETTIFICA_NEGATIVA => 'Rettifica negativa',
                                    $movimento->lottoProduzione !== null => 'Consumo lotto ' . $movimento->lottoProduzione->codice_lotto . ($movimento->lottoProduzione->ordine?->numero ? ' · Ordine ' . $movimento->lottoProduzione->ordine->numero : ''),
                                    default => 'Scarico manuale',
                                };
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap">
                                    {{ optional($movimento->data_movimento ?? $movimento->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span class="badge {{ $movimento->tipo->color() === 'red' ? 'badge-destructive' : 'badge-secondary' }}">
                                        {{ $movimento->tipo->label() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($movimento->lottoProduzione)
                                        <a href="{{ route('lotti.show', $movimento->lottoProduzione) }}" class="font-medium hover:underline">
                                            {{ $origineLabel }}
                                        </a>
                                    @else
                                        {{ $origineLabel }}
                                    @endif
                                </td>
                                <td class="text-right font-mono">
                                    -{{ number_format((float) $movimento->quantita, 4, ',', '.') }}
                                    {{ $lotto->prodotto?->unita_misura?->abbreviation() ?? '' }}
                                </td>
                                <td>{{ $movimento->causale ?: '-' }}</td>
                                <td>
                                    @if ($movimento->documento)
                                        {{ $movimento->documento->riferimento_completo }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $movimento->createdBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-muted-foreground">
                                    Nessun movimento in uscita registrato per questo lotto materiale.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
