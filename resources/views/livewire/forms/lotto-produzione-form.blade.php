<div>
    <!-- Navigation Header -->
    @if ($returnTo === 'preventivo')
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-900">Configurazione lotto da preventivo</p>
                <p class="text-xs text-blue-700">Configura il lotto e poi torna al preventivo per aggiungere altre
                    righe</p>
            </div>
        </div>
        <button type="button" wire:click="tornaAlPreventivo" class="btn-secondary flex items-center gap-2"
            wire:loading.attr="disabled" wire:target="tornaAlPreventivo">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            <span wire:loading.remove wire:target="tornaAlPreventivo">Torna al Preventivo</span>
            <span wire:loading wire:target="tornaAlPreventivo">
                <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Salvataggio...
            </span>
        </button>
    </div>
    @endif

    <form wire:submit="save">
        @if ($isReadOnly)
            <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Questo lotto è in stato "{{ $readOnlyStatoLabel }}" e non può essere modificato.
            </div>
        @endif

        @error('lotto')
            <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $message }}
            </div>
        @enderror

        <fieldset @disabled($isReadOnly) class="{{ $isReadOnly ? 'space-y-6 opacity-70' : 'space-y-6' }}">
        <!-- Dati Principali -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dati Lotto Produzione</h3>
            </div>
            <div class="card-body space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Codice Lotto -->
                    <div>
                        <label for="codice_lotto" class="form-label">Codice Lotto</label>
                        <input wire:model="codice_lotto" type="text" id="codice_lotto"
                            class="form-input font-mono @error('codice_lotto') form-input-error @enderror"
                            placeholder="Generato automaticamente se vuoto" {{ $isEditing ? 'readonly' : '' }}>
                        <p class="text-xs text-muted-foreground mt-1">
                            Lascia vuoto per generazione automatica (es. LP-2026-0001)
                        </p>
                        @error('codice_lotto')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tipo Costruzione -->
                    <div>
                        <label for="costruzione_id" class="form-label">Tipo Costruzione *</label>
                        <select wire:model.live="costruzione_id" id="costruzione_id"
                            class="form-select @error('costruzione_id') form-input-error @enderror">
                            <option value="">Seleziona costruzione...</option>
                            @foreach ($costruzioni as $costruzione)
                            <option value="{{ $costruzione->id }}">{{ $costruzione->nome }}</option>
                            @endforeach
                        </select>
                        @error('costruzione_id')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="preventivo_associazione" class="form-label">
                            <span class="inline-flex items-center gap-1">
                                <span>Preventivo associato</span>
                                <x-help-tooltip text="Usa il preventivo quando il lotto nasce da offerta commerciale. Dopo la conversione in ordine il lotto può mantenere anche questo collegamento per tracciabilità storica." />
                            </span>
                        </label>
                        <select wire:model.live="preventivoId" id="preventivo_associazione"
                            class="form-select @error('preventivoId') form-input-error @enderror"
                            @disabled($returnTo === 'preventivo' && $preventivoId)>
                            <option value="">Nessuno</option>
                            @foreach ($preventiviDisponibili as $preventivoRef)
                                <option value="{{ $preventivoRef->id }}">
                                    {{ $preventivoRef->numero }} @if($preventivoRef->cliente) - {{ $preventivoRef->cliente->ragione_sociale }} @endif
                                </option>
                            @endforeach
                        </select>
                        @if($returnTo === 'preventivo' && $preventivoId)
                            <p class="text-xs text-muted-foreground mt-1">Precompilato dal flusso preventivo.</p>
                        @endif
                        @error('preventivoId')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="ordine_associazione" class="form-label">
                            <span class="inline-flex items-center gap-1">
                                <span>Ordine associato</span>
                                <x-help-tooltip text="Usa l'ordine quando il lotto è già in pianificazione o produzione. Se compili anche il preventivo, l'ordine deve provenire da quello stesso preventivo." />
                            </span>
                        </label>
                        <select wire:model.live="ordineId" id="ordine_associazione"
                            class="form-select @error('ordineId') form-input-error @enderror">
                            <option value="">Nessuno</option>
                            @foreach ($ordiniDisponibili as $ordineRef)
                                <option value="{{ $ordineRef->id }}">
                                    {{ $ordineRef->numero }} @if($ordineRef->cliente) - {{ $ordineRef->cliente->ragione_sociale }} @endif @if($ordineRef->stato) [{{ $ordineRef->stato->label() }}] @endif
                                </option>
                            @endforeach
                        </select>
                        @error('ordineId')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @error('associazione')
                    <p class="form-error">{{ $message }}</p>
                @enderror

                <!-- Prodotto Finale -->
                <div>
                    <label for="prodotto_finale" class="form-label">Prodotto Finale *</label>
                    <input wire:model="prodotto_finale" type="text" id="prodotto_finale"
                        class="form-input @error('prodotto_finale') form-input-error @enderror"
                        placeholder="es. Tavolo in legno massello 200x90">
                    @error('prodotto_finale')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Descrizione -->
                <div>
                    <label for="descrizione" class="form-label">Descrizione</label>
                    <textarea wire:model="descrizione" id="descrizione" rows="3"
                        class="form-input @error('descrizione') form-input-error @enderror"
                        placeholder="Dettagli sulla lavorazione, specifiche tecniche..."></textarea>
                    @error('descrizione')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stato -->
                @if ($isEditing && $canChangeStato)
                <div>
                    <label for="stato" class="form-label">Stato</label>
                    <select wire:model="stato" id="stato"
                        class="form-select @error('stato') form-input-error @enderror">
                        @foreach ($stati as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('stato')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                @elseif($isEditing)
                <div>
                    <label class="form-label">Stato</label>
                    @php
                    $statoEnum = \App\Enums\StatoLottoProduzione::from($stato);
                    $badgeClass = match ($statoEnum) {
                    \App\Enums\StatoLottoProduzione::BOZZA => 'badge-muted',
                    \App\Enums\StatoLottoProduzione::CONFERMATO => 'badge-info',
                    \App\Enums\StatoLottoProduzione::IN_LAVORAZIONE => 'badge-primary',
                    \App\Enums\StatoLottoProduzione::COMPLETATO => 'badge-success',
                    \App\Enums\StatoLottoProduzione::ANNULLATO => 'badge-destructive',
                    };
                    @endphp
                    <div class="mt-2">
                        <span class="badge {{ $badgeClass }}">{{ $statoEnum->label() }}</span>
                        <span class="text-sm text-muted-foreground ml-2">
                            (non modificabile in questo stato)
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @php
        $selectedCostruzione = $costruzioni->firstWhere('id', $costruzione_id);
        $selectedMateriale = $materiale_id ? $materiali->firstWhere('id', $materiale_id) : null;
        $materialeHaDimensioni = $selectedMateriale && $selectedMateriale->lunghezza_mm;
        $isAdmin = auth()->user()?->isAdmin() ?? false;
        $isExcelCassa = is_array($cassaVariant ?? null) && ($cassaVariant['uses_excel_builder'] ?? false);
        @endphp

        <!-- Parametri e Dimensioni (Visibile se costruzione selezionata) -->
        @if ($selectedCostruzione)
        <div class="card">
            <div class="card-header">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="card-title">Parametri e Dimensioni</h3>
                    @if ($isExcelCassa)
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900">
                            Routine {{ $cassaVariant['label'] ?? 'Excel' }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body space-y-4">
                <!-- Dimensioni cassa (cm) -->
                <div>
                    <label class="form-label font-semibold text-sm mb-2 block">Dimensioni Esterne (cm)</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if ($selectedCostruzione->richiede_lunghezza)
                        <div>
                            <label for="larghezza_cm" class="form-label text-xs">Larghezza / Lunghezza (cm)</label>
                            <input wire:model.live="larghezza_cm" type="number" step="0.01" id="larghezza_cm"
                                class="form-input @error('larghezza_cm') form-input-error @enderror"
                                placeholder="es. 120">
                            @error('larghezza_cm')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif

                        @if ($selectedCostruzione->richiede_larghezza)
                        <div>
                            <label for="profondita_cm" class="form-label text-xs">Profondità / Larghezza (cm)</label>
                            <input wire:model.live="profondita_cm" type="number" step="0.01" id="profondita_cm"
                                class="form-input @error('profondita_cm') form-input-error @enderror"
                                placeholder="es. 80">
                            @error('profondita_cm')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif

                        @if ($selectedCostruzione->richiede_altezza)
                        <div>
                            <label for="altezza_cm" class="form-label text-xs">Altezza (cm)</label>
                            <input wire:model.live="altezza_cm" type="number" step="0.01" id="altezza_cm"
                                class="form-input @error('altezza_cm') form-input-error @enderror" placeholder="es. 80">
                            @error('altezza_cm')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Materiale e Quantità -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="numero_pezzi" class="form-label">Quantità (Pezzi)</label>
                        <input wire:model.live="numero_pezzi" type="number" id="numero_pezzi"
                            class="form-input @error('numero_pezzi') form-input-error @enderror" placeholder="es. 100">
                        @error('numero_pezzi')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if (! $isExcelCassa)
                        <div>
                            <label for="materiale_id" class="form-label">
                                <span class="inline-flex items-center gap-1">
                                    <span>Materiale (Asse)</span>
                                    <x-help-tooltip text="Sono selezionabili solo i materiali con giacenza di magazzino maggiore di zero. In modifica resta visibile anche il materiale già associato al lotto." />
                                </span>
                            </label>
                            <select wire:model.live="materiale_id" id="materiale_id"
                                class="form-select @error('materiale_id') form-input-error @enderror">
                                <option value="">Seleziona materiale...</option>
                                @foreach ($materialiAsse as $materiale)
                                <option value="{{ $materiale->id }}">
                                    {{ $materiale->nome }}
                                    @if ($materiale->lunghezza_mm)
                                    ({{ $materiale->lunghezza_mm }}mm)
                                    @endif
                                    @if ($materiale->spessore_mm)
                                    sp. {{ $materiale->spessore_mm }}mm
                                    @endif
                                </option>
                                @endforeach
                            </select>
                            @error('materiale_id')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                            @if ($materialiAsse->isEmpty())
                            <p class="text-xs text-muted-foreground mt-1">
                                Nessun materiale con giacenza disponibile per questo tipo di calcolo.
                            </p>
                            @endif
                            @if ($selectedMateriale && !$materialeHaDimensioni)
                            <p class="text-xs text-red-600 mt-1 font-medium">
                                Attenzione, il materiale selezionato non ha misure.
                                @if($isAdmin)
                                    <a href="{{ route('prodotti.show', $selectedMateriale->id) }}"
                                        class="underline hover:text-red-800" target="_blank">
                                        Modificare qui
                                    </a>
                                @endif
                            </p>
                            @endif
                        </div>
                    @else
                        <div class="md:col-span-2 rounded-lg border border-amber-200 bg-amber-50/40 p-4">
                            <div class="mb-3">
                                <h4 class="text-sm font-semibold text-amber-900">Materiali cassa</h4>
                                <p class="text-xs text-amber-800">
                                    Questa routine usa profili materiali distinti: il calcolo fisico rispetta spessore e larghezza reali delle assi.
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach (($cassaVariant['required_profiles'] ?? []) as $profile)
                                    @php
                                        $profileKey = $profile['key'] ?? 'base';
                                        $options = $materialiCassaPerProfilo[$profileKey] ?? collect();
                                    @endphp
                                    <div>
                                        <label for="primary-material-{{ $profileKey }}" class="form-label">
                                            {{ $profile['label'] ?? ucfirst($profileKey) }}
                                        </label>
                                        <select
                                            wire:model.live="primaryMaterialProfiles.{{ $profileKey }}"
                                            id="primary-material-{{ $profileKey }}"
                                            class="form-select @error('primaryMaterialProfiles.' . $profileKey) form-input-error @enderror"
                                        >
                                            <option value="">Seleziona materiale...</option>
                                            @foreach ($options as $materiale)
                                                <option value="{{ $materiale->id }}">
                                                    {{ $materiale->nome }}
                                                    @if ($materiale->lunghezza_mm)
                                                        ({{ $materiale->lunghezza_mm }}mm)
                                                    @endif
                                                    @if ($materiale->spessore_mm)
                                                        sp. {{ $materiale->spessore_mm }}mm
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('primaryMaterialProfiles.' . $profileKey)
                                            <p class="form-error">{{ $message }}</p>
                                        @enderror
                                        @if ($options->isEmpty())
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                Nessun materiale compatibile con il profilo richiesto.
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="numero_univoco" class="form-label">Numero Univoco</label>
                        <input wire:model="numero_univoco" type="text" id="numero_univoco"
                            class="form-input font-mono @error('numero_univoco') form-input-error @enderror"
                            placeholder="Generato automaticamente" maxlength="10">
                        @error('numero_univoco')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if ($materialeHaDimensioni)
                <div class="pt-4 space-y-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="controllaScarti" class="form-checkbox">
                        <span class="inline-flex items-center gap-1">
                            <span>Controlla scarti compatibili</span>
                            <x-help-tooltip text="Se attivo, il sistema verifica prima se negli scarti riutilizzabili sono presenti pezzi compatibili e propone il riuso." />
                        </span>
                    </label>

                    <div class="flex justify-end">
                        <button type="button" wire:click="calcolaMateriali" class="btn-primary"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="calcolaMateriali">Calcola Ottimizzazione</span>
                            <span wire:loading wire:target="calcolaMateriali">
                                <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Calcolo in corso...
                            </span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if (!empty($componentiManuali))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Componenti Manuali</h3>
            </div>
            <div class="card-body">
                <p class="text-sm text-muted-foreground mb-4">
                    Questi componenti non sono calcolati automaticamente: seleziona materiale e quantita per questo
                    lotto.
                </p>
                <div class="overflow-x-auto">
                    <table class="table table-compact">
                        <thead>
                            <tr>
                                <th>Componente</th>
                                <th>Materiale</th>
                                <th class="w-32">Quantita</th>
                                <th class="w-40">Prezzo Unitario</th>
                                <th class="w-28">U.M.</th>
                                <th class="w-40 text-right">Totale Riga</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($componentiManuali as $index => $componenteManuale)
                            @php
                            $dettaglioComponente = $componentiManualiDettaglio[$index] ?? null;
                            @endphp
                            <tr wire:key="comp-manuale-{{ $componenteManuale['componente_costruzione_id'] }}">
                                <td class="font-medium">
                                    {{ $componenteManuale['nome'] }}
                                    <input type="hidden"
                                        wire:model="componentiManuali.{{ $index }}.componente_costruzione_id">
                                </td>
                                <td>
                                    <select wire:model.live="componentiManuali.{{ $index }}.prodotto_id"
                                        class="form-select @error('componentiManuali.'.$index.'.prodotto_id') form-input-error @enderror">
                                        <option value="">Seleziona materiale...</option>
                                        @foreach ($materiali as $materiale)
                                        <option value="{{ $materiale->id }}">{{ $materiale->nome }}</option>
                                        @endforeach
                                    </select>
                                    @error('componentiManuali.'.$index.'.prodotto_id')
                                    <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number" step="0.0001" min="0"
                                        wire:model.live="componentiManuali.{{ $index }}.quantita"
                                        class="form-input @error('componentiManuali.'.$index.'.quantita') form-input-error @enderror">
                                    @error('componentiManuali.'.$index.'.quantita')
                                    <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number" step="0.0001" min="0"
                                        wire:model.live="componentiManuali.{{ $index }}.prezzo_unitario"
                                        class="form-input @error('componentiManuali.'.$index.'.prezzo_unitario') form-input-error @enderror"
                                        placeholder="Auto da materiale">
                                    @error('componentiManuali.'.$index.'.prezzo_unitario')
                                    <p class="form-error">{{ $message }}</p>
                                    @enderror
                                    @if ($dettaglioComponente)
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Effettivo: € {{ number_format($dettaglioComponente['prezzo_unitario_effettivo'],
                                        4) }}
                                        ({{ $dettaglioComponente['sorgente_prezzo'] === 'manuale' ? 'manuale' :
                                        'listino' }})
                                    </p>
                                    @endif
                                </td>
                                <td>
                                    <select wire:model.live="componentiManuali.{{ $index }}.unita_misura"
                                        class="form-select @error('componentiManuali.'.$index.'.unita_misura') form-input-error @enderror">
                                        @foreach ($unitaMisura as $unita)
                                        <option value="{{ $unita->value }}">{{ $unita->abbreviation() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-right font-mono">
                                    € {{ number_format($dettaglioComponente['totale_riga'] ?? 0, 2) }}
                                </td>
                                <td>
                                    <input type="text" wire:model="componentiManuali.{{ $index }}.note"
                                        class="form-input @error('componentiManuali.'.$index.'.note') form-input-error @enderror"
                                        placeholder="Note componente...">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if (session()->has('optimizer-success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            {{ session('optimizer-success') }}
        </div>
        @endif

        @if (session()->has('optimizer-error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ session('optimizer-error') }}
        </div>
        @endif

        @if (session()->has('optimizer-warning'))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded">
            <p class="font-medium">{{ session('optimizer-warning') }}</p>
        </div>
        @endif

        @if ($scartiCompatibiliPreview && ($scartiCompatibiliPreview['matched_count'] ?? 0) > 0)
        <div class="card border-emerald-200 bg-emerald-50/40">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Scarti compatibili rilevati</h3>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ $scartiCompatibiliPreview['matched_count'] ?? 0 }} pezzi coperti dagli scarti su
                        {{ $scartiCompatibiliPreview['required_count'] ?? 0 }} richiesti.
                        Disponibili in archivio: {{ $scartiCompatibiliPreview['available_scraps_count'] ?? 0 }}.
                    </p>
                </div>
            </div>
            <div class="card-body space-y-4">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    @if (data_get($scartiCompatibiliPreview, 'used'))
                        <span class="badge badge-success">Riutilizzo applicato al calcolo</span>
                    @else
                        <span class="badge badge-warning">Riutilizzo escluso da questo calcolo</span>
                    @endif
                    <span class="text-muted-foreground">
                        Il sistema tiene traccia del lotto materiale di origine e del lotto che ha generato lo scarto.
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-compact w-full">
                        <thead>
                            <tr>
                                <th>Pezzo richiesto</th>
                                <th>Scarto usato</th>
                                <th>Lotto materiale</th>
                                <th>Lotto origine</th>
                                <th>Residuo</th>
                                <th class="text-right">Volume</th>
                                <th class="text-right">Peso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (data_get($scartiCompatibiliPreview, 'matches', []) as $match)
                                <tr wire:key="scrap-preview-{{ $match['scrap_id'] }}-{{ $match['piece_index'] }}">
                                    <td>
                                        <div class="font-medium">{{ $match['piece_label'] }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            Richiesto:
                                            {{ number_format((float) ($match['required_length_mm'] ?? 0), 0, ',', '.') }} x
                                            {{ number_format((float) ($match['required_width_mm'] ?? 0), 0, ',', '.') }} mm
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-medium">#{{ $match['scrap_id'] }}</div>
                                        <div class="text-xs text-muted-foreground">{{ $match['dimensioni_label'] }}</div>
                                    </td>
                                    <td>{{ $match['source_lotto_materiale_code'] ?? '-' }}</td>
                                    <td>{{ $match['source_lotto_produzione_code'] ?? 'Scarto non generato da lotto' }}</td>
                                    <td>
                                        @if (($match['remaining_length_mm'] ?? 0) > 0)
                                            <div class="font-medium">
                                                {{ number_format((float) ($match['remaining_length_mm'] ?? 0), 0, ',', '.') }} mm
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ (($match['remaining_riutilizzabile'] ?? false) ? 'Riutilizzabile' : 'Sotto soglia riuso') }}
                                            </div>
                                        @else
                                            <span class="text-muted-foreground">Nessuno</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format((float) ($match['volume_mc'] ?? 0), 4, ',', '.') }} m³</td>
                                    <td class="text-right">
                                        @if (($match['peso_kg'] ?? 0) > 0)
                                            {{ number_format((float) ($match['peso_kg'] ?? 0), 3, ',', '.') }} kg
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="usaScartiCompatibiliERicalcola" class="btn-primary btn-sm">
                        Applica/Ricalcola con scarti
                    </button>
                    <button type="button" wire:click="ignoraScartiCompatibiliERicalcola" class="btn-secondary btn-sm">
                        Ricalcola senza scarti
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Optimizer Results -->
        @if ($showOptimizerResults && $optimizerResult && isset($optimizerResult['bins']))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
            <h4 class="font-semibold text-blue-900 mb-3">
                Risultato Ottimizzazione ({{ data_get($optimizerResult, 'optimizer.name', 'legacy-bin-packing') }})
            </h4>

            <!-- Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 p-3 bg-white rounded">
                <div>
                    <span class="text-blue-600 block text-sm">Materiale:</span>
                    <p class="font-bold text-blue-900">{{ $optimizerResult['materiale']['nome'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Assi Necessarie:</span>
                    <p class="font-bold text-lg text-blue-900">{{ data_get($optimizerResult, 'total_bins', count($optimizerResult['bins'] ?? [])) }}</p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Scarto Totale:</span>
                    <p class="font-bold text-lg text-blue-900">
                        {{ number_format((float) data_get($optimizerResult, 'total_waste', 0) / 1000, 2) }} m
                    </p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Scarto %:</span>
                    <p
                        class="font-bold text-lg {{ (float) data_get($optimizerResult, 'total_waste_percent', 0) < 10 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format((float) data_get($optimizerResult, 'total_waste_percent', 0), 2) }}%
                    </p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Volume Lordo:</span>
                    <p class="font-bold text-lg text-blue-900">
                        {{ number_format(data_get($optimizerResult, 'totali.volume_lordo_mc', data_get($optimizerResult, 'totali.volume_totale_mc', 0)), 4) }} m³
                    </p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Volume Netto:</span>
                    <p class="font-bold text-lg text-blue-900">
                        @if (data_get($optimizerResult, 'totali.volume_netto_mc') !== null)
                        {{ number_format((float) data_get($optimizerResult, 'totali.volume_netto_mc', 0), 4) }} m³
                        @else
                        -
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Volume Scarto:</span>
                    <p class="font-bold text-lg text-blue-900">
                        @if (data_get($optimizerResult, 'totali.volume_scarto_mc') !== null)
                        {{ number_format((float) data_get($optimizerResult, 'totali.volume_scarto_mc', 0), 4) }} m³
                        @else
                        -
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Costo Stimato:</span>
                    <p class="font-bold text-lg text-blue-900">
                        € {{ number_format($optimizerResult['totali']['costo_totale'] ?? 0, 2) }}
                    </p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Prezzo Vendita:</span>
                    <p class="font-bold text-lg text-green-700">
                        € {{ number_format($optimizerResult['totali']['prezzo_totale'] ?? 0, 2) }}
                    </p>
                </div>
                <div>
                    <span class="text-blue-600 block text-sm">Preview FITOK:</span>
                    <p class="font-bold text-sm text-blue-900">
                        {{ data_get($optimizerResult, 'fitok_preview.label', 'In attesa calcolo FITOK') }}
                    </p>
                </div>
            </div>

            @if (auth()->user()?->isAdmin())
            <div class="mb-4 flex flex-col gap-3 rounded border border-blue-200 bg-white p-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="toggleAllOptimizerBinsSelection" class="btn-secondary btn-sm">
                        {{ count($selectedOptimizerBins) === count($optimizerResult['bins']) ? 'Deseleziona tutte' : 'Seleziona tutte' }}
                    </button>
                    <button type="button" wire:click="openSubstitutionModal" class="btn-primary btn-sm"
                        @disabled(count($selectedOptimizerBins) === 0)>
                        Sostituisci materiale
                    </button>
                </div>
                <p class="text-sm text-blue-800">
                    Assi selezionate: <strong>{{ count($selectedOptimizerBins) }}</strong>
                </p>
            </div>
            @endif

            <!-- Bins Visualization -->
            <div class="space-y-4">
                @foreach (array_slice($optimizerResult['bins'], 0, 20) as $index => $bin)
                @php
                    $isSelectedBin = in_array($index, $selectedOptimizerBins, true);
                    $binCapacity = (float) ($bin['capacity'] ?? $optimizerResult['bin_length'] ?? 0);
                    $binMaterialName = data_get($bin, 'source_material.nome', data_get($optimizerResult, 'materiale.nome', 'N/A'));
                    $binSourceType = data_get($bin, 'source_type', 'primary');
                @endphp
                <div class="bg-white p-3 rounded border {{ $isSelectedBin ? 'border-blue-500 ring-2 ring-blue-200' : 'border-gray-200' }}">
                    <div class="flex flex-col gap-2 text-xs mb-1 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-start gap-3">
                            @if (auth()->user()?->isAdmin())
                            <label class="mt-0.5 inline-flex items-center">
                                <input
                                    type="checkbox"
                                    class="form-checkbox h-4 w-4"
                                    wire:click="toggleOptimizerBinSelection({{ $index }})"
                                    @checked($isSelectedBin)
                                >
                            </label>
                            @endif
                            <div>
                                <span class="font-semibold">Asse #{{ $index + 1 }}</span>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-900">
                                        {{ $binMaterialName }}
                                    </span>
                                    @if ($binSourceType === 'substituted')
                                    <span class="inline-flex rounded bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-900">
                                        Sostituita
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <span class="text-gray-500">
                            Utilizzato: {{ number_format($bin['used_length']) }}mm
                            / Scarto: {{ number_format($bin['waste']) }}mm
                            ({{ $bin['waste_percent'] }}%)
                        </span>
                    </div>

                    <!-- Bar representation -->
                    <div class="w-full h-6 bg-gray-200 rounded flex overflow-hidden">
                        @foreach ($bin['items'] as $item)
                        @php
                        $widthPercent = $binCapacity > 0 ? (($item['length'] / $binCapacity) * 100) : 0;
                        // Generate consistent color based on item ID/desc
                        $hue = crc32($item['description']) % 360;
                        $color = "hsl($hue, 70%, 80%)";
                        @endphp
                        <div class="h-full flex items-center justify-center text-[10px] text-gray-800 border-r border-white/50 relative group"
                            style="width: {{ $widthPercent }}%; background-color: {{ $color }};"
                            title="{{ $item['description'] }} ({{ $item['length'] }}mm)">
                            <span class="truncate px-1">{{ $item['length'] }}</span>
                        </div>
                        <!-- Kerf -->
                        <div class="h-full bg-red-400 w-[1px]" title="Taglio"></div>
                        @endforeach
                        <!-- Waste -->
                        <div class="h-full bg-gray-300 flex-1 relative" title="Scarto">
                            <span
                                class="absolute inset-0 flex items-center justify-center text-[10px] text-gray-500 opacity-0 hover:opacity-100">
                                Scarto
                            </span>
                        </div>
                    </div>

                    <!-- Legend/List for this bin -->
                    <div class="mt-2 text-xs text-gray-600">
                        @foreach (collect($bin['items'])->groupBy('description') as $desc => $items)
                        <span class="inline-block bg-gray-100 px-2 py-1 rounded mr-2 mb-1">
                            {{ count($items) }}x {{ $desc }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endforeach

                @if (count($optimizerResult['bins']) > 20)
                <div class="text-center text-sm text-gray-500 italic">
                    ... e altri {{ count($optimizerResult['bins']) - 20 }} assi.
                </div>
                @endif
            </div>

            <div class="mt-4 flex justify-end items-center">
                <p class="text-sm text-muted-foreground mr-auto flex items-center gap-1">
                    <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    Il piano di taglio verrà salvato automaticamente al salvataggio del lotto.
                </p>
                <button type="button" wire:click="salvaMateriali" class="btn-success">
                    Salva subito il piano di taglio
                </button>
            </div>
        </div>

        @if ($showSubstitutionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl">
                <div class="border-b border-gray-200 px-4 py-3">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-900">Sostituisci materiale</h4>
                            <p class="text-sm text-slate-600">
                                Assi selezionate: {{ count($selectedOptimizerBins) }}
                            </p>
                        </div>
                        <button type="button" wire:click="closeSubstitutionModal" class="btn-secondary btn-sm">
                            Chiudi
                        </button>
                    </div>
                </div>

                <div class="space-y-4 px-4 py-4">
                    <div>
                        <label class="form-label">Materiale sostitutivo</label>
                        <select wire:model.live="substitutionMaterialId" class="form-select w-full">
                            <option value="">Seleziona materiale</option>
                            @foreach ($materialiSostituzioneCompatibili as $materialeCompatibile)
                                <option value="{{ $materialeCompatibile->id }}">
                                    {{ $materialeCompatibile->nome }}
                                    @if($materialeCompatibile->lunghezza_mm && $materialeCompatibile->larghezza_mm && $materialeCompatibile->spessore_mm)
                                        ({{ number_format((float) $materialeCompatibile->lunghezza_mm, 0, ',', '.') }} x
                                        {{ number_format((float) $materialeCompatibile->larghezza_mm, 0, ',', '.') }} x
                                        {{ number_format((float) $materialeCompatibile->spessore_mm, 0, ',', '.') }} mm)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (($substitutionPreview['error'] ?? null) !== null)
                        <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                            {{ $substitutionPreview['error'] }}
                        </div>
                    @elseif (is_array($substitutionPreview['payload'] ?? null))
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div class="rounded border border-slate-200 bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-wide text-slate-500">Assi</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ data_get($substitutionPreview, 'payload.total_bins', 0) }}
                                </div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-wide text-slate-500">Scarto %</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ number_format((float) data_get($substitutionPreview, 'payload.total_waste_percent', 0), 2) }}%
                                </div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-wide text-slate-500">Costo</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    € {{ number_format((float) data_get($substitutionPreview, 'payload.totali.costo_totale', 0), 2) }}
                                </div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 p-3">
                                <div class="text-xs uppercase tracking-wide text-slate-500">FITOK</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ data_get($substitutionPreview, 'payload.fitok_preview.label', 'In attesa calcolo FITOK') }}
                                </div>
                            </div>
                        </div>

                        <div class="rounded border border-slate-200">
                            <div class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900">
                                Disponibilità materiali del piano dopo la sostituzione
                            </div>
                            <div class="overflow-x-auto">
                                <table class="table table-compact w-full">
                                    <thead>
                                        <tr>
                                            <th>Materiale</th>
                                            <th class="text-right">Richiesto</th>
                                            <th class="text-right">Disponibile</th>
                                            <th class="text-right">Esito</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (data_get($substitutionPreview, 'availability', []) as $row)
                                            <tr>
                                                <td>{{ $row['material_name'] }}</td>
                                                <td class="text-right">
                                                    {{ number_format((float) $row['required_qty'], 4, ',', '.') }} {{ $row['uom_label'] }}
                                                </td>
                                                <td class="text-right">
                                                    {{ number_format((float) $row['available_qty'], 4, ',', '.') }} {{ $row['uom_label'] }}
                                                </td>
                                                <td class="text-right">
                                                    @if ($row['enough'])
                                                        <span class="badge badge-success">OK</span>
                                                    @else
                                                        <span class="badge badge-danger">Insufficiente</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-2 border-t border-gray-200 px-4 py-3 md:flex-row md:items-center md:justify-end">
                    <button type="button" wire:click="closeSubstitutionModal" class="btn-secondary">
                        Annulla
                    </button>
                    <button
                        type="button"
                        wire:click="applySubstitution"
                        class="btn-primary"
                        @disabled(! is_array($substitutionPreview['payload'] ?? null) || ! (bool) data_get($substitutionPreview, 'all_available', false))
                    >
                        Applica sostituzione
                    </button>
                </div>
            </div>
        </div>
        @endif
        @endif

        @php
            $canViewOptimizerDebug = auth()->user()?->isAdmin() ?? false;
            $optimizerTrace = (array) data_get($optimizerResult ?? [], 'trace', []);
            $optimizerTraceJson = json_encode(
                $optimizerTrace,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $settingsSnapshot = data_get($optimizerResult ?? [], 'trace.settings_snapshot', []);
            $debugBins = is_array(data_get($optimizerResult ?? [], 'bins')) ? data_get($optimizerResult, 'bins') : [];
            $debugComponentSummary = is_array(data_get($optimizerResult ?? [], 'trace.component_summary'))
                ? data_get($optimizerResult, 'trace.component_summary')
                : [];
        @endphp
        @if ($canViewOptimizerDebug && $optimizerResult)
            <div class="card border-amber-200 bg-amber-50/30">
                <div class="card-header flex items-center justify-between gap-3">
                    <div>
                        <h3 class="card-title">Debug Optimizer (admin)</h3>
                        <p class="text-xs text-muted-foreground mt-1">
                            Dati tecnici per audit `calculation_trace` e troubleshooting del piano di taglio.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleOptimizerDebugPanel"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        {{ $showOptimizerDebugPanel ? 'Nascondi dettagli' : 'Mostra dettagli' }}
                    </button>
                </div>
                @if ($showOptimizerDebugPanel)
                    <div class="card-body space-y-4">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3 text-sm">
                            <div>
                                <span class="text-muted-foreground block">Payload version</span>
                                <span class="font-mono">{{ data_get($optimizerResult, 'version', '-') }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block">Optimizer</span>
                                <span class="font-mono">
                                    {{ data_get($optimizerResult, 'optimizer.name', '-') }}
                                    / {{ data_get($optimizerResult, 'optimizer.version', '-') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block">Audit timestamp</span>
                                <span class="font-mono">{{ data_get($optimizerResult, 'trace.audit.logical_timestamp', '-') }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block">Routine</span>
                                <span class="font-mono">{{ data_get($optimizerResult, 'trace.variant_routine', '-') }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block">Optimizer mode</span>
                                <span class="font-mono">{{ data_get($optimizerResult, 'trace.optimizer_mode', '-') }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block">Piece source</span>
                                <span class="font-mono">{{ data_get($optimizerResult, 'trace.piece_source', '-') }}</span>
                            </div>
                        </div>

                        @if (is_array($settingsSnapshot) && count($settingsSnapshot) > 0)
                            <details class="rounded border border-amber-200 bg-white p-3">
                                <summary class="cursor-pointer text-sm font-medium text-amber-900">
                                    Settings snapshot applicati
                                </summary>
                                <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                                    @foreach ($settingsSnapshot as $key => $value)
                                        @php
                                            $displayValue = is_scalar($value) || $value === null
                                                ? $value
                                                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                        @endphp
                                        <div class="rounded bg-amber-50 px-3 py-2">
                                            <div class="text-xs text-amber-700">{{ $key }}</div>
                                            <div class="font-mono text-xs text-amber-900">{{ $displayValue }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif

                        <details class="rounded border border-amber-200 bg-white p-3" open>
                            <summary class="cursor-pointer text-sm font-medium text-amber-900">
                                calculation_trace
                            </summary>
                            <pre
                                class="mt-3 max-h-96 overflow-auto rounded bg-slate-950 p-3 text-xs text-slate-100">{{ $optimizerTraceJson ?: '{}' }}</pre>
                        </details>

                        <details class="rounded border border-amber-200 bg-white p-3">
                            <summary class="cursor-pointer text-sm font-medium text-amber-900">
                                Debug componenti -> assi
                            </summary>
                            @if ($debugComponentSummary === [])
                                <p class="mt-3 text-sm text-muted-foreground">
                                    Nessun dettaglio componente disponibile nel payload corrente.
                                </p>
                            @else
                                <div class="mt-3 overflow-x-auto">
                                    <table class="table table-compact w-full">
                                        <thead>
                                            <tr>
                                                <th>Componente</th>
                                                <th class="text-right">Richiesti</th>
                                                <th class="text-right">Prodotti</th>
                                                <th class="text-right">Scarto allocato (mm)</th>
                                                <th>Assegnazione assi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($debugComponentSummary as $componentRow)
                                                <tr>
                                                    <td class="font-medium">{{ $componentRow['description'] ?? 'Componente' }}</td>
                                                    <td class="text-right">{{ (int) ($componentRow['requested_strips'] ?? 0) }}</td>
                                                    <td class="text-right">{{ (int) ($componentRow['produced_strips'] ?? 0) }}</td>
                                                    <td class="text-right">{{ number_format((float) ($componentRow['allocated_waste_mm'] ?? 0), 2) }}</td>
                                                    <td>
                                                        @php
                                                            $assignedBins = is_array($componentRow['assigned_bins'] ?? null)
                                                                ? $componentRow['assigned_bins']
                                                                : [];
                                                        @endphp
                                                        @if ($assignedBins === [])
                                                            <span class="text-xs text-muted-foreground">Nessuna assegnazione</span>
                                                        @else
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach ($assignedBins as $binRow)
                                                                    <span class="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-900">
                                                                        Asse #{{ (int) ($binRow['board_number'] ?? 0) }}
                                                                        ({{ (int) ($binRow['strips'] ?? 0) }} pezzi)
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </details>

                        <details class="rounded border border-amber-200 bg-white p-3">
                            <summary class="cursor-pointer text-sm font-medium text-amber-900">
                                Debug piano di taglio
                            </summary>
                            @if ($debugBins === [])
                                <p class="mt-3 text-sm text-muted-foreground">
                                    Nessun bin disponibile nel payload corrente.
                                </p>
                            @else
                                <div class="mt-3 overflow-x-auto">
                                    <table class="table table-compact w-full">
                                        <thead>
                                            <tr>
                                                <th># Asse</th>
                                                <th class="text-right">Usato (mm)</th>
                                                <th class="text-right">Scarto (mm)</th>
                                                <th class="text-right">Scarto %</th>
                                                <th>Pezzi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($debugBins as $index => $bin)
                                                <tr>
                                                    <td class="font-medium">Asse #{{ $index + 1 }}</td>
                                                    <td class="text-right">{{ number_format((float) ($bin['used_length'] ?? 0), 2) }}</td>
                                                    <td class="text-right">{{ number_format((float) ($bin['waste'] ?? 0), 2) }}</td>
                                                    <td class="text-right">{{ number_format((float) ($bin['waste_percent'] ?? 0), 2) }}%</td>
                                                    <td>
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach (($bin['items'] ?? []) as $item)
                                                                @php
                                                                    $lengthMm = (float) ($item['length'] ?? 0);
                                                                    $widthMm = (float) ($item['width'] ?? 0);
                                                                    $dimensionsLabel = $widthMm > 0
                                                                        ? number_format($lengthMm, 2) . 'x' . number_format($widthMm, 2) . 'mm'
                                                                        : number_format($lengthMm, 2) . 'mm';
                                                                @endphp
                                                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs">
                                                                    {{ ($item['description'] ?? 'pezzo') . ' (' . $dimensionsLabel . ')' }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </details>
                    </div>
                @endif
            </div>
        @endif

        <!-- Materiali Salvati (solo in modifica) -->
        @if ($isEditing && $lotto && $lotto->materialiUsati->count() > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Materiali Calcolati</h3>
            </div>
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Descrizione</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lung.
                                    (mm)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Quantità</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Pz/Asse</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Assi
                                    Nec.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Scarto
                                    %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($lotto->materialiUsati as $materiale)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $materiale->ordine }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $materiale->descrizione }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    {{ number_format($materiale->lunghezza_mm, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    {{ $materiale->quantita_pezzi }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    {{ $materiale->pezzi_per_asse ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    {{ $materiale->assi_necessarie ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    @if ($materiale->scarto_percentuale)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                    {{ $materiale->scarto_percentuale < 10 ? 'bg-green-100 text-green-800' : ($materiale->scarto_percentuale < 20 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($materiale->scarto_percentuale, 2) }}%
                                    </span>
                                    @else
                                    -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if ($isEditing && count($scartiRiutilizzati) > 0)
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Scarti riutilizzati in questo lotto</h3>
                    <p class="text-sm text-muted-foreground mt-1">
                        Elenco degli scarti effettivamente attribuiti a questo lotto in fase di ottimizzazione/salvataggio.
                    </p>
                </div>
            </div>
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table table-compact w-full">
                        <thead>
                            <tr>
                                <th>Scarto</th>
                                <th>Materiale</th>
                                <th>Lotto materiale</th>
                                <th>Lotto origine</th>
                                <th>Dimensioni</th>
                                <th>Residuo</th>
                                <th class="text-right">Volume</th>
                                <th class="text-right">Peso</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scartiRiutilizzati as $scartoRiutilizzato)
                                <tr wire:key="saved-scarto-{{ $scartoRiutilizzato['scrap_id'] }}">
                                    <td class="font-medium">#{{ $scartoRiutilizzato['scrap_id'] }}</td>
                                    <td>{{ $scartoRiutilizzato['materiale_nome'] }}</td>
                                    <td>{{ $scartoRiutilizzato['source_lotto_materiale_code'] ?? '-' }}</td>
                                    <td>{{ $scartoRiutilizzato['source_lotto_produzione_code'] ?? 'Scarto manuale' }}</td>
                                    <td>{{ $scartoRiutilizzato['dimensioni_label'] }}</td>
                                    <td>
                                        @if (($scartoRiutilizzato['remaining_length_mm'] ?? 0) > 0)
                                            <div class="font-medium">
                                                {{ number_format((float) ($scartoRiutilizzato['remaining_length_mm'] ?? 0), 0, ',', '.') }} mm
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ number_format((float) ($scartoRiutilizzato['remaining_volume_mc'] ?? 0), 4, ',', '.') }} m³
                                            </div>
                                        @else
                                            <span class="text-muted-foreground">Nessuno</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format((float) ($scartoRiutilizzato['volume_mc'] ?? 0), 4, ',', '.') }} m³</td>
                                    <td class="text-right">
                                        @if (($scartoRiutilizzato['peso_kg'] ?? 0) > 0)
                                            {{ number_format((float) ($scartoRiutilizzato['peso_kg'] ?? 0), 3, ',', '.') }} kg
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $scartoRiutilizzato['note'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pricing Lotto</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="pricing_mode" class="form-label">Modalità Calcolo</label>
                        <select wire:model.live="pricing_mode" id="pricing_mode"
                            class="form-select @error('pricing_mode') form-input-error @enderror">
                            @foreach ($pricingModes as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                            @endforeach
                        </select>
                        @error('pricing_mode')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        @if ($pricing_mode === \App\Enums\LottoPricingMode::TARIFFA_MC->value)
                        <label for="tariffa_mc" class="form-label">Tariffa €/m³</label>
                        <input wire:model.live="tariffa_mc" type="number" step="0.01" min="0" id="tariffa_mc"
                            class="form-input @error('tariffa_mc') form-input-error @enderror" placeholder="es. 250">
                        @error('tariffa_mc')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                        @else
                        <label for="ricarico_percentuale" class="form-label">Ricarico %</label>
                        <input wire:model.live="ricarico_percentuale" type="number" step="0.01" min="0"
                            id="ricarico_percentuale"
                            class="form-input @error('ricarico_percentuale') form-input-error @enderror"
                            placeholder="es. 25">
                        @error('ricarico_percentuale')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                        @endif
                    </div>

                    <div>
                        <label for="prezzo_finale_override" class="form-label">Prezzo Finale Manuale</label>
                        <input wire:model.live="prezzo_finale_override" type="number" step="0.01" min="0"
                            id="prezzo_finale_override"
                            class="form-input @error('prezzo_finale_override') form-input-error @enderror"
                            placeholder="Lascia vuoto per automatico">
                        @error('prezzo_finale_override')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Prezzo Calcolato</div>
                        <div class="text-2xl font-bold">€ {{ number_format($prezzo_calcolato, 2) }}</div>
                        @if ($pricing_mode === \App\Enums\LottoPricingMode::TARIFFA_MC->value)
                            @if ($pricingVolumeReady)
                                <p class="text-xs text-muted-foreground mt-1">
                                    Volume {{ number_format($volume_totale_mc, 4) }} m³
                                </p>
                                @if ($pricingFallbackActive)
                                <p class="text-xs text-amber-600 mt-1">
                                    Prezzo derivato automaticamente dal listino materiali finché non imposti una tariffa €/m³.
                                </p>
                                @endif
                            @else
                                <p class="text-xs text-muted-foreground mt-1">
                                    Volume pricing disponibile dopo il salvataggio del piano di taglio.
                                </p>
                            @endif
                        @else
                        <p class="text-xs text-muted-foreground mt-1">
                            Costo base {{ $materialCostState['display'] }}
                        </p>
                        @if (!$materialCostState['available'] && $materialCostState['message'])
                        <p class="text-xs text-amber-600 mt-1">{{ $materialCostState['message'] }}</p>
                        @endif
                        <p class="text-xs text-muted-foreground">Materiali salvati + componenti manuali</p>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Prezzo Finale</div>
                        <div class="text-2xl font-bold text-primary">€ {{ number_format($prezzo_finale, 2) }}</div>
                    </div>
                </div>

                @if (!empty($componentiManuali))
                <div class="mt-4 border-t border-border pt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-muted-foreground">Materiali calcolati</div>
                        <div class="font-semibold">
                            € {{ number_format(max(0, $prezzo_vendita_totale - $totale_componenti_manuali_prezzo), 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Componenti manuali</div>
                        <div class="font-semibold">€ {{ number_format($totale_componenti_manuali_prezzo, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Totale componenti e materiali</div>
                        <div class="font-semibold">€ {{ number_format($prezzo_vendita_totale, 2) }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Sezione riepilogo costi -->
        @if ($isEditing && $lotto && $hasSavedCuttingPlan)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Totali e Costi</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                    <div>
                        <div class="text-sm text-muted-foreground">Volume Totale</div>
                        <div class="text-2xl font-bold">{{ number_format($volume_totale_mc, 4) }} m³</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Costo Materiali</div>
                        <div class="text-2xl font-bold">{{ $materialCostState['display'] }}</div>
                        @if (!$materialCostState['available'] && $materialCostState['message'])
                        <p class="text-xs text-amber-600 mt-1">{{ $materialCostState['message'] }}</p>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Prezzo Vendita</div>
                        <div class="text-2xl font-bold text-primary">€
                            {{ number_format($prezzo_vendita_totale, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Prezzo Calcolato</div>
                        <div class="text-2xl font-bold">€ {{ number_format($prezzo_calcolato, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Prezzo Finale</div>
                        <div class="text-2xl font-bold text-primary">€ {{ number_format($prezzo_finale, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Scarto Totale</div>
                        <div class="text-2xl font-bold">
                            <span
                                class="inline-flex items-center
                                    {{ $scarto_totale_percentuale < 10 ? 'text-green-600' : ($scarto_totale_percentuale < 20 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($scarto_totale_percentuale, 2) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Info Aggiuntive (solo in modifica) -->
        @if ($isEditing && $lotto)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informazioni</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-muted-foreground">Creato il:</span>
                        <p class="font-medium">{{ $lotto->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Creato da:</span>
                        <p class="font-medium">{{ $lotto->createdBy?->name ?? '-' }}</p>
                    </div>
                    @if ($lotto->data_inizio)
                    <div>
                        <span class="text-muted-foreground">Inizio lavorazione:</span>
                        <p class="font-medium">{{ optional($lotto->avviato_at)->format('d/m/Y H:i') ?? $lotto->data_inizio->format('d/m/Y') }}</p>
                    </div>
                    @endif
                    @if ($lotto->data_fine)
                    <div>
                        <span class="text-muted-foreground">Fine lavorazione:</span>
                        <p class="font-medium">{{ optional($lotto->completato_at)->format('d/m/Y H:i') ?? $lotto->data_fine->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
        </fieldset>

        <!-- Azioni -->
        <div class="flex items-center justify-between gap-3">
            <div>
                @if ($returnTo === 'preventivo')
                <button type="button" wire:click="tornaAlPreventivo" class="btn-secondary flex items-center gap-2"
                    wire:loading.attr="disabled" wire:target="tornaAlPreventivo">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span wire:loading.remove wire:target="tornaAlPreventivo">Torna al Preventivo</span>
                    <span wire:loading wire:target="tornaAlPreventivo">
                        <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Salvataggio...
                    </span>
                </button>
                @else
                <a href="{{ route('lotti.index') }}" class="btn-secondary">
                    Annulla
                </a>
                @endif
            </div>

            @unless($isReadOnly)
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ $isEditing ? 'Aggiorna Lotto' : 'Crea Lotto' }}
                        </span>
                        <span wire:loading>
                            <svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Salvataggio...
                        </span>
                    </button>
                </div>
            @endunless
        </div>
    </form>
</div>
