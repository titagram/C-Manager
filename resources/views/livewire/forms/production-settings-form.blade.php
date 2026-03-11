<div>
    @if (session()->has('success'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            {{ session('warning') }}
        </div>
    @endif

    @if (session()->has('critical'))
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('critical') }}
            <p class="mt-1 text-xs">
                Verifica sempre lo storico modifiche e, per `cassa`, riesegui la validazione rollout.
            </p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($lockPolicyActive && count($lockedKeys) > 0)
        <div class="mb-6 rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            Policy lock ambiente attiva: le chiavi bloccate non sono modificabili da pannello.
        </div>
    @endif

    @if (count($activePreviewModes) > 0)
        <div class="mb-6 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            <p class="font-medium">Modalita preview ancora attive</p>
            <p class="mt-1">
                Categorie coinvolte: {{ implode(', ', $activePreviewModes) }}.
                In `preview` il taglio usa fallback rettangolare v1; passa a `compatibility` o `strict` solo dopo validazione.
            </p>
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="card">
            <div class="card-body grid grid-cols-1 gap-4 md:grid-cols-2">
                @php($kerfLocked = in_array('cutting_kerf_mm', $lockedKeys, true))
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>{{ $fieldMeta['cutting_kerf_mm']['label'] }}</span>
                        <x-help-tooltip :text="$fieldMeta['cutting_kerf_mm']['help']" />
                    </label>
                    <input
                        wire:model="cutting_kerf_mm"
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-input @error('cutting_kerf_mm') form-input-error @enderror"
                        @disabled($kerfLocked)
                    >
                    @error('cutting_kerf_mm') <p class="form-error">{{ $message }}</p> @enderror
                    <!-- <p class="mt-1 text-xs text-muted-foreground">ENV: <code>{{ $fieldMeta['cutting_kerf_mm']['env'] }}</code></p> -->
                    @if($kerfLocked)
                        <p class="mt-1 text-xs font-medium text-amber-700">Chiave bloccata da policy ambiente.</p>
                    @endif
                </div>

                @php($scrapLocked = in_array('scrap_reusable_min_length_mm', $lockedKeys, true))
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>{{ $fieldMeta['scrap_reusable_min_length_mm']['label'] }}</span>
                        <x-help-tooltip :text="$fieldMeta['scrap_reusable_min_length_mm']['help']" />
                    </label>
                    <input
                        wire:model="scrap_reusable_min_length_mm"
                        type="number"
                        step="1"
                        min="0"
                        class="form-input @error('scrap_reusable_min_length_mm') form-input-error @enderror"
                        @disabled($scrapLocked)
                    >
                    @error('scrap_reusable_min_length_mm') <p class="form-error">{{ $message }}</p> @enderror
                    <!-- <p class="mt-1 text-xs text-muted-foreground">ENV: <code>{{ $fieldMeta['scrap_reusable_min_length_mm']['env'] }}</code></p> -->
                    @if($scrapLocked)
                        <p class="mt-1 text-xs font-medium text-amber-700">Chiave bloccata da policy ambiente.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="text-base font-medium">Mode per categoria</h3>
            </div>
            <div class="card-body grid grid-cols-1 gap-4 md:grid-cols-4">
                @php($cassaLocked = in_array('cassa_optimizer_mode', $lockedKeys, true))
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>{{ $fieldMeta['cassa_optimizer_mode']['label'] }}</span>
                        <x-help-tooltip :text="$fieldMeta['cassa_optimizer_mode']['help']" />
                    </label>
                    <select wire:model="cassa_optimizer_mode" class="form-select @error('cassa_optimizer_mode') form-input-error @enderror" @disabled($cassaLocked)>
                        @foreach($cassaModeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('cassa_optimizer_mode') <p class="form-error">{{ $message }}</p> @enderror
                    <!-- <p class="mt-1 text-xs text-muted-foreground">ENV: <code>{{ $fieldMeta['cassa_optimizer_mode']['env'] }}</code></p> -->
                    @if($cassaLocked)
                        <p class="mt-1 text-xs font-medium text-amber-700">Chiave bloccata da policy ambiente.</p>
                    @endif
                </div>

                @php($gabbiaLocked = in_array('gabbia_excel_mode', $lockedKeys, true))
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>{{ $fieldMeta['gabbia_excel_mode']['label'] }}</span>
                        <x-help-tooltip :text="$fieldMeta['gabbia_excel_mode']['help']" />
                    </label>
                    <select wire:model="gabbia_excel_mode" class="form-select @error('gabbia_excel_mode') form-input-error @enderror" @disabled($gabbiaLocked)>
                        @foreach($modeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('gabbia_excel_mode') <p class="form-error">{{ $message }}</p> @enderror
                    <!-- <p class="mt-1 text-xs text-muted-foreground">ENV: <code>{{ $fieldMeta['gabbia_excel_mode']['env'] }}</code></p> -->
                    @if($gabbiaLocked)
                        <p class="mt-1 text-xs font-medium text-amber-700">Chiave bloccata da policy ambiente.</p>
                    @endif
                </div>

                @php($bancaleLocked = in_array('bancale_excel_mode', $lockedKeys, true))
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>{{ $fieldMeta['bancale_excel_mode']['label'] }}</span>
                        <x-help-tooltip :text="$fieldMeta['bancale_excel_mode']['help']" />
                    </label>
                    <select wire:model="bancale_excel_mode" class="form-select @error('bancale_excel_mode') form-input-error @enderror" @disabled($bancaleLocked)>
                        @foreach($modeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('bancale_excel_mode') <p class="form-error">{{ $message }}</p> @enderror
                    <!-- <p class="mt-1 text-xs text-muted-foreground">ENV: <code>{{ $fieldMeta['bancale_excel_mode']['env'] }}</code></p> -->
                    @if($bancaleLocked)
                        <p class="mt-1 text-xs font-medium text-amber-700">Chiave bloccata da policy ambiente.</p>
                    @endif
                </div>

                @php($legaccioLocked = in_array('legaccio_excel_mode', $lockedKeys, true))
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>{{ $fieldMeta['legaccio_excel_mode']['label'] }}</span>
                        <x-help-tooltip :text="$fieldMeta['legaccio_excel_mode']['help']" />
                    </label>
                    <select wire:model="legaccio_excel_mode" class="form-select @error('legaccio_excel_mode') form-input-error @enderror" @disabled($legaccioLocked)>
                        @foreach($modeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('legaccio_excel_mode') <p class="form-error">{{ $message }}</p> @enderror
                    <!-- <p class="mt-1 text-xs text-muted-foreground">ENV: <code>{{ $fieldMeta['legaccio_excel_mode']['env'] }}</code></p> -->
                    @if($legaccioLocked)
                        <p class="mt-1 text-xs font-medium text-amber-700">Chiave bloccata da policy ambiente.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="text-base font-medium">Motivo modifica (audit)</h3>
            </div>
            <div class="card-body">
                <label class="form-label flex items-center gap-1">
                    <span>Motivo (opzionale, max 500 caratteri)</span>
                    <x-help-tooltip text="Il motivo viene salvato nello storico audit delle impostazioni." />
                </label>
                <textarea
                    wire:model="change_reason"
                    rows="3"
                    class="form-input @error('change_reason') form-input-error @enderror"
                    placeholder="Es. allineamento regole gabbia con benchmark Excel"
                ></textarea>
                @error('change_reason') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                Salva impostazioni produzione
            </button>
        </div>
    </form>

    @if($showDebugResetSection)
        <div class="card mt-6 border border-red-200 bg-red-50/40">
            <div class="card-header">
                <h3 class="text-base font-medium flex items-center gap-2">
                    <span>Reset ambiente debug</span>
                    <x-help-tooltip text="Operazione distruttiva: resetta il database e rilancia i seeder. Disponibile solo con APP_DEBUG=true." />
                </h3>
            </div>
            <div class="card-body space-y-3">
                <p class="text-sm text-red-700">
                    Sezione visibile solo in ambiente debug. Usare esclusivamente per ripristino rapido locale.
                </p>
                <div>
                    <label class="form-label flex items-center gap-1">
                        <span>Conferma testuale</span>
                        <x-help-tooltip text="Digita RESET DB per confermare l'operazione nel passo successivo." />
                    </label>
                    <input
                        wire:model.defer="debugResetConfirmation"
                        type="text"
                        class="form-input @error('debugResetConfirmation') form-input-error @enderror"
                        placeholder="RESET DB"
                    >
                    @error('debugResetConfirmation') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="btn-secondary"
                        wire:click="debugResetDatabase"
                        wire:loading.attr="disabled"
                    >
                        Resetta database e ripopola seed
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="card mt-6">
        <div class="card-header">
            <h3 class="text-base font-medium flex items-center gap-2">
                <span>Anomalie inventario (ultimi 30 giorni)</span>
                <x-help-tooltip text="KPI di controllo per distinguere possibili ammanchi da scarti non registrati o errori inventariali." />
            </h3>
        </div>
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="rounded border px-3 py-2">
                    <p class="text-xs text-muted-foreground">Rettifiche negative</p>
                    <p class="text-lg font-semibold">{{ $inventoryAnomalyReport['kpis']['rettifiche_negative_count'] }}</p>
                </div>
                <div class="rounded border px-3 py-2">
                    <p class="text-xs text-muted-foreground">Qty sospetto ammanco</p>
                    <p class="text-lg font-semibold">{{ number_format($inventoryAnomalyReport['kpis']['rettifiche_sospetto_ammanco_qty'], 4, ',', '.') }}</p>
                </div>
                <div class="rounded border px-3 py-2">
                    <p class="text-xs text-muted-foreground">Lotti mismatch scarti</p>
                    <p class="text-lg font-semibold">{{ $inventoryAnomalyReport['kpis']['scarti_mismatch_lotti_count'] }}</p>
                </div>
                <div class="rounded border px-3 py-2">
                    <p class="text-xs text-muted-foreground">Consumi senza movimento</p>
                    <p class="text-lg font-semibold">{{ $inventoryAnomalyReport['kpis']['consumi_senza_movimento_count'] }}</p>
                </div>
            </div>

            @if(!empty($inventoryAnomalyReport['top_lotti_rischio']))
                <div>
                    <p class="text-sm font-medium mb-2">Top lotti a rischio (delta scarti)</p>
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Lotto</th>
                                    <th class="text-right">Delta scarto (mc)</th>
                                    <th class="text-right">Teorico (mc)</th>
                                    <th class="text-right">Registrato (mc)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inventoryAnomalyReport['top_lotti_rischio'] as $row)
                                    <tr>
                                        <td class="font-mono">{{ $row['codice_lotto'] }}</td>
                                        <td class="text-right font-mono">{{ number_format($row['delta_scarto_mc'], 6, ',', '.') }}</td>
                                        <td class="text-right font-mono">{{ number_format($row['volume_scarto_teorico_mc'], 6, ',', '.') }}</td>
                                        <td class="text-right font-mono">{{ number_format($row['volume_scarto_registrato_mc'], 6, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if(!empty($inventoryAnomalyReport['top_materiali_rettifiche']))
                <div>
                    <p class="text-sm font-medium mb-2">Top lotti materiale per rettifiche negative</p>
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Lotto materiale</th>
                                    <th class="text-right">Qty rettifiche</th>
                                    <th class="text-right">Qty sospetto ammanco</th>
                                    <th class="text-right">Movimenti</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inventoryAnomalyReport['top_materiali_rettifiche'] as $row)
                                    <tr>
                                        <td class="font-mono">{{ $row['codice_lotto'] }}</td>
                                        <td class="text-right font-mono">{{ number_format($row['quantita_rettifiche_negative'], 4, ',', '.') }}</td>
                                        <td class="text-right font-mono">{{ number_format($row['quantita_sospetto_ammanco'], 4, ',', '.') }}</td>
                                        <td class="text-right">{{ $row['movimenti_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header">
            <h3 class="text-base font-medium">Storico modifiche</h3>
        </div>
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Utente</th>
                            <th>Chiave</th>
                            <th>Vecchio</th>
                            <th>Nuovo</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historyRows as $row)
                            <tr>
                                <td class="whitespace-nowrap text-sm">
                                    {{ optional($row->created_at)->format('d/m/Y H:i:s') ?? '-' }}
                                </td>
                                <td class="text-sm">{{ $row->changedBy->name ?? 'N/A' }}</td>
                                <td class="font-mono text-xs">{{ $row->key }}</td>
                                <td class="font-mono text-xs">{{ $row->old_value ?? '-' }}</td>
                                <td class="font-mono text-xs">{{ $row->new_value ?? '-' }}</td>
                                <td class="text-sm">{{ $row->changed_reason ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-muted-foreground">
                                    Nessuna modifica registrata.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
