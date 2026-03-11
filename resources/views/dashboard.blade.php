<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" description="Panoramica generale del gestionale" />

    <!-- FITOK Alert -->
    @if(isset($lottiInScadenza) && $lottiInScadenza->isNotEmpty())
        <div class="alert alert-warning mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <strong>Attenzione FITOK:</strong> {{ $lottiInScadenza->count() }} lotti con trattamento in scadenza nei prossimi 30 giorni.
                    <a href="{{ route('fitok.index') }}" class="underline ml-2">Visualizza registro</a>
                </div>
            </div>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-stat-card
            title="Lotti in Magazzino"
            :value="$stats['lotti_attivi'] ?? 0"
            color="primary"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Lotti Produzione Attivi"
            :value="$stats['lotti_produzione_attivi'] ?? 0"
            color="accent"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Preventivi Aperti"
            :value="$stats['preventivi_aperti'] ?? 0"
            color="warning"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-warning" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Clienti Attivi"
            :value="$stats['clienti_attivi'] ?? 0"
            color="info"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-info" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Valore Preventivi -->
    @if(($stats['valore_preventivi_aperti'] ?? 0) > 0)
        <div class="card mb-8 bg-gradient-to-r from-primary/10 to-accent/10">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Valore Totale Preventivi Aperti</p>
                        <p class="text-3xl font-bold text-primary">€ {{ number_format($stats['valore_preventivi_aperti'], 2, ',', '.') }}</p>
                    </div>
                    <a href="{{ route('preventivi.index') }}" class="btn-primary">
                        Gestisci Preventivi
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Ultimi Movimenti -->
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h2 class="font-semibold">Ultimi Movimenti</h2>
                <a href="{{ route('magazzino.index') }}" class="text-sm text-primary hover:underline">Vedi tutti</a>
            </div>
            <div class="card-body p-0">
                @if(isset($movimenti) && $movimenti->count() > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Lotto</th>
                                <th class="text-right">Qtà</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movimenti as $movimento)
                                <tr>
                                    <td>{{ $movimento->data_movimento->format('d/m') }}</td>
                                    <td>
                                        <x-status-badge
                                            :status="$movimento->tipo->label()"
                                            :type="$movimento->tipo->value === 'carico' ? 'success' : 'warning'"
                                        />
                                    </td>
                                    <td class="font-mono text-sm">{{ $movimento->lottoMateriale->codice_lotto ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($movimento->quantita, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-6 text-center text-muted-foreground">
                        Nessun movimento recente
                    </div>
                @endif
            </div>
        </div>

        <!-- Lotti Produzione Recenti -->
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h2 class="font-semibold">Lotti Produzione</h2>
                <a href="{{ route('lotti.index') }}" class="text-sm text-primary hover:underline">Vedi tutti</a>
            </div>
            <div class="card-body p-0">
                @if(isset($lottiProduzione) && $lottiProduzione->count() > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Codice</th>
                                <th>Cliente</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lottiProduzione as $lotto)
                                <tr>
                                    <td class="font-mono text-sm">{{ $lotto->codice_lotto }}</td>
                                    <td>{{ Str::limit($lotto->cliente->ragione_sociale ?? '-', 15) }}</td>
                                    <td>
                                        @php
                                            $statusType = match($lotto->stato->value) {
                                                'completato' => 'success',
                                                'in_lavorazione' => 'warning',
                                                'annullato' => 'destructive',
                                                default => 'info',
                                            };
                                        @endphp
                                        <x-status-badge
                                            :status="$lotto->stato->label()"
                                            :type="$statusType"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-6 text-center text-muted-foreground">
                        Nessun lotto di produzione recente
                    </div>
                @endif
            </div>
        </div>

        <!-- Preventivi Recenti -->
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h2 class="font-semibold">Preventivi Recenti</h2>
                <a href="{{ route('preventivi.index') }}" class="text-sm text-primary hover:underline">Vedi tutti</a>
            </div>
            <div class="card-body p-0">
                @if(isset($preventiviRecenti) && $preventiviRecenti->count() > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Cliente</th>
                                <th class="text-right">Totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preventiviRecenti as $preventivo)
                                <tr>
                                    <td>
                                        <a href="{{ route('preventivi.show', $preventivo->id) }}" class="text-primary hover:underline font-mono text-sm">
                                            {{ $preventivo->numero }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($preventivo->cliente->ragione_sociale ?? '-', 15) }}</td>
                                    <td class="text-right font-medium">€ {{ number_format($preventivo->totale, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-6 text-center text-muted-foreground">
                        Nessun preventivo recente
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8">
        <h3 class="text-lg font-semibold mb-4">Azioni Rapide</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <a href="{{ route('magazzino.carico') }}" class="card hover:shadow-lg transition-shadow">
                <div class="card-body text-center py-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="text-sm font-medium">Nuovo Carico</span>
                </div>
            </a>
            <a href="{{ route('magazzino.scarico') }}" class="card hover:shadow-lg transition-shadow">
                <div class="card-body text-center py-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                    </svg>
                    <span class="text-sm font-medium">Nuovo Scarico</span>
                </div>
            </a>
            <a href="{{ route('preventivi.create') }}" class="card hover:shadow-lg transition-shadow">
                <div class="card-body text-center py-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span class="text-sm font-medium">Nuovo Preventivo</span>
                </div>
            </a>
            <a href="{{ route('lotti.create') }}" class="card hover:shadow-lg transition-shadow">
                <div class="card-body text-center py-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                    </svg>
                    <span class="text-sm font-medium">Nuovo Lotto</span>
                </div>
            </a>
            <a href="{{ route('clienti.create') }}" class="card hover:shadow-lg transition-shadow">
                <div class="card-body text-center py-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    <span class="text-sm font-medium">Nuovo Cliente</span>
                </div>
            </a>
            <a href="{{ route('fitok.index') }}" class="card hover:shadow-lg transition-shadow">
                <div class="card-body text-center py-6">
                    <svg class="w-8 h-8 mx-auto mb-2 text-green-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                    <span class="text-sm font-medium">Registro FITOK</span>
                </div>
            </a>
        </div>
    </div>
</x-layouts.app>
