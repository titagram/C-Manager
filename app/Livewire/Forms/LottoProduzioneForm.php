<?php

namespace App\Livewire\Forms;

use App\Enums\LottoPricingMode;
use App\Enums\StatoConsumoMateriale;
use App\Enums\StatoLottoProduzione;
use App\Enums\UnitaMisura;
use App\Models\ComponenteCostruzione;
use App\Models\ConsumoMateriale;
use App\Models\Costruzione;
use App\Models\LottoComponenteManuale;
use App\Models\LottoMateriale;
use App\Models\LottoPrimaryMaterialProfile;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Services\BinPackingService;
use App\Services\InventoryService;
use App\Services\LottoPricingService;
use App\Services\Production\CassaVariantResolver;
use App\Services\Production\ComponentRequirementsBuilder;
use App\Services\Production\ConstructionOptimizerResolver;
use App\Services\Production\DTO\OptimizationInput;
use App\Services\Production\DTO\OptimizerResultPayload;
use App\Services\Production\OptimizerBinSubstitutionService;
use App\Services\Production\ProductionSettingsService;
use App\Services\Production\ScrapReusePlanner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LottoProduzioneForm extends Component
{
    public ?LottoProduzione $lotto = null;

    public string $codice_lotto = '';

    public string $prodotto_finale = '';

    public string $descrizione = '';

    public string $stato = 'bozza';

    // Navigation properties
    public ?string $returnTo = null;

    public ?int $preventivoId = null;

    public ?int $ordineId = null;

    // Dimension fields
    public string $larghezza_cm = '';

    public string $profondita_cm = '';

    public string $altezza_cm = '';

    public string $tipo_prodotto = '';

    public string $spessore_base_mm = '';

    public string $spessore_fondo_mm = '';

    public string $numero_pezzi = '1';

    public string $numero_univoco = '';

    // Costruzione (product prototype)
    public ?int $costruzione_id = null;

    public ?int $materiale_id = null;

    /**
     * @var array<string, int|string|null>
     */
    public array $primaryMaterialProfiles = [];

    // Optimizer results
    public ?array $optimizerResult = null;

    public bool $showOptimizerResults = false;

    public bool $showOptimizerDebugPanel = false;

    public array $selectedOptimizerBins = [];

    public bool $showSubstitutionModal = false;

    public ?int $substitutionMaterialId = null;

    public ?array $substitutionPreview = null;

    public array $compatibleSubstitutionMaterialIds = [];

    public bool $controllaScarti = false;

    public bool $isReadOnly = false;

    public ?string $readOnlyStatoLabel = null;

    public bool $usaScartiCompatibili = false;

    public bool $ignoraScartiCompatibili = false;

    public ?array $scartiCompatibiliPreview = null;

    // Calculated totals (real-time)
    public float $volume_totale_mc = 0;

    public float $costo_totale = 0;

    public float $prezzo_vendita_totale = 0;

    public float $scarto_totale_percentuale = 0;

    public string $pricing_mode = 'tariffa_mc';

    public ?float $tariffa_mc = null;

    public float $ricarico_percentuale = 0;

    public ?float $prezzo_finale_override = null;

    public float $prezzo_calcolato = 0;

    public float $prezzo_finale = 0;

    public array $pricing_snapshot = [];

    public float $totale_componenti_manuali_prezzo = 0;

    // Componenti manuali (componenti costruzione non calcolati)
    public array $componentiManuali = [];

    public function mount(?LottoProduzione $lotto = null): void
    {
        // Detect navigation parameters from query string
        $this->returnTo = request()->get('from');
        $this->preventivoId = request()->get('preventivo_id') ? (int) request()->get('preventivo_id') : null;
        $this->ordineId = request()->get('ordine_id') ? (int) request()->get('ordine_id') : null;

        if ($lotto?->exists) {
            $this->lotto = $lotto;
            $this->preventivoId = $lotto->preventivo_id ?: $lotto->ordine?->preventivo_id ?: $this->preventivoId;
            $this->ordineId = $lotto->ordine_id ?: $this->ordineId;
            $this->codice_lotto = $lotto->codice_lotto;
            $this->prodotto_finale = $lotto->prodotto_finale ?? '';
            $this->descrizione = $lotto->descrizione ?? '';
            $this->stato = $lotto->stato->value;

            // Load dimension fields
            $this->larghezza_cm = (string) ($lotto->larghezza_cm ?? '');
            $this->profondita_cm = (string) ($lotto->profondita_cm ?? '');
            $this->altezza_cm = (string) ($lotto->altezza_cm ?? '');
            $this->tipo_prodotto = $lotto->tipo_prodotto ?? '';
            $this->spessore_base_mm = (string) ($lotto->spessore_base_mm ?? '');
            $this->spessore_fondo_mm = (string) ($lotto->spessore_fondo_mm ?? '');
            $this->numero_pezzi = (string) ($lotto->numero_pezzi ?? '1');
            $this->numero_univoco = $lotto->numero_univoco ?? '';

            $this->costruzione_id = $lotto->costruzione_id;
            $this->syncPrimaryMaterialProfilesFromLotto($lotto);
            $this->materiale_id = $this->resolveMaterialeIdDaLotto($lotto);
            $this->pricing_mode = $lotto->pricing_mode?->value ?? LottoPricingMode::TARIFFA_MC->value;
            $this->tariffa_mc = $lotto->tariffa_mc !== null
                ? (float) $lotto->tariffa_mc
                : null;
            $this->ricarico_percentuale = (float) ($lotto->ricarico_percentuale ?? 0);
            $this->prezzo_finale_override = $lotto->prezzo_finale_override !== null
                ? (float) $lotto->prezzo_finale_override
                : null;
            $this->prezzo_calcolato = (float) ($lotto->prezzo_calcolato ?? 0);
            $this->prezzo_finale = (float) ($lotto->prezzo_finale ?? 0);
            $this->pricing_snapshot = $lotto->pricing_snapshot ?? [];

            // Load saved optimizer result if exists
            if ($lotto->optimizer_result) {
                $this->optimizerResult = OptimizerResultPayload::normalizeForRuntime($lotto->optimizer_result);
                $this->showOptimizerResults = $this->optimizerResult !== null;
            }

            $this->syncComponentiManuali();

            // Calculate totals on mount
            $this->ricalcolaTotali();
            $this->syncReadOnlyState();
        } elseif ($this->costruzione_id) {
            $this->syncComponentiManuali();
            $this->syncPrimaryMaterialProfilesForCurrentCostruzione();
        }
    }

    private function resolveMaterialeIdDaLotto(LottoProduzione $lotto): ?int
    {
        $profileMaterialId = $lotto->primaryMaterialProfiles()
            ->where('profile_key', 'base')
            ->value('prodotto_id');
        if ($profileMaterialId !== null) {
            return (int) $profileMaterialId;
        }

        $optimizerResult = OptimizerResultPayload::normalizeForRuntime($lotto->optimizer_result);
        $materialeDaOptimizer = data_get($optimizerResult, 'materiale.id');
        if (is_numeric($materialeDaOptimizer)) {
            return (int) $materialeDaOptimizer;
        }

        $materialeDaRigheSalvate = $lotto->materialiUsati()->value('prodotto_id');
        if ($materialeDaRigheSalvate !== null) {
            return (int) $materialeDaRigheSalvate;
        }

        return null;
    }

    private function syncReadOnlyState(): void
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        $this->isReadOnly = $this->lotto?->exists && (! $isAdmin || ! $this->lotto->canBeModified());
        $this->readOnlyStatoLabel = $this->isReadOnly
            ? ($this->lotto?->stato?->label() ?? null)
            : null;
    }

    private function ensureAdminEditor(): void
    {
        if (auth()->user()?->isAdmin()) {
            return;
        }

        throw ValidationException::withMessages([
            'lotto' => "Solo l'amministratore puo modificare o ricalcolare i dati tecnici del lotto.",
        ]);
    }

    private function ensureEditable(): void
    {
        $this->ensureAdminEditor();

        if (! $this->lotto?->exists || $this->lotto->canBeModified()) {
            return;
        }

        $stato = $this->lotto->stato->label();

        throw ValidationException::withMessages([
            'lotto' => "Questo lotto è in stato \"{$stato}\" e non può essere modificato.",
        ]);
    }

    public function toggleOptimizerDebugPanel(): void
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            return;
        }

        $this->showOptimizerDebugPanel = ! $this->showOptimizerDebugPanel;
    }

    public function rules(): array
    {
        $codiceRule = 'nullable|string|max:50|unique:lotti_produzione,codice_lotto';
        if ($this->lotto?->exists) {
            $codiceRule .= ','.$this->lotto->id;
        }

        $rules = [
            'codice_lotto' => $codiceRule,
            'prodotto_finale' => 'required|string|max:255',
            'descrizione' => 'nullable|string|max:1000',
            'stato' => 'required|in:'.implode(',', array_column(StatoLottoProduzione::cases(), 'value')),

            // Dimension fields
            'larghezza_cm' => 'nullable|numeric|min:0|max:999999.99',
            'profondita_cm' => 'nullable|numeric|min:0|max:999999.99',
            'altezza_cm' => 'nullable|numeric|min:0|max:999999.99',
            'tipo_prodotto' => 'nullable|string|max:50',
            'spessore_base_mm' => 'nullable|numeric|min:0|max:999999.99',
            'spessore_fondo_mm' => 'nullable|numeric|min:0|max:999999.99',
            'numero_pezzi' => 'nullable|integer|min:1',
            'numero_univoco' => 'nullable|string|max:10',
            'costruzione_id' => 'nullable|exists:costruzioni,id',
            'preventivoId' => 'nullable|exists:preventivi,id',
            'ordineId' => 'nullable|exists:ordini,id',
            'pricing_mode' => 'required|string|in:'.implode(',', array_column(LottoPricingMode::cases(), 'value')),
            'tariffa_mc' => 'nullable|numeric|min:0|max:999999999.99',
            'ricarico_percentuale' => 'nullable|numeric|min:0|max:999.99',
            'prezzo_finale_override' => 'nullable|numeric|min:0|max:999999999.99',
        ];

        if ($this->componentiManuali !== []) {
            $rules['componentiManuali'] = 'array';
            $rules['componentiManuali.*.componente_costruzione_id'] = 'required|exists:componenti_costruzione,id';
            $rules['componentiManuali.*.prodotto_id'] = 'required|exists:prodotti,id';
            $rules['componentiManuali.*.quantita'] = 'required|numeric|min:0.0001';
            $rules['componentiManuali.*.prezzo_unitario'] = 'nullable|numeric|min:0|max:999999999.9999';
            $rules['componentiManuali.*.unita_misura'] = 'required|string|max:10';
            $rules['componentiManuali.*.note'] = 'nullable|string|max:1000';
        }

        if ($this->requiresCassaPrimaryProfiles()) {
            foreach ($this->primaryMaterialProfileKeys() as $profileKey) {
                $rules["primaryMaterialProfiles.{$profileKey}"] = 'nullable|exists:prodotti,id';
            }
        }

        return $rules;
    }

    public function save(): void
    {
        $this->ensureEditable();
        $this->syncCassaSpessoreSnapshots();
        $validated = $this->validate();
        $this->ricalcolaTotali();
        [$selectedPreventivoId, $selectedOrdineId] = $this->resolveAssociazioni($validated);
        $this->validateStateConstraints($validated, $selectedPreventivoId, $selectedOrdineId);

        $data = [
            'codice_lotto' => $validated['codice_lotto'] ?: null,
            'prodotto_finale' => $validated['prodotto_finale'],
            'descrizione' => $validated['descrizione'] ?: null,
            'stato' => $validated['stato'],

            // Dimension fields
            'larghezza_cm' => $validated['larghezza_cm'] ?: null,
            'profondita_cm' => $validated['profondita_cm'] ?: null,
            'altezza_cm' => $validated['altezza_cm'] ?: null,
            'tipo_prodotto' => $validated['tipo_prodotto'] ?: null,
            'spessore_base_mm' => $validated['spessore_base_mm'] ?: null,
            'spessore_fondo_mm' => $validated['spessore_fondo_mm'] ?: null,
            'numero_pezzi' => $validated['numero_pezzi'] ?: 1,
            'numero_univoco' => $validated['numero_univoco'] ?: null,

            'costruzione_id' => $validated['costruzione_id'] ?: null,
            'preventivo_id' => $selectedPreventivoId,
            'ordine_id' => $selectedOrdineId,
            'cliente_id' => $this->resolveClienteId($selectedPreventivoId, $selectedOrdineId),
            'pricing_mode' => $validated['pricing_mode'],
            'tariffa_mc' => $validated['tariffa_mc'] ?? null,
            'ricarico_percentuale' => $validated['ricarico_percentuale'] ?? 0,
            'prezzo_finale_override' => $validated['prezzo_finale_override'] ?? null,
            'prezzo_calcolato' => $this->prezzo_calcolato,
            'prezzo_finale' => $this->prezzo_finale,
            'prezzo_calcolato_at' => now(),
            'pricing_snapshot' => $this->pricing_snapshot ?: null,

            // Save optimizer result if calculated
            'optimizer_result' => OptimizerResultPayload::normalizeForPersistence($this->optimizerResult),
        ];

        if ($this->lotto?->exists) {
            $this->lotto->update($data);
            $lotto = $this->lotto;
            session()->flash('success', "Lotto \"{$this->lotto->codice_lotto}\" aggiornato con successo.");
        } else {
            $data['created_by'] = auth()->id();
            $lotto = LottoProduzione::create($data);
            session()->flash('success', "Lotto \"{$lotto->codice_lotto}\" creato con successo.");
        }

        $this->lotto = $lotto;
        $this->savePrimaryMaterialProfiles($lotto);

        // Auto-persist optimizer materiali (delete old + re-generate from latest calculation)
        if ($this->optimizerResult && isset($this->optimizerResult['bins'])) {
            $lotto->materialiUsati()->delete();
            $this->generaMaterialiDaOptimizer($lotto);
        }

        $this->salvaComponentiManuali($lotto);
        $this->ricalcolaTotali();

        // Persist final pricing after materiali/componenti sync.
        $lotto->update([
            'pricing_mode' => $validated['pricing_mode'],
            'tariffa_mc' => $validated['tariffa_mc'] ?? null,
            'ricarico_percentuale' => $validated['ricarico_percentuale'] ?? 0,
            'prezzo_finale_override' => $validated['prezzo_finale_override'] ?? null,
            'prezzo_calcolato' => $this->prezzo_calcolato,
            'prezzo_finale' => $this->prezzo_finale,
            'prezzo_calcolato_at' => now(),
            'pricing_snapshot' => $this->pricing_snapshot ?: null,
        ]);

        // Update linked preventivo riga if exists
        $this->aggiornaRigaPreventivo($lotto);
        $this->syncRiutilizzoScartiDaOptimizer($lotto);

        // Redirect based on navigation context
        if ($this->returnTo === 'preventivo' && $this->preventivoId) {
            $this->redirect(route('preventivi.edit', $this->preventivoId));
        } else {
            $this->redirect(route('lotti.index'));
        }
    }

    private function validateStateConstraints(array $validated, ?int $selectedPreventivoId, ?int $selectedOrdineId): void
    {
        if (($validated['stato'] ?? StatoLottoProduzione::BOZZA->value) === StatoLottoProduzione::BOZZA->value) {
            return;
        }

        $errors = [];

        if ($selectedPreventivoId && ! $selectedOrdineId) {
            $errors['stato'] = 'Un lotto collegato solo al preventivo può restare soltanto in bozza. Genera prima l\'ordine.';
        }

        if (! $this->formHasTechnicalDefinition($validated)) {
            $errors['costruzione_id'] = 'Per portare il lotto oltre la bozza serve almeno una definizione tecnica (costruzione o materiali/componenti già definiti).';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function formHasTechnicalDefinition(array $validated): bool
    {
        if (! empty($validated['costruzione_id'])) {
            return true;
        }

        if (! empty($this->optimizerResult)) {
            return true;
        }

        if ($this->lotto?->hasTechnicalDefinition()) {
            return true;
        }

        return collect($this->componentiManuali)
            ->contains(fn (array $componente) => ! empty($componente['prodotto_id']) && (float) ($componente['quantita'] ?? 0) > 0);
    }

    /**
     * Update the linked preventivo riga with lotto totals
     */
    protected function aggiornaRigaPreventivo(LottoProduzione $lotto): void
    {
        $riga = PreventivoRiga::where('lotto_produzione_id', $lotto->id)->first();

        if ($riga) {
            $lunghezzaMm = $lotto->larghezza_cm !== null ? round((float) $lotto->larghezza_cm * 10, 2) : 0;
            $larghezzaMm = $lotto->profondita_cm !== null ? round((float) $lotto->profondita_cm * 10, 2) : 0;
            $spessoreMm = $lotto->altezza_cm !== null ? round((float) $lotto->altezza_cm * 10, 2) : 0;
            $pricingSnapshot = $this->buildPreventivoRigaPricingSnapshot($lotto);

            $riga->update([
                'descrizione' => $lotto->prodotto_finale ?? $lotto->descrizione ?? 'Lotto produzione',
                'unita_misura' => UnitaMisura::MC->value,
                'lunghezza_mm' => $lunghezzaMm,
                'larghezza_mm' => $larghezzaMm,
                'spessore_mm' => $spessoreMm,
                'quantita' => max(1, (int) ($lotto->numero_pezzi ?? 1)),
                'volume_mc' => $pricingSnapshot['volume_lordo_mc'],
                'materiale_netto' => $pricingSnapshot['volume_netto_mc'],
                'materiale_lordo' => $pricingSnapshot['volume_lordo_mc'],
                'prezzo_unitario' => $pricingSnapshot['prezzo_unitario'],
                'totale_riga' => $pricingSnapshot['totale_riga'],
            ]);

            $preventivo = $riga->preventivo;
            if ($preventivo) {
                $preventivo->ricalcolaTotali();
            }
        }
    }

    /**
     * @return array{
     *   volume_lordo_mc: float,
     *   volume_netto_mc: float,
     *   prezzo_unitario: float,
     *   totale_riga: float
     * }
     */
    private function buildPreventivoRigaPricingSnapshot(LottoProduzione $lotto): array
    {
        $optimizerPayload = OptimizerResultPayload::normalizeForRuntime(
            $this->lotto?->is($lotto)
                ? $this->optimizerResult
                : $lotto->optimizer_result
        );

        $volumeLordo = round(max(
            0,
            (float) (
                data_get($optimizerPayload, 'totali.volume_lordo_mc')
                ?? data_get($optimizerPayload, 'totali.volume_totale_mc')
                ?? $lotto->materialiUsati()->sum('volume_mc')
                ?? $lotto->volume_totale_mc
                ?? 0
            )
        ), 6);

        if ($volumeLordo <= 0) {
            $volumeLordo = round(max(0, (float) ($lotto->volume_totale_mc ?? 0)), 6);
        }

        $volumeNetto = data_get($optimizerPayload, 'totali.volume_netto_mc');
        if ($volumeNetto === null) {
            $sumNetto = (float) $lotto->materialiUsati()->sum('volume_netto_mc');
            $volumeNetto = $sumNetto > 0 ? $sumNetto : $volumeLordo;
        }

        $volumeNetto = round(max(0, min($volumeLordo > 0 ? $volumeLordo : (float) $volumeNetto, (float) $volumeNetto)), 6);

        $totaleRiga = round(max(
            0,
            (float) (
                $this->lotto?->is($lotto)
                    ? $this->prezzo_finale
                    : ($lotto->prezzo_finale ?? 0)
            )
        ), 2);

        if ($totaleRiga <= 0) {
            $totaleRiga = round(max(
                0,
                (float) (
                    $this->lotto?->is($lotto)
                        ? $this->prezzo_calcolato
                        : ($lotto->prezzo_calcolato ?? 0)
                )
            ), 2);
        }

        if ($totaleRiga <= 0) {
            $totaleRiga = round(max(0, (float) $lotto->calcolaPrezzoVenditaTotale()), 2);
        }

        $prezzoUnitario = 0.0;
        if ($volumeLordo > 0) {
            $prezzoUnitario = round($totaleRiga / $volumeLordo, 4);
        } elseif ($totaleRiga > 0) {
            $prezzoUnitario = $totaleRiga;
        }

        return [
            'volume_lordo_mc' => $volumeLordo,
            'volume_netto_mc' => $volumeNetto,
            'prezzo_unitario' => $prezzoUnitario,
            'totale_riga' => $totaleRiga,
        ];
    }

    public function updatedCostruzioneId(): void
    {
        // Reset results when costruzione changes
        $this->resetOptimizerResults();
        $this->syncComponentiManuali();
        $this->syncPrimaryMaterialProfilesForCurrentCostruzione();
    }

    public function updatedMaterialeId(): void
    {
        // Reset results when materiale changes
        if ($this->requiresCassaPrimaryProfiles()) {
            $this->primaryMaterialProfiles['base'] = $this->materiale_id;
            $this->syncCassaSpessoreSnapshots();
        }

        $this->resetOptimizerResults();
    }

    public function updatedPrimaryMaterialProfiles(): void
    {
        $baseMaterialId = $this->primaryMaterialProfiles['base'] ?? null;
        $this->materiale_id = is_numeric($baseMaterialId) ? (int) $baseMaterialId : null;
        $this->syncCassaSpessoreSnapshots();
        $this->resetOptimizerResults();
    }

    public function updatedLarghezzaCm(): void
    {
        // Reset results when dimensions change
        $this->resetOptimizerResults();
    }

    public function updatedProfonditaCm(): void
    {
        // Reset results when dimensions change
        $this->resetOptimizerResults();
    }

    public function updatedAltezzaCm(): void
    {
        // Reset results when dimensions change
        $this->resetOptimizerResults();
    }

    public function updatedNumeroPezzi(): void
    {
        // Reset results when quantity changes
        $this->resetOptimizerResults();
    }

    public function updatedControllaScarti(): void
    {
        if (! $this->controllaScarti) {
            $this->usaScartiCompatibili = false;
            $this->ignoraScartiCompatibili = false;
            $this->scartiCompatibiliPreview = null;
        } else {
            $this->ignoraScartiCompatibili = false;
        }

        $this->resetOptimizerResults();
    }

    public function updatedPricingMode(): void
    {
        if ($this->pricing_mode === LottoPricingMode::TARIFFA_MC->value) {
            $this->ricarico_percentuale = 0;
        }

        if ($this->pricing_mode === LottoPricingMode::COSTO_RICARICO->value) {
            $this->tariffa_mc = null;
        }

        $this->ricalcolaPrezzo();
    }

    public function updatedTariffaMc(): void
    {
        $this->ricalcolaPrezzo();
    }

    public function updatedRicaricoPercentuale(): void
    {
        $this->ricalcolaPrezzo();
    }

    public function updatedPrezzoFinaleOverride(): void
    {
        $this->ricalcolaPrezzo();
    }

    public function updatedComponentiManuali(): void
    {
        $this->ricalcolaTotali();
    }

    protected function resetOptimizerResults(): void
    {
        $this->showOptimizerResults = false;
        $this->optimizerResult = null;
        $this->selectedOptimizerBins = [];
        $this->showSubstitutionModal = false;
        $this->substitutionMaterialId = null;
        $this->substitutionPreview = null;
        $this->compatibleSubstitutionMaterialIds = [];
    }

    private function syncComponentiManuali(): void
    {
        if (! $this->costruzione_id) {
            $this->componentiManuali = [];

            return;
        }

        $componentiManuali = ComponenteCostruzione::query()
            ->where('costruzione_id', $this->costruzione_id)
            ->where(function ($query) {
                $query->where('calcolato', false)
                    ->orWhere('tipo_dimensionamento', 'MANUALE');
            })
            ->orderBy('id')
            ->get();

        $esistenti = collect();
        if ($this->lotto?->exists) {
            $esistenti = $this->lotto->componentiManuali()
                ->get()
                ->keyBy('componente_costruzione_id');
        }

        $this->componentiManuali = $componentiManuali->map(function (ComponenteCostruzione $componente) use ($esistenti) {
            $manuale = $esistenti->get($componente->id);

            return [
                'componente_costruzione_id' => $componente->id,
                'nome' => $componente->nome,
                'prodotto_id' => $manuale?->prodotto_id,
                'quantita' => $manuale ? (float) $manuale->quantita : 0,
                'prezzo_unitario' => $manuale?->prezzo_unitario !== null ? (float) $manuale->prezzo_unitario : null,
                'unita_misura' => $manuale?->unita_misura ?? UnitaMisura::PZ->value,
                'note' => $manuale?->note,
            ];
        })->toArray();
    }

    private function syncPrimaryMaterialProfilesFromLotto(LottoProduzione $lotto): void
    {
        $profiles = $lotto->primaryMaterialProfiles()
            ->orderBy('ordine')
            ->get()
            ->pluck('prodotto_id', 'profile_key')
            ->map(fn ($id) => $id !== null ? (int) $id : null)
            ->all();

        $this->primaryMaterialProfiles = is_array($profiles) ? $profiles : [];
        $this->syncPrimaryMaterialProfilesForCurrentCostruzione();
    }

    private function syncPrimaryMaterialProfilesForCurrentCostruzione(): void
    {
        if (! $this->requiresCassaPrimaryProfiles()) {
            $this->primaryMaterialProfiles = [];
            $this->syncCassaSpessoreSnapshots();

            return;
        }

        $keys = $this->primaryMaterialProfileKeys();
        $normalized = [];

        foreach ($keys as $profileKey) {
            $existing = $this->primaryMaterialProfiles[$profileKey] ?? null;

            if ($profileKey === 'base' && ! is_numeric($existing) && $this->materiale_id !== null) {
                $existing = $this->materiale_id;
            }

            $normalized[$profileKey] = is_numeric($existing) ? (int) $existing : null;
        }

        $this->primaryMaterialProfiles = $normalized;
        $this->syncCassaSpessoreSnapshots();
    }

    private function requiresCassaPrimaryProfiles(?Costruzione $costruzione = null): bool
    {
        $costruzione ??= $this->costruzione_id
            ? Costruzione::query()->find($this->costruzione_id)
            : null;

        if (! $costruzione || strtolower((string) $costruzione->categoria) !== 'cassa') {
            return false;
        }

        $variant = app(CassaVariantResolver::class)->resolve($costruzione);

        return (bool) ($variant['uses_excel_builder'] ?? false);
    }

    /**
     * @return array<int, string>
     */
    private function primaryMaterialProfileKeys(?Costruzione $costruzione = null): array
    {
        $costruzione ??= $this->costruzione_id
            ? Costruzione::query()->find($this->costruzione_id)
            : null;

        if (! $costruzione) {
            return [];
        }

        $variant = app(CassaVariantResolver::class)->resolve($costruzione);

        return collect($variant['required_profiles'] ?? [])
            ->filter(fn ($profile) => is_array($profile))
            ->map(fn (array $profile): string => (string) ($profile['key'] ?? 'base'))
            ->values()
            ->all();
    }

    private function syncCassaSpessoreSnapshots(): void
    {
        if (! $this->requiresCassaPrimaryProfiles()) {
            return;
        }

        $baseId = $this->primaryMaterialProfiles['base'] ?? null;
        $fondoId = $this->primaryMaterialProfiles['fondo'] ?? null;

        $base = is_numeric($baseId) ? Prodotto::query()->find((int) $baseId) : null;
        $fondo = is_numeric($fondoId) ? Prodotto::query()->find((int) $fondoId) : null;

        $this->spessore_base_mm = $base?->spessore_mm !== null ? (string) $base->spessore_mm : '';
        $this->spessore_fondo_mm = $fondo?->spessore_mm !== null ? (string) $fondo->spessore_mm : '';
    }

    private function savePrimaryMaterialProfiles(LottoProduzione $lotto): void
    {
        $selected = collect($this->primaryMaterialProfiles)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id);

        if ($selected->isEmpty()) {
            $lotto->primaryMaterialProfiles()->delete();

            return;
        }

        $orderedKeys = array_values(array_keys($selected->all()));
        $lotto->primaryMaterialProfiles()
            ->whereNotIn('profile_key', $orderedKeys)
            ->delete();

        foreach ($selected as $profileKey => $productId) {
            LottoPrimaryMaterialProfile::query()->updateOrCreate(
                [
                    'lotto_produzione_id' => $lotto->id,
                    'profile_key' => (string) $profileKey,
                ],
                [
                    'prodotto_id' => $productId,
                    'ordine' => (int) array_search($profileKey, $orderedKeys, true),
                ]
            );
        }
    }

    /**
     * @return array<string, Prodotto>
     */
    private function validateAndResolveSelectedPrimaryMaterials(Costruzione $costruzione): array
    {
        if (! $this->requiresCassaPrimaryProfiles($costruzione)) {
            $this->validate([
                'materiale_id' => 'required|exists:prodotti,id',
            ]);

            return [
                'base' => Prodotto::query()->findOrFail($this->materiale_id),
            ];
        }

        $rules = [];
        foreach ($this->primaryMaterialProfileKeys($costruzione) as $profileKey) {
            $rules["primaryMaterialProfiles.{$profileKey}"] = 'required|exists:prodotti,id';
        }

        $this->validate($rules);

        $productIds = collect($this->primaryMaterialProfiles)
            ->only($this->primaryMaterialProfileKeys($costruzione))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $materials = Prodotto::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $resolved = [];
        foreach ($this->primaryMaterialProfileKeys($costruzione) as $profileKey) {
            $productId = (int) ($this->primaryMaterialProfiles[$profileKey] ?? 0);
            $materiale = $materials->get($productId);
            if (! $materiale) {
                throw ValidationException::withMessages([
                    "primaryMaterialProfiles.{$profileKey}" => 'Materiale profilo non valido.',
                ]);
            }

            $resolved[$profileKey] = $materiale;
        }

        $this->materiale_id = $resolved['base']->id ?? null;
        $this->syncCassaSpessoreSnapshots();

        return $resolved;
    }

    private function salvaComponentiManuali(LottoProduzione $lotto): void
    {
        $idsPresenti = collect($this->componentiManuali)
            ->pluck('componente_costruzione_id')
            ->filter()
            ->values()
            ->toArray();

        if ($idsPresenti === []) {
            $lotto->componentiManuali()->delete();

            return;
        }

        $lotto->componentiManuali()
            ->whereNotIn('componente_costruzione_id', $idsPresenti)
            ->delete();

        foreach ($this->componentiManuali as $riga) {
            LottoComponenteManuale::updateOrCreate(
                [
                    'lotto_produzione_id' => $lotto->id,
                    'componente_costruzione_id' => $riga['componente_costruzione_id'],
                ],
                [
                    'prodotto_id' => $riga['prodotto_id'] ?: null,
                    'quantita' => $riga['quantita'] ?: 0,
                    'prezzo_unitario' => ($riga['prezzo_unitario'] ?? null) !== null && $riga['prezzo_unitario'] !== ''
                        ? (float) $riga['prezzo_unitario']
                        : null,
                    'unita_misura' => strtolower($riga['unita_misura'] ?? UnitaMisura::PZ->value),
                    'note' => $riga['note'] ?: null,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveAssociazioni(array $validated): array
    {
        $selectedPreventivoId = isset($validated['preventivoId']) && $validated['preventivoId'] !== ''
            ? (int) $validated['preventivoId']
            : ($this->preventivoId ?: null);

        $selectedOrdineId = isset($validated['ordineId']) && $validated['ordineId'] !== ''
            ? (int) $validated['ordineId']
            : ($this->ordineId ?: null);

        $canRetainStandaloneLotto = $this->lotto?->exists
            && $this->lotto->preventivo_id === null
            && $this->lotto->ordine_id === null;

        if ($selectedPreventivoId === null && $selectedOrdineId === null && ! $canRetainStandaloneLotto) {
            throw ValidationException::withMessages([
                'associazione' => 'Il lotto deve essere associato a un preventivo o a un ordine.',
            ]);
        }

        if ($selectedPreventivoId !== null && $selectedOrdineId !== null) {
            $ordine = Ordine::find($selectedOrdineId);
            if (! $ordine || (int) $ordine->preventivo_id !== $selectedPreventivoId) {
                throw ValidationException::withMessages([
                    'associazione' => 'Se il lotto mantiene sia preventivo sia ordine, l\'ordine deve provenire da quel preventivo.',
                ]);
            }
        }

        $this->preventivoId = $selectedPreventivoId;
        $this->ordineId = $selectedOrdineId;

        return [$selectedPreventivoId, $selectedOrdineId];
    }

    private function resolveClienteId(?int $selectedPreventivoId = null, ?int $selectedOrdineId = null): ?int
    {
        if ($selectedOrdineId) {
            return Ordine::find($selectedOrdineId)?->cliente_id;
        }

        if ($selectedPreventivoId) {
            return Preventivo::find($selectedPreventivoId)?->cliente_id;
        }

        if ($this->lotto?->cliente_id) {
            return $this->lotto->cliente_id;
        }

        if ($this->lotto?->preventivo?->cliente_id) {
            return $this->lotto->preventivo->cliente_id;
        }

        if ($this->preventivoId) {
            return Preventivo::find($this->preventivoId)?->cliente_id;
        }

        return null;
    }

    public function usaScartiCompatibiliERicalcola(): void
    {
        $this->ensureAdminEditor();
        $this->controllaScarti = true;
        $this->ignoraScartiCompatibili = false;

        $this->calcolaMateriali(
            app(BinPackingService::class),
            app(ComponentRequirementsBuilder::class),
            app(ConstructionOptimizerResolver::class),
            app(ProductionSettingsService::class),
        );
    }

    public function ignoraScartiCompatibiliERicalcola(): void
    {
        $this->ensureAdminEditor();
        $this->usaScartiCompatibili = false;
        $this->ignoraScartiCompatibili = true;

        $this->calcolaMateriali(
            app(BinPackingService::class),
            app(ComponentRequirementsBuilder::class),
            app(ConstructionOptimizerResolver::class),
            app(ProductionSettingsService::class),
        );
    }

    public function calcolaMateriali(
        BinPackingService $binPackingService,
        ComponentRequirementsBuilder $componentRequirementsBuilder,
        ConstructionOptimizerResolver $optimizerResolver,
        ProductionSettingsService $productionSettings
    ): void {
        $this->ensureEditable();

        // Validate base inputs first (dimension rules depend on selected costruzione)
        $validatedInputs = $this->validate([
            'costruzione_id' => 'required|exists:costruzioni,id',
            'numero_pezzi' => 'required|integer|min:1',
        ]);

        try {
            $costruzione = Costruzione::findOrFail($validatedInputs['costruzione_id']);
            $selectedPrimaryMaterials = $this->validateAndResolveSelectedPrimaryMaterials($costruzione);
            $materiale = $selectedPrimaryMaterials['base'];

            foreach ($selectedPrimaryMaterials as $selectedMateriale) {
                $optimizerResolver->assertPrimaryMaterialCompatible($costruzione, $selectedMateriale);
            }

            $this->validate($this->dimensionValidationRulesForCostruzione($costruzione));

            $requirements = $componentRequirementsBuilder->buildCalculatedPieces(
                costruzione: $costruzione->loadMissing('componenti'),
                materiale: $materiale,
                larghezzaCm: (float) $this->larghezza_cm,
                profonditaCm: (float) $this->profondita_cm,
                altezzaCm: (float) $this->altezza_cm,
                numeroPezzi: (int) $this->numero_pezzi,
                userId: auth()->id()
            );
            $piecesToPack = $requirements['pieces'];
            $formulaErrors = $requirements['errors'];
            $hasCategoryOptimizer = $optimizerResolver->hasCategoryOptimizer($costruzione);

            if (empty($piecesToPack)) {
                $details = $formulaErrors !== [] ? ' Dettaglio: '.$formulaErrors[0] : '';
                throw new \InvalidArgumentException(
                    'Nessun componente calcolato trovato o formule non valide.'.$details
                );
            }

            $scrapSuggestion = null;
            if ($this->controllaScarti) {
                if ($hasCategoryOptimizer) {
                    $this->scartiCompatibiliPreview = null;
                    $this->usaScartiCompatibili = false;
                } else {
                    $scrapSuggestion = $this->buildScartiCompatibiliSuggestion(
                        materiale: $materiale,
                        piecesToPack: $piecesToPack,
                        kerfMm: $productionSettings->cuttingKerfMm(),
                        minReusableLengthMm: $productionSettings->scrapReusableMinLengthMm()
                    );
                    $shouldUseScraps = ($scrapSuggestion['matched_count'] ?? 0) > 0
                        && ! $this->ignoraScartiCompatibili;

                    $this->usaScartiCompatibili = $shouldUseScraps;
                    $scrapSuggestion['used'] = $shouldUseScraps;
                    $this->scartiCompatibiliPreview = $scrapSuggestion;

                    if ($shouldUseScraps) {
                        $piecesToPack = $scrapSuggestion['pieces_after_reuse'];
                    }
                }
            } else {
                $this->scartiCompatibiliPreview = null;
                $this->usaScartiCompatibili = false;
                $this->ignoraScartiCompatibili = false;
            }

            if ($this->usaScartiCompatibili && empty($piecesToPack)) {
                $result = [
                    'version' => 'v2',
                    'optimizer' => [
                        'name' => 'legacy-bin-packing',
                        'version' => 'legacy-1d-v1',
                        'strategy' => 'direct-1d-bfd',
                    ],
                    'trace' => [
                        'scrap_reuse' => [
                            'check_enabled' => true,
                            'used' => true,
                            'matched_count' => (int) ($scrapSuggestion['matched_count'] ?? 0),
                            'required_count' => (int) ($scrapSuggestion['required_count'] ?? 0),
                            'used_scrap_ids' => $scrapSuggestion['used_scrap_ids'] ?? [],
                            'matches' => $scrapSuggestion['matches'] ?? [],
                            'available_scraps_count' => (int) ($scrapSuggestion['available_scraps_count'] ?? 0),
                        ],
                    ],
                    'bins' => [],
                    'total_bins' => 0,
                    'total_waste' => 0,
                    'total_waste_percent' => 0,
                    'bin_length' => (float) ($materiale->lunghezza_mm ?? 0),
                    'kerf' => 0,
                    'materiale' => [
                        'id' => $materiale->id,
                        'nome' => $materiale->nome,
                        'codice' => $materiale->codice,
                        'lunghezza_mm' => (float) ($materiale->lunghezza_mm ?? 0),
                        'larghezza_mm' => (float) ($materiale->larghezza_mm ?? 0),
                        'spessore_mm' => (float) ($materiale->spessore_mm ?? 0),
                        'unita_misura' => $materiale->unita_misura?->value ?? 'mc',
                        'costo_unitario' => $materiale->costo_unitario,
                        'prezzo_unitario' => $materiale->prezzo_unitario,
                        'prezzo_mc' => $materiale->prezzo_mc,
                    ],
                    'totali' => [
                        'costo_totale' => 0,
                        'prezzo_totale' => 0,
                        'volume_totale_mc' => 0,
                        'pricing_volume_basis' => 'lordo',
                        'volume_lordo_mc' => 0,
                        'volume_netto_mc' => 0,
                        'volume_scarto_mc' => 0,
                        'costo_totale_lordo' => 0,
                        'costo_totale_netto' => 0,
                        'prezzo_totale_lordo' => 0,
                        'prezzo_totale_netto' => 0,
                    ],
                ];

                $optimizationInput = OptimizationInput::fromRuntime(
                    costruzione: $costruzione,
                    materiale: $materiale,
                    kerfMm: 0,
                    dimensions: [
                        'larghezza_cm' => (float) $this->larghezza_cm,
                        'profondita_cm' => (float) $this->profondita_cm,
                        'altezza_cm' => (float) $this->altezza_cm,
                        'numero_pezzi' => (int) $this->numero_pezzi,
                    ],
                    pieces: []
                );

                $this->optimizerResult = OptimizerResultPayload::fromComputation($result, $optimizationInput);
                $this->showOptimizerResults = true;
                session()->flash(
                    'optimizer-success',
                    'Tutti i pezzi richiesti sono coperti dagli scarti compatibili: nessuna nuova asse da ottimizzare.'
                );
                $this->usaScartiCompatibili = false;

                return;
            }

            $cooldownSeconds = max(0, (int) config('production.material_calculation_cooldown_seconds', 0));
            if (! $this->acquireMaterialCalculationCooldown($cooldownSeconds)) {
                return;
            }

            // Run optimization
            $binLength = $materiale->lunghezza_mm ?? 4000; // Default 4m if not set
            $T = (float) ($materiale->spessore_mm ?? 0);
            $kerf = $productionSettings->cuttingKerfMm();

            $result = $optimizerResolver->optimizeOrNull(
                $costruzione,
                $piecesToPack,
                $materiale,
                $kerf,
                [
                    'larghezza_cm' => (float) $this->larghezza_cm,
                    'profondita_cm' => (float) $this->profondita_cm,
                    'altezza_cm' => (float) $this->altezza_cm,
                    'numero_pezzi' => (int) $this->numero_pezzi,
                    'selected_primary_materials' => $selectedPrimaryMaterials,
                    'scrap_reuse' => [
                        'enabled' => $this->controllaScarti,
                        'ignore' => $this->ignoraScartiCompatibili,
                        'min_reusable_length_mm' => $productionSettings->scrapReusableMinLengthMm(),
                    ],
                ]
            )
                ?? $binPackingService->pack($piecesToPack, $binLength, $kerf);

            if ($hasCategoryOptimizer) {
                $categoryScrapSuggestion = data_get($result, 'trace.scrap_reuse');
                if (is_array($categoryScrapSuggestion)) {
                    $scrapSuggestion = $categoryScrapSuggestion;
                    $this->scartiCompatibiliPreview = $categoryScrapSuggestion;
                    $this->usaScartiCompatibili = (bool) ($categoryScrapSuggestion['used'] ?? false);
                } else {
                    $this->scartiCompatibiliPreview = null;
                    $this->usaScartiCompatibili = false;
                }
            }

            if (! isset($result['optimizer'])) {
                $result['optimizer'] = [
                    'name' => 'legacy-bin-packing',
                    'version' => 'legacy-1d-v1',
                    'strategy' => 'direct-1d-bfd',
                ];
            }

            if (! isset($result['trace']) || ! is_array($result['trace'])) {
                $result['trace'] = [];
            }
            $result['trace']['settings_snapshot'] = array_merge(
                (array) ($result['trace']['settings_snapshot'] ?? []),
                $productionSettings->snapshotForTrace([
                    'cutting_kerf_mm',
                    'cassa_optimizer_mode',
                    'gabbia_excel_mode',
                    'bancale_excel_mode',
                    'legaccio_excel_mode',
                    'scrap_reusable_min_length_mm',
                ]),
                [
                    'cassa_shadow_compare_enabled' => $this->cassaShadowCompareEnabled(),
                    'cassa_shadow_compare_volume_delta_mc' => $this->cassaShadowCompareVolumeDeltaThresholdMc(),
                    'cassa_shadow_compare_waste_delta_percent' => $this->cassaShadowCompareWasteDeltaThresholdPercent(),
                ]
            );
            if (is_array($scrapSuggestion) && ! $hasCategoryOptimizer) {
                $scrapReuseUsed = $this->usaScartiCompatibili && (($scrapSuggestion['matched_count'] ?? 0) > 0);
                $result['trace']['scrap_reuse'] = [
                    'check_enabled' => $this->controllaScarti,
                    'used' => $scrapReuseUsed,
                    'matched_count' => (int) ($scrapSuggestion['matched_count'] ?? 0),
                    'required_count' => (int) ($scrapSuggestion['required_count'] ?? 0),
                    'used_scrap_ids' => $scrapReuseUsed ? ($scrapSuggestion['used_scrap_ids'] ?? []) : [],
                    'matches' => $scrapReuseUsed ? ($scrapSuggestion['matches'] ?? []) : [],
                    'available_scraps_count' => (int) ($scrapSuggestion['available_scraps_count'] ?? 0),
                ];
            }

            // Calculate estimated costs using optimizer geometric totals when available.
            $uom = $materiale->unita_misura;
            $boardsCount = (int) ($result['total_bins'] ?? 0);
            $volumeTotals = $this->resolveOptimizerVolumeTotals($result, $materiale, $binLength, $T);

            $shadowComparison = $this->runCassaShadowComparisonIfEnabled(
                costruzione: $costruzione,
                binPackingService: $binPackingService,
                piecesToPack: $piecesToPack,
                activeResult: $result,
                materiale: $materiale,
                binLengthMm: (float) $binLength,
                thicknessMm: $T,
                kerfMm: $kerf,
                activeVolumeTotals: $volumeTotals
            );
            if ($shadowComparison !== null) {
                $result['trace']['shadow_compare']['cassa'] = $shadowComparison;
            }

            $pricingTotals = $this->calculateOptimizerPricingTotalsFromBins($result, $materiale, $volumeTotals);
            $stockCheckSummary = $this->buildOptimizerMaterialAvailabilitySummary([
                'bins' => is_array($result['bins'] ?? null) ? $result['bins'] : [],
                'materiale' => [
                    'id' => $materiale->id,
                ],
            ]);
            $result['trace']['stock_check_summary'] = $stockCheckSummary;
            $result['trace']['stock_check'] = $stockCheckSummary[0] ?? [
                'required_qty' => 0.0,
                'available_qty' => 0.0,
                'uom' => strtolower((string) ($uom?->value ?? UnitaMisura::MC->value)),
                'uom_label' => $uom?->abbreviation() ?? 'm³',
                'enough' => true,
            ];
            $stockWarnings = collect($stockCheckSummary)
                ->filter(fn (array $row): bool => ! (bool) ($row['enough'] ?? false))
                ->map(function (array $row): string {
                    $requiredFormatted = number_format((float) ($row['required_qty'] ?? 0), 4, ',', '.');
                    $availableFormatted = number_format((float) ($row['available_qty'] ?? 0), 4, ',', '.');
                    $label = (string) ($row['material_name'] ?? 'Materiale');
                    $uomLabel = (string) ($row['uom_label'] ?? 'm³');

                    return "{$label}: richiesti {$requiredFormatted} {$uomLabel}, disponibili {$availableFormatted} {$uomLabel}";
                })
                ->values()
                ->all();
            if ($stockWarnings !== []) {
                session()->flash(
                    'optimizer-warning',
                    'Disponibilità magazzino insufficiente per i materiali selezionati: '.implode(' | ', $stockWarnings)
                );
            }

            // Add material info to result
            $result['materiale'] = [
                'id' => $materiale->id,
                'nome' => $materiale->nome,
                'codice' => $materiale->codice,
                'lunghezza_mm' => $binLength,
                'larghezza_mm' => (float) ($materiale->larghezza_mm ?? 0),
                'spessore_mm' => $T,
                'unita_misura' => $uom?->value ?? 'mc',
                'costo_unitario' => $materiale->costo_unitario,
                'prezzo_unitario' => $materiale->prezzo_unitario,
                'prezzo_mc' => $materiale->prezzo_mc,
            ];
            $result['material_profiles'] = $this->buildMaterialProfilesTracePayload($selectedPrimaryMaterials);

            // Add calculated totals to result for preview
            $result['totali'] = [
                // Backward-compatible aliases (currently priced on gross volume = include scarti)
                'costo_totale' => $pricingTotals['costo_totale_lordo'],
                'prezzo_totale' => $pricingTotals['prezzo_totale_lordo'],
                'volume_totale_mc' => (float) ($volumeTotals['volume_lordo_mc'] ?? 0),
                // New explicit totals
                'pricing_volume_basis' => 'lordo',
                'volume_lordo_mc' => (float) ($volumeTotals['volume_lordo_mc'] ?? 0),
                'volume_netto_mc' => $volumeTotals['volume_netto_mc'],
                'volume_scarto_mc' => $volumeTotals['volume_scarto_mc'],
                'costo_totale_lordo' => $pricingTotals['costo_totale_lordo'],
                'costo_totale_netto' => $pricingTotals['costo_totale_netto'],
                'prezzo_totale_lordo' => $pricingTotals['prezzo_totale_lordo'],
                'prezzo_totale_netto' => $pricingTotals['prezzo_totale_netto'],
            ];

            $optimizationInput = OptimizationInput::fromRuntime(
                costruzione: $costruzione,
                materiale: $materiale,
                kerfMm: $kerf,
                dimensions: [
                    'larghezza_cm' => (float) $this->larghezza_cm,
                    'profondita_cm' => (float) $this->profondita_cm,
                    'altezza_cm' => (float) $this->altezza_cm,
                    'numero_pezzi' => (int) $this->numero_pezzi,
                ],
                pieces: $piecesToPack
            );

            $this->optimizerResult = OptimizerResultPayload::fromComputation($result, $optimizationInput);
            $this->showOptimizerResults = true;

            $optimizerName = (string) data_get($result, 'optimizer.name', 'legacy-bin-packing');
            $optimizerLabel = $optimizerName === 'legacy-bin-packing'
                ? 'legacy bin packing'
                : "optimizer {$optimizerName}";

            session()->flash(
                'optimizer-success',
                $this->buildOptimizerSuccessMessage(
                    optimizerLabel: $optimizerLabel,
                    scrapSuggestion: $scrapSuggestion,
                    scrapReuseUsed: $this->usaScartiCompatibili
                )
            );
            $this->usaScartiCompatibili = false;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            session()->flash('optimizer-error', $e->getMessage());
            $this->showOptimizerResults = false;
            $this->usaScartiCompatibili = false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function dimensionValidationRulesForCostruzione(Costruzione $costruzione): array
    {
        return [
            'larghezza_cm' => $costruzione->richiede_lunghezza
                ? 'required|numeric|min:1'
                : 'nullable|numeric|min:0',
            'profondita_cm' => $costruzione->richiede_larghezza
                ? 'required|numeric|min:1'
                : 'nullable|numeric|min:0',
            'altezza_cm' => $costruzione->richiede_altezza
                ? 'required|numeric|min:1'
                : 'nullable|numeric|min:0',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $piecesToPack
     * @return array{
     *   required_count:int,
     *   available_scraps_count:int,
     *   matched_count:int,
     *   used_scrap_ids:array<int,int>,
     *   used_piece_indexes:array<int,int>,
     *   matches:array<int,array<string,mixed>>,
     *   used:bool,
     *   pieces_after_reuse:array<int, array<string, mixed>>,
     *   source_summaries:array<int, array<string, mixed>>
     * }
     */
    private function buildScartiCompatibiliSuggestion(
        Prodotto $materiale,
        array $piecesToPack,
        float $kerfMm,
        int $minReusableLengthMm
    ): array {
        return app(ScrapReusePlanner::class)->plan(
            materiale: $materiale,
            pieces: $piecesToPack,
            kerfMm: $kerfMm,
            minReusableLengthMm: $minReusableLengthMm
        );
    }

    private function syncRiutilizzoScartiDaOptimizer(LottoProduzione $lotto): void
    {
        if (! $this->optimizerResult) {
            return;
        }

        $scrapReuseTrace = data_get($this->optimizerResult, 'trace.scrap_reuse');
        if (! is_array($scrapReuseTrace)) {
            return;
        }

        $usedIds = collect($scrapReuseTrace['used_scrap_ids'] ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($usedIds->isEmpty()) {
            Scarto::query()
                ->where('riutilizzato_in_lotto_id', $lotto->id)
                ->update([
                    'riutilizzato' => false,
                    'riutilizzato_in_lotto_id' => null,
                ]);

            return;
        }

        Scarto::query()
            ->where('riutilizzato_in_lotto_id', $lotto->id)
            ->whereNotIn('id', $usedIds->all())
            ->update([
                'riutilizzato' => false,
                'riutilizzato_in_lotto_id' => null,
            ]);

        Scarto::query()
            ->whereIn('id', $usedIds->all())
            ->where(function ($query) use ($lotto) {
                $query->whereNull('riutilizzato_in_lotto_id')
                    ->orWhere('riutilizzato_in_lotto_id', $lotto->id);
            })
            ->update([
                'riutilizzato' => true,
                'riutilizzato_in_lotto_id' => $lotto->id,
            ]);
    }

    private function buildOptimizerSuccessMessage(
        string $optimizerLabel,
        ?array $scrapSuggestion,
        bool $scrapReuseUsed
    ): string {
        $message = "Calcolo materiali completato con successo ({$optimizerLabel}).";

        if (! is_array($scrapSuggestion) || ! $scrapReuseUsed) {
            return $message;
        }

        $matchedCount = (int) ($scrapSuggestion['matched_count'] ?? 0);
        $requiredCount = (int) ($scrapSuggestion['required_count'] ?? 0);

        if ($matchedCount <= 0) {
            return $message;
        }

        return "{$message} Riutilizzati automaticamente {$matchedCount} scarti compatibili su {$requiredCount} pezzi richiesti.";
    }

    private function formatScartoDimensioniLabel(Scarto $scarto): string
    {
        return sprintf(
            '%s x %s x %s mm',
            number_format((float) ($scarto->lunghezza_mm ?? 0), 0, ',', '.'),
            number_format((float) ($scarto->larghezza_mm ?? 0), 0, ',', '.'),
            number_format((float) ($scarto->spessore_mm ?? 0), 0, ',', '.')
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function usedScrapRowsForCurrentLotto(): array
    {
        if (! $this->lotto?->exists) {
            return [];
        }

        $optimizerResult = OptimizerResultPayload::normalizeForRuntime($this->lotto->optimizer_result);
        $residualsByScrap = collect(data_get($optimizerResult, 'trace.scrap_reuse.source_summaries', []))
            ->filter(fn ($summary) => is_array($summary) && is_numeric($summary['source_scrap_id'] ?? null))
            ->keyBy(fn (array $summary) => (int) $summary['source_scrap_id']);

        return Scarto::query()
            ->with([
                'lottoProduzione:id,codice_lotto',
                'lottoMateriale:id,codice_lotto,prodotto_id',
                'lottoMateriale.prodotto:id,nome,peso_specifico_kg_mc',
            ])
            ->where('riutilizzato_in_lotto_id', $this->lotto->id)
            ->orderBy('id')
            ->get()
            ->map(function (Scarto $scarto) use ($residualsByScrap): array {
                $pesoSpecifico = (float) ($scarto->lottoMateriale?->prodotto?->peso_specifico_kg_mc ?? 0);
                $residualSummary = $residualsByScrap->get((int) $scarto->id);
                $volumeMc = $scarto->calculatedVolumeMc();

                return [
                    'scrap_id' => (int) $scarto->id,
                    'materiale_nome' => (string) ($scarto->lottoMateriale?->prodotto?->nome ?? 'Materiale'),
                    'source_lotto_materiale_code' => $scarto->lottoMateriale?->codice_lotto,
                    'source_lotto_produzione_code' => $scarto->lottoProduzione?->codice_lotto,
                    'dimensioni_label' => $this->formatScartoDimensioniLabel($scarto),
                    'volume_mc' => round($volumeMc, 6),
                    'peso_kg' => round(Scarto::calculateWeightKgFromVolume($volumeMc, $pesoSpecifico), 3),
                    'remaining_length_mm' => round((float) ($residualSummary['remaining_length_mm'] ?? 0), 2),
                    'remaining_volume_mc' => round((float) ($residualSummary['remaining_volume_mc'] ?? 0), 6),
                    'note' => $scarto->note,
                ];
            })
            ->all();
    }

    public function toggleOptimizerBinSelection(int $index): void
    {
        $this->ensureEditable();

        $bins = is_array(data_get($this->optimizerResult, 'bins')) ? $this->optimizerResult['bins'] : [];
        if (! array_key_exists($index, $bins)) {
            return;
        }

        if (in_array($index, $this->selectedOptimizerBins, true)) {
            $this->selectedOptimizerBins = array_values(array_filter(
                $this->selectedOptimizerBins,
                fn (int $selected): bool => $selected !== $index
            ));
        } else {
            $this->selectedOptimizerBins[] = $index;
            $this->selectedOptimizerBins = array_values(array_unique($this->selectedOptimizerBins));
            sort($this->selectedOptimizerBins);
        }

        $this->substitutionPreview = null;
    }

    public function toggleAllOptimizerBinsSelection(): void
    {
        $this->ensureEditable();

        $bins = is_array(data_get($this->optimizerResult, 'bins')) ? $this->optimizerResult['bins'] : [];
        if ($bins === []) {
            return;
        }

        if (count($this->selectedOptimizerBins) === count($bins)) {
            $this->selectedOptimizerBins = [];
        } else {
            $this->selectedOptimizerBins = array_keys($bins);
        }

        $this->substitutionPreview = null;
    }

    public function openSubstitutionModal(): void
    {
        $this->ensureEditable();

        if (! is_array($this->optimizerResult) || ! is_array($this->optimizerResult['bins'] ?? null) || $this->optimizerResult['bins'] === []) {
            session()->flash('error', 'Calcola prima un piano di taglio valido.');

            return;
        }

        if ($this->selectedOptimizerBins === []) {
            session()->flash('error', 'Seleziona almeno un asse da sostituire.');

            return;
        }

        $this->compatibleSubstitutionMaterialIds = $this->loadCompatibleSubstitutionMaterialIds(
            app(OptimizerBinSubstitutionService::class),
            app(ConstructionOptimizerResolver::class)
        );

        if ($this->compatibleSubstitutionMaterialIds === []) {
            $this->showSubstitutionModal = false;
            $this->substitutionPreview = null;
            $this->substitutionMaterialId = null;
            session()->flash('error', 'Nessun materiale compatibile disponibile per le assi selezionate.');

            return;
        }

        $this->showSubstitutionModal = true;
        $this->substitutionPreview = null;

        if (! in_array((int) $this->substitutionMaterialId, $this->compatibleSubstitutionMaterialIds, true)) {
            $primaryMaterialId = (int) data_get($this->optimizerResult, 'materiale.id', 0);
            $preferredCandidateId = collect($this->compatibleSubstitutionMaterialIds)
                ->first(fn (int $id): bool => $id !== $primaryMaterialId);

            $this->substitutionMaterialId = $preferredCandidateId ?: $this->compatibleSubstitutionMaterialIds[0];
        }

        $this->previewSubstitution();
    }

    public function closeSubstitutionModal(): void
    {
        $this->showSubstitutionModal = false;
        $this->substitutionPreview = null;
        $this->substitutionMaterialId = null;
        $this->compatibleSubstitutionMaterialIds = [];
    }

    public function updatedSubstitutionMaterialId(): void
    {
        if (! $this->showSubstitutionModal) {
            return;
        }

        $this->previewSubstitution();
    }

    public function previewSubstitution(): void
    {
        $this->ensureEditable();

        if (! $this->showSubstitutionModal || ! is_array($this->optimizerResult)) {
            return;
        }

        $candidate = $this->resolveSelectedSubstitutionMaterial();
        if (! $candidate) {
            $this->substitutionPreview = null;

            return;
        }

        try {
            $previewPayload = app(OptimizerBinSubstitutionService::class)->substitute(
                $this->optimizerResult,
                $this->selectedOptimizerBins,
                $candidate
            );

            $availability = $this->buildOptimizerMaterialAvailabilitySummary($previewPayload);

            $this->substitutionPreview = [
                'payload' => $previewPayload,
                'availability' => $availability,
                'all_available' => collect($availability)->every(fn (array $row): bool => (bool) ($row['enough'] ?? false)),
            ];
        } catch (\Throwable $e) {
            $this->substitutionPreview = [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function applySubstitution(): void
    {
        $this->ensureEditable();

        if (! $this->showSubstitutionModal) {
            return;
        }

        if (($this->substitutionPreview['error'] ?? null) !== null) {
            session()->flash('error', (string) $this->substitutionPreview['error']);

            return;
        }

        if (! is_array($this->substitutionPreview['payload'] ?? null)) {
            session()->flash('error', 'Anteprima sostituzione non disponibile.');

            return;
        }

        if (! (bool) ($this->substitutionPreview['all_available'] ?? false)) {
            session()->flash('error', 'Materiale sostitutivo non disponibile in quantità sufficiente.');

            return;
        }

        $this->optimizerResult = $this->substitutionPreview['payload'];
        $this->showOptimizerResults = true;
        $this->showSubstitutionModal = false;
        $this->substitutionPreview = null;
        $this->compatibleSubstitutionMaterialIds = [];
        $this->substitutionMaterialId = null;
        $this->selectedOptimizerBins = [];

        $this->ricalcolaTotali();
        session()->flash('success', 'Sostituzione materiale applicata al piano di taglio.');
    }

    private function acquireMaterialCalculationCooldown(int $cooldownSeconds): bool
    {
        if ($cooldownSeconds <= 0) {
            return true;
        }

        $rateLimitKey = $this->materialCalculationRateLimitKey();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $retryAfterSeconds = max(1, RateLimiter::availableIn($rateLimitKey));
            session()->flash(
                'optimizer-error',
                "Calcolo materiali troppo ravvicinato. Attendere {$retryAfterSeconds} secondi e riprovare."
            );

            return false;
        }

        RateLimiter::hit($rateLimitKey, $cooldownSeconds);

        return true;
    }

    private function materialCalculationRateLimitKey(): string
    {
        $userId = auth()->id() ?? 'guest';
        $sessionId = session()->getId() ?: request()->ip() ?: 'unknown';

        return "lotto:calcola-materiali:{$userId}:{$sessionId}";
    }

    public function salvaMateriali(): void
    {
        $this->ensureEditable();

        if (! $this->lotto?->exists) {
            session()->flash('error', 'Salva prima il lotto prima di salvare i materiali.');

            return;
        }

        if (! $this->optimizerResult) {
            session()->flash('error', 'Esegui prima il calcolo dei materiali.');

            return;
        }

        // Remove old materials
        $this->lotto->materialiUsati()->delete();
        $this->savePrimaryMaterialProfiles($this->lotto);

        // Generate new materials
        $this->generaMaterialiDaOptimizer($this->lotto);

        session()->flash('success', 'Materiali salvati con successo.');
        $this->resetOptimizerResults();

        // Recalculate totals after adding materials
        $this->ricalcolaTotali();
    }

    /**
     * Generate materialiUsati records from optimizer result
     */
    protected function generaMaterialiDaOptimizer(LottoProduzione $lotto): void
    {
        if (! $this->optimizerResult || ! isset($this->optimizerResult['bins'])) {
            return;
        }

        $ordine = 0;

        foreach ($this->optimizerResult['bins'] as $index => $bin) {
            $materialeData = is_array($bin['source_material'] ?? null)
                ? $bin['source_material']
                : (is_array($this->optimizerResult['materiale'] ?? null) ? $this->optimizerResult['materiale'] : []);
            $materialeId = $bin['source_material_id'] ?? $materialeData['id'] ?? null;
            $sourceProfile = isset($bin['source_profile']) ? (string) $bin['source_profile'] : null;
            $materiale = $materialeId ? Prodotto::find($materialeId) : null;
            $binLength = (float) ($bin['capacity'] ?? $this->optimizerResult['bin_length'] ?? $materiale?->lunghezza_mm ?? 0);
            $width = (float) ($materiale->larghezza_mm ?? $materialeData['larghezza_mm'] ?? 100);
            $spessore = (float) ($materiale->spessore_mm ?? $materialeData['spessore_mm'] ?? 0);

            // Describe contents
            $contents = [];
            foreach (($bin['items'] ?? []) as $item) {
                $widthLabel = isset($item['width']) ? " x {$item['width']}mm" : '';
                $contents[] = "{$item['description']} ({$item['length']}mm{$widthLabel})";
            }
            $descrizione = 'Asse '.($index + 1).': '.implode(', ', $contents);

            // Gross volume of the whole board used (default pricing basis: includes scraps)
            $volumeMc = isset($bin['volume_lordo_mc'])
                ? (float) $bin['volume_lordo_mc']
                : ($binLength * $width * $spessore) / 1000000000;
            $volumeNettoMc = isset($bin['volume_netto_mc'])
                ? (float) $bin['volume_netto_mc']
                : null;
            $volumeScartoMc = isset($bin['volume_scarto_mc'])
                ? (float) $bin['volume_scarto_mc']
                : ($volumeNettoMc !== null ? max(0.0, $volumeMc - $volumeNettoMc) : null);

            // Calculate costs for this single board
            $costoMateriale = 0;
            $prezzoVendita = 0;

            if ($materiale) {
                $valuation = $this->calculateMaterialValuationForBoard(
                    materiale: $materiale,
                    volumeLordoMc: $volumeMc
                );
                $costoMateriale = $valuation['costo'];
                $prezzoVendita = $valuation['prezzo'];
            }

            $lotto->materialiUsati()->create([
                'prodotto_id' => $materialeId,
                'source_profile' => $sourceProfile,
                'descrizione' => substr($descrizione, 0, 255), // Truncate if too long
                'lunghezza_mm' => $binLength,
                'larghezza_mm' => $width,
                'spessore_mm' => $spessore,
                'quantita_pezzi' => 1, // 1 Board
                'volume_mc' => $volumeMc,
                'volume_netto_mc' => $volumeNettoMc,
                'volume_scarto_mc' => $volumeScartoMc,
                'costo_materiale' => $costoMateriale,
                'prezzo_vendita' => $prezzoVendita,
                'is_fitok' => (bool) ($materiale?->soggetto_fitok ?? ($materialeData['soggetto_fitok'] ?? false)),
                'pezzi_per_asse' => count($bin['items'] ?? []),
                'assi_necessarie' => 1,
                'scarto_per_asse_mm' => (float) ($bin['waste'] ?? 0),
                'scarto_totale_mm' => (float) ($bin['waste'] ?? 0),
                'scarto_percentuale' => (float) ($bin['waste_percent'] ?? 0),
                'ordine' => $ordine++,
            ]);
        }

        // Recalculate totals after adding materials
        $this->ricalcolaTotali();
    }

    /**
     * Recalculate all totals from lotto and materials
     */
    public function ricalcolaTotali(): void
    {
        if (! $this->lotto?->exists) {
            $this->volume_totale_mc = 0;
            $this->costo_totale = 0;
            $this->prezzo_vendita_totale = 0;
            $this->scarto_totale_percentuale = 0;
            $this->totale_componenti_manuali_prezzo = 0;
            $this->ricalcolaPrezzo();

            return;
        }

        // Refresh lotto to get latest data
        $this->lotto->refresh();

        $materialiUsati = $this->lotto->materialiUsati;
        $optimizerPreview = $this->showOptimizerResults ? $this->getOptimizerPreviewTotals() : null;

        // Canonical volume source:
        // 1) Sum of optimized materials (if present)
        // 2) Preview optimizer totals (when showing unsaved cut plan)
        // 3) Geometric dimensions (current form values)
        // 4) Persisted fallback field
        $volumeDaMateriali = (float) $materialiUsati->sum('volume_mc');
        if ($materialiUsati->count() > 0 && $volumeDaMateriali > 0) {
            $this->volume_totale_mc = round($volumeDaMateriali, 6);
            $this->sincronizzaVolumeTotaleLotto($this->volume_totale_mc);
        } elseif (($optimizerPreview['volume_totale_mc'] ?? 0) > 0) {
            $this->volume_totale_mc = (float) $optimizerPreview['volume_totale_mc'];
            $this->sincronizzaVolumeTotaleLotto($this->volume_totale_mc);
        } else {
            $volumeGeometrico = $this->calcolaVolumeGeometricoLotto();
            if ($volumeGeometrico > 0) {
                $this->volume_totale_mc = $volumeGeometrico;
                $this->sincronizzaVolumeTotaleLotto($this->volume_totale_mc);
            } else {
                $this->volume_totale_mc = (float) ($this->lotto->volume_totale_mc ?? 0);
            }
        }

        // Calculate costs from materialiUsati
        [$costoManuale, $prezzoManuale] = $this->calcolaTotaliComponentiManuali();
        $this->totale_componenti_manuali_prezzo = $prezzoManuale;
        $costoMateriali = $optimizerPreview['costo_totale'] ?? $this->lotto->calcolaCostoTotale();
        $prezzoMateriali = $optimizerPreview['prezzo_totale'] ?? $this->lotto->calcolaPrezzoVenditaTotale();
        $this->costo_totale = round((float) $costoMateriali + $costoManuale, 2);
        $this->prezzo_vendita_totale = round((float) $prezzoMateriali + $prezzoManuale, 2);

        // Weighted global scrap across all boards.
        $this->scarto_totale_percentuale = $materialiUsati->count() > 0
            ? $this->calcolaScartoPonderatoPercentuale($materialiUsati)
            : round(max(0, (float) ($this->optimizerResult['total_waste_percent'] ?? 0)), 2);
        $this->ricalcolaPrezzo();
    }

    private function calcolaVolumeGeometricoLotto(): float
    {
        $larghezzaCm = $this->larghezza_cm !== ''
            ? (float) $this->larghezza_cm
            : (float) ($this->lotto->larghezza_cm ?? 0);
        $profonditaCm = $this->profondita_cm !== ''
            ? (float) $this->profondita_cm
            : (float) ($this->lotto->profondita_cm ?? 0);
        $altezzaCm = $this->altezza_cm !== ''
            ? (float) $this->altezza_cm
            : (float) ($this->lotto->altezza_cm ?? 0);

        if ($larghezzaCm <= 0 || $profonditaCm <= 0 || $altezzaCm <= 0) {
            return 0.0;
        }

        $numeroPezzi = $this->numero_pezzi !== ''
            ? (int) $this->numero_pezzi
            : (int) ($this->lotto->numero_pezzi ?? 1);
        $numeroPezzi = max(1, $numeroPezzi);
        $volume = (
            $larghezzaCm *
            $profonditaCm *
            $altezzaCm *
            $numeroPezzi
        ) / 1000000;

        return round($volume, 6);
    }

    /**
     * @return array{
     *   volume_lordo_mc: float,
     *   volume_netto_mc: ?float,
     *   volume_scarto_mc: ?float
     * }
     */
    private function resolveOptimizerVolumeTotals(array $result, Prodotto $materiale, float $binLengthMm, float $thicknessMm): array
    {
        $boardWidthMm = (float) ($materiale->larghezza_mm ?? 0);
        $boardsCount = (int) ($result['total_bins'] ?? 0);

        $fallbackBoardVolumeMc = 0.0;
        if ($binLengthMm > 0 && $boardWidthMm > 0 && $thicknessMm > 0) {
            $fallbackBoardVolumeMc = ($binLengthMm * $boardWidthMm * $thicknessMm) / 1000000000;
        }

        $volumeLordo = data_get($result, 'cutting_totals.volume_lordo_mc');
        if ($volumeLordo === null) {
            $volumeLordo = $boardsCount * $fallbackBoardVolumeMc;
        }
        $volumeLordo = round(max(0, (float) $volumeLordo), 6);

        $volumeNetto = data_get($result, 'cutting_totals.volume_netto_mc');
        $volumeScarto = data_get($result, 'cutting_totals.volume_scarto_mc');

        if ($volumeNetto === null) {
            $derivedNet = $this->deriveNetVolumeFromBins($result['bins'] ?? null, $thicknessMm);
            if ($derivedNet !== null) {
                $volumeNetto = $derivedNet;
            }
        }

        if ($volumeNetto !== null) {
            $volumeNetto = round(max(0, (float) $volumeNetto), 6);
            $volumeNetto = min($volumeLordo, $volumeNetto);
        }

        if ($volumeNetto !== null) {
            $volumeScarto = max(0.0, $volumeLordo - $volumeNetto);
        } elseif ($volumeScarto === null) {
            $volumeScarto = null;
        }
        if ($volumeScarto !== null) {
            $volumeScarto = round(max(0, (float) $volumeScarto), 6);
            $volumeScarto = min($volumeLordo, $volumeScarto);
        }

        return [
            'volume_lordo_mc' => $volumeLordo,
            'volume_netto_mc' => $volumeNetto,
            'volume_scarto_mc' => $volumeScarto,
        ];
    }

    /**
     * @param  array<int, array{id:int, description:string, length:float, quantity:int, width?:float}>  $piecesToPack
     * @param  array<string, mixed>  $activeResult
     * @param  array{volume_lordo_mc: float, volume_netto_mc: ?float, volume_scarto_mc: ?float}  $activeVolumeTotals
     * @return array<string, mixed>|null
     */
    private function runCassaShadowComparisonIfEnabled(
        Costruzione $costruzione,
        BinPackingService $binPackingService,
        array $piecesToPack,
        array $activeResult,
        Prodotto $materiale,
        float $binLengthMm,
        float $thicknessMm,
        float $kerfMm,
        array $activeVolumeTotals
    ): ?array {
        if (strtolower((string) $costruzione->categoria) !== 'cassa') {
            return null;
        }

        if (! $this->cassaShadowCompareEnabled()) {
            return null;
        }

        if ((string) data_get($activeResult, 'optimizer.name', 'legacy-bin-packing') !== 'cassa') {
            return null;
        }

        $thresholds = [
            'total_bins_delta' => 1,
            'waste_percent_delta' => $this->cassaShadowCompareWasteDeltaThresholdPercent(),
            'volume_lordo_delta_mc' => $this->cassaShadowCompareVolumeDeltaThresholdMc(),
        ];

        try {
            $legacyResult = $binPackingService->pack($piecesToPack, $binLengthMm, max(0, $kerfMm));
            $legacyVolumeTotals = $this->resolveOptimizerVolumeTotals(
                $legacyResult,
                $materiale,
                $binLengthMm,
                $thicknessMm
            );

            $activeSummary = [
                'optimizer' => (string) data_get($activeResult, 'optimizer.name', 'unknown'),
                'total_bins' => (int) ($activeResult['total_bins'] ?? 0),
                'total_waste_percent' => round(max(0, (float) ($activeResult['total_waste_percent'] ?? 0)), 2),
                'volume_lordo_mc' => round(max(0, (float) ($activeVolumeTotals['volume_lordo_mc'] ?? 0)), 6),
                'volume_netto_mc' => $activeVolumeTotals['volume_netto_mc'],
            ];

            $legacySummary = [
                'optimizer' => 'legacy-bin-packing',
                'total_bins' => (int) ($legacyResult['total_bins'] ?? 0),
                'total_waste_percent' => round(max(0, (float) ($legacyResult['total_waste_percent'] ?? 0)), 2),
                'volume_lordo_mc' => round(max(0, (float) ($legacyVolumeTotals['volume_lordo_mc'] ?? 0)), 6),
                'volume_netto_mc' => $legacyVolumeTotals['volume_netto_mc'],
            ];

            $deltas = [
                'total_bins' => abs($activeSummary['total_bins'] - $legacySummary['total_bins']),
                'total_waste_percent' => abs($activeSummary['total_waste_percent'] - $legacySummary['total_waste_percent']),
                'volume_lordo_mc' => abs($activeSummary['volume_lordo_mc'] - $legacySummary['volume_lordo_mc']),
            ];

            $isSignificant = $deltas['total_bins'] >= $thresholds['total_bins_delta']
                || $deltas['total_waste_percent'] >= $thresholds['waste_percent_delta']
                || $deltas['volume_lordo_mc'] >= $thresholds['volume_lordo_delta_mc'];

            if ($isSignificant) {
                Log::warning('production.cassa_optimizer.shadow_delta', [
                    'lotto_id' => $this->lotto?->id,
                    'costruzione_id' => $costruzione->id,
                    'costruzione_slug' => $costruzione->slug,
                    'materiale_id' => $materiale->id,
                    'kerf_mm' => $kerfMm,
                    'dimensions_cm' => [
                        'larghezza' => (float) $this->larghezza_cm,
                        'profondita' => (float) $this->profondita_cm,
                        'altezza' => (float) $this->altezza_cm,
                        'numero_pezzi' => (int) $this->numero_pezzi,
                    ],
                    'active' => $activeSummary,
                    'legacy' => $legacySummary,
                    'deltas' => $deltas,
                    'thresholds' => $thresholds,
                ]);
            }

            return [
                'enabled' => true,
                'status' => 'ok',
                'significant' => $isSignificant,
                'active' => $activeSummary,
                'legacy' => $legacySummary,
                'deltas' => $deltas,
                'thresholds' => $thresholds,
            ];
        } catch (\Throwable $e) {
            Log::notice('production.cassa_optimizer.shadow_error', [
                'lotto_id' => $this->lotto?->id,
                'costruzione_id' => $costruzione->id,
                'costruzione_slug' => $costruzione->slug,
                'materiale_id' => $materiale->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'enabled' => true,
                'status' => 'error',
                'error' => $e->getMessage(),
                'thresholds' => $thresholds,
            ];
        }
    }

    private function cassaShadowCompareEnabled(): bool
    {
        return (bool) config('production.cassa_shadow_compare_enabled', false);
    }

    private function cassaShadowCompareVolumeDeltaThresholdMc(): float
    {
        return max(0.0, (float) config('production.cassa_shadow_compare_volume_delta_mc', 0.0005));
    }

    private function cassaShadowCompareWasteDeltaThresholdPercent(): float
    {
        return max(0.0, (float) config('production.cassa_shadow_compare_waste_delta_percent', 0.5));
    }

    private function deriveNetVolumeFromBins(mixed $bins, float $thicknessMm): ?float
    {
        if (! is_array($bins) || $thicknessMm <= 0) {
            return null;
        }

        $netVolumeMc = 0.0;
        $hasItems = false;

        foreach ($bins as $bin) {
            if (! is_array($bin)) {
                continue;
            }

            foreach (($bin['items'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                // If width is not explicitly tracked, we cannot derive a real net volume.
                if (! array_key_exists('width', $item)) {
                    return null;
                }

                $lengthMm = max(0.0, (float) ($item['length'] ?? 0));
                $widthMm = max(0.0, (float) ($item['width'] ?? 0));
                $netVolumeMc += ($lengthMm * $widthMm * $thicknessMm) / 1000000000;
                $hasItems = true;
            }
        }

        return $hasItems ? round($netVolumeMc, 6) : null;
    }

    /**
     * @return array{
     *   costo_totale_lordo: float,
     *   costo_totale_netto: ?float,
     *   prezzo_totale_lordo: float,
     *   prezzo_totale_netto: ?float
     * }
     */
    private function calculateOptimizerPricingTotalsFromBins(
        array $result,
        Prodotto $fallbackMateriale,
        array $fallbackVolumeTotals
    ): array {
        $bins = is_array($result['bins'] ?? null) ? $result['bins'] : [];
        if ($bins === []) {
            return $this->calculateMaterialPricingTotals(
                materiale: $fallbackMateriale,
                boardsCount: (int) ($result['total_bins'] ?? 0),
                volumeLordoMc: (float) ($fallbackVolumeTotals['volume_lordo_mc'] ?? 0),
                volumeNettoMc: $fallbackVolumeTotals['volume_netto_mc'] ?? null
            );
        }

        $materialIds = collect($bins)
            ->map(fn (array $bin) => (int) data_get($bin, 'source_material_id', 0))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $materiali = $materialIds === []
            ? collect()
            : Prodotto::query()->whereIn('id', $materialIds)->get()->keyBy('id');

        $aggregate = [
            'costo_totale_lordo' => 0.0,
            'costo_totale_netto' => 0.0,
            'prezzo_totale_lordo' => 0.0,
            'prezzo_totale_netto' => 0.0,
        ];
        $hasNet = false;

        foreach (collect($bins)->groupBy(fn (array $bin) => (int) data_get($bin, 'source_material_id', 0)) as $materialId => $groupedBins) {
            $materiale = $materiali->get((int) $materialId) ?: $fallbackMateriale;
            $volumeLordo = round((float) $groupedBins->sum(fn (array $bin) => (float) ($bin['volume_lordo_mc'] ?? 0)), 6);
            $volumeNetto = round((float) $groupedBins->sum(fn (array $bin) => (float) ($bin['volume_netto_mc'] ?? 0)), 6);
            $pricing = $this->calculateMaterialPricingTotals(
                materiale: $materiale,
                boardsCount: $groupedBins->count(),
                volumeLordoMc: $volumeLordo,
                volumeNettoMc: $volumeNetto > 0 ? $volumeNetto : null
            );

            $aggregate['costo_totale_lordo'] += (float) ($pricing['costo_totale_lordo'] ?? 0);
            $aggregate['prezzo_totale_lordo'] += (float) ($pricing['prezzo_totale_lordo'] ?? 0);

            if (($pricing['costo_totale_netto'] ?? null) !== null) {
                $aggregate['costo_totale_netto'] += (float) $pricing['costo_totale_netto'];
                $hasNet = true;
            }

            if (($pricing['prezzo_totale_netto'] ?? null) !== null) {
                $aggregate['prezzo_totale_netto'] += (float) $pricing['prezzo_totale_netto'];
                $hasNet = true;
            }
        }

        return [
            'costo_totale_lordo' => round($aggregate['costo_totale_lordo'], 2),
            'costo_totale_netto' => $hasNet ? round($aggregate['costo_totale_netto'], 2) : null,
            'prezzo_totale_lordo' => round($aggregate['prezzo_totale_lordo'], 2),
            'prezzo_totale_netto' => $hasNet ? round($aggregate['prezzo_totale_netto'], 2) : null,
        ];
    }

    /**
     * @return array{
     *   costo_totale_lordo: float,
     *   costo_totale_netto: ?float,
     *   prezzo_totale_lordo: float,
     *   prezzo_totale_netto: ?float
     * }
     */
    private function calculateMaterialPricingTotals(
        Prodotto $materiale,
        int $boardsCount,
        float $volumeLordoMc,
        ?float $volumeNettoMc
    ): array {
        $uom = $materiale->unita_misura?->value;

        if ($uom === UnitaMisura::PZ->value) {
            $costoUnitario = $materiale->costoListinoPerUnita(UnitaMisura::PZ);
            $prezzoUnitario = $materiale->prezzoListinoPerUnita(UnitaMisura::PZ);
            $costo = round($boardsCount * $costoUnitario, 2);
            $prezzo = round($boardsCount * $prezzoUnitario, 2);

            return [
                'costo_totale_lordo' => $costo,
                'costo_totale_netto' => $costo,
                'prezzo_totale_lordo' => $prezzo,
                'prezzo_totale_netto' => $prezzo,
            ];
        }

        $rates = $this->resolveMaterialVolumePricingRates($materiale);

        $costoLordo = round($volumeLordoMc * $rates['costo_per_mc'], 2);
        $prezzoLordo = round($volumeLordoMc * $rates['prezzo_per_mc'], 2);

        return [
            'costo_totale_lordo' => $costoLordo,
            'costo_totale_netto' => $volumeNettoMc !== null
                ? round($volumeNettoMc * $rates['costo_per_mc'], 2)
                : null,
            'prezzo_totale_lordo' => $prezzoLordo,
            'prezzo_totale_netto' => $volumeNettoMc !== null
                ? round($volumeNettoMc * $rates['prezzo_per_mc'], 2)
                : null,
        ];
    }

    /**
     * @return array{costo: float, prezzo: float}
     */
    private function calculateMaterialValuationForBoard(Prodotto $materiale, float $volumeLordoMc): array
    {
        $uom = $materiale->unita_misura?->value;

        if ($uom === UnitaMisura::PZ->value) {
            return [
                'costo' => $materiale->costoListinoPerUnita(UnitaMisura::PZ),
                'prezzo' => $materiale->prezzoListinoPerUnita(UnitaMisura::PZ),
            ];
        }

        $rates = $this->resolveMaterialVolumePricingRates($materiale);

        return [
            'costo' => round($volumeLordoMc * $rates['costo_per_mc'], 2),
            'prezzo' => round($volumeLordoMc * $rates['prezzo_per_mc'], 2),
        ];
    }

    /**
     * @return array{
     *   required_qty: float,
     *   available_qty: float,
     *   uom: string,
     *   uom_label: string,
     *   enough: bool
     * }
     */
    private function buildMaterialAvailabilityCheck(
        Prodotto $materiale,
        int $boardsCount,
        float $binLengthMm,
        float $volumeLordoMc
    ): array {
        $requiredQty = $this->resolveRequiredPrimaryMaterialQuantity(
            materiale: $materiale,
            boardsCount: $boardsCount,
            binLengthMm: $binLengthMm,
            volumeLordoMc: $volumeLordoMc
        );
        $availableQty = $this->resolveAvailablePrimaryMaterialQuantity($materiale);
        $uom = strtolower((string) ($materiale->unita_misura?->value ?? UnitaMisura::MC->value));
        $uomLabel = $materiale->unita_misura?->abbreviation() ?? 'm³';

        return [
            'required_qty' => $requiredQty,
            'available_qty' => $availableQty,
            'uom' => $uom,
            'uom_label' => $uomLabel,
            'enough' => $availableQty >= $requiredQty,
        ];
    }

    private function resolveRequiredPrimaryMaterialQuantity(
        Prodotto $materiale,
        int $boardsCount,
        float $binLengthMm,
        float $volumeLordoMc
    ): float {
        $uom = $materiale->unita_misura ?? UnitaMisura::MC;

        return match ($uom) {
            UnitaMisura::PZ => round((float) $boardsCount, 4),
            UnitaMisura::ML => round(max(0, ((float) $boardsCount * $binLengthMm) / 1000), 4),
            UnitaMisura::MQ => round(
                max(0, ((float) $boardsCount * $binLengthMm / 1000) * ((float) ($materiale->larghezza_mm ?? 0) / 1000)),
                4
            ),
            UnitaMisura::KG => round(
                max(0, $volumeLordoMc * (float) ($materiale->peso_specifico_kg_mc ?? 0)),
                4
            ),
            default => round(max(0, $volumeLordoMc), 4),
        };
    }

    private function resolveAvailablePrimaryMaterialQuantity(Prodotto $materiale): float
    {
        $inventoryService = app(InventoryService::class);

        $totaleDisponibile = LottoMateriale::query()
            ->where('prodotto_id', $materiale->id)
            ->get(['id', 'quantita_iniziale'])
            ->reduce(function (float $carry, LottoMateriale $lotto) use ($inventoryService) {
                $giacenza = (float) $inventoryService->calcolaGiacenza($lotto);
                if ($giacenza <= 0) {
                    return $carry;
                }

                $giaOpzionata = (float) ConsumoMateriale::query()
                    ->where('lotto_materiale_id', $lotto->id)
                    ->where('stato', StatoConsumoMateriale::OPZIONATO->value)
                    ->sum('quantita');

                return $carry + max(0, $giacenza - $giaOpzionata);
            }, 0.0);

        return round($totaleDisponibile, 4);
    }

    /**
     * For materials priced by m3, prefer `prezzo_mc` when available.
     *
     * @return array{costo_per_mc: float, prezzo_per_mc: float}
     */
    private function resolveMaterialVolumePricingRates(Prodotto $materiale): array
    {
        return [
            // `costo_unitario` is currently used as the canonical material cost field in lotto flows.
            'costo_per_mc' => $materiale->costoListinoPerUnita(UnitaMisura::MC),
            'prezzo_per_mc' => $materiale->prezzoListinoPerUnita(UnitaMisura::MC),
        ];
    }

    /**
     * @return array{volume_totale_mc: float, costo_totale: float, prezzo_totale: float}|null
     */
    private function getOptimizerPreviewTotals(): ?array
    {
        if (! is_array($this->optimizerResult)) {
            return null;
        }

        $totali = $this->optimizerResult['totali'] ?? null;
        if (! is_array($totali)) {
            return null;
        }

        return [
            'volume_totale_mc' => round(max(0, (float) ($totali['volume_totale_mc'] ?? 0)), 6),
            'costo_totale' => round(max(0, (float) ($totali['costo_totale'] ?? 0)), 2),
            'prezzo_totale' => round(max(0, (float) ($totali['prezzo_totale'] ?? 0)), 2),
        ];
    }

    private function sincronizzaVolumeTotaleLotto(float $volumeTotaleMc): void
    {
        $corrente = (float) ($this->lotto->volume_totale_mc ?? 0);
        if (abs($corrente - $volumeTotaleMc) < 0.000001) {
            return;
        }

        $this->lotto->update(['volume_totale_mc' => $volumeTotaleMc]);
        $this->lotto->refresh();
    }

    /**
     * @return array{float, float}
     */
    private function calcolaTotaliComponentiManuali(): array
    {
        $costo = 0.0;
        $prezzo = 0.0;

        if ($this->componentiManuali !== []) {
            $prodotti = Prodotto::query()
                ->whereIn('id', collect($this->componentiManuali)->pluck('prodotto_id')->filter()->all())
                ->get()
                ->keyBy('id');

            foreach ($this->componentiManuali as $riga) {
                $quantita = (float) ($riga['quantita'] ?? 0);
                if ($quantita <= 0) {
                    continue;
                }

                $prodotto = $prodotti->get((int) ($riga['prodotto_id'] ?? 0));
                $prezzoUnitario = (($riga['prezzo_unitario'] ?? null) !== null && $riga['prezzo_unitario'] !== '')
                    ? (float) $riga['prezzo_unitario']
                    : (float) ($prodotto?->prezzo_unitario ?? 0);

                $costo += $quantita * (float) ($prodotto?->costo_unitario ?? 0);
                $prezzo += $quantita * $prezzoUnitario;
            }

            return [round($costo, 2), round($prezzo, 2)];
        }

        $righePersistite = $this->lotto?->componentiManuali()->with('prodotto')->get() ?? collect();
        foreach ($righePersistite as $riga) {
            $quantita = (float) ($riga->quantita ?? 0);
            if ($quantita <= 0) {
                continue;
            }

            $prezzoUnitario = $riga->prezzo_unitario !== null
                ? (float) $riga->prezzo_unitario
                : (float) ($riga->prodotto?->prezzo_unitario ?? 0);

            $costo += $quantita * (float) ($riga->prodotto?->costo_unitario ?? 0);
            $prezzo += $quantita * $prezzoUnitario;
        }

        return [round($costo, 2), round($prezzo, 2)];
    }

    private function ricalcolaPrezzo(): void
    {
        $volumePerPricing = (float) $this->volume_totale_mc;
        if (
            $this->pricing_mode === LottoPricingMode::TARIFFA_MC->value
            && $this->lotto?->exists
            && ! $this->isPricingVolumeReady()
        ) {
            $volumePerPricing = 0.0;
        }

        $pricing = app(LottoPricingService::class)->calcola(
            volumeTotaleMc: $volumePerPricing,
            costoTotale: (float) $this->costo_totale,
            pricingMode: $this->pricing_mode,
            tariffaMc: $this->tariffa_mc,
            ricaricoPercentuale: $this->ricarico_percentuale,
            prezzoFinaleOverride: $this->prezzo_finale_override
        );

        $this->pricing_mode = $pricing['pricing_mode'];
        $this->tariffa_mc = $pricing['tariffa_mc'];
        $this->ricarico_percentuale = $pricing['ricarico_percentuale'];
        $totaleManuali = round(max(0, (float) $this->totale_componenti_manuali_prezzo), 2);
        $prezzoCalcolato = round($pricing['prezzo_calcolato'] + $totaleManuali, 2);
        $prezzoFinale = $this->prezzo_finale_override !== null
            ? $pricing['prezzo_finale']
            : $prezzoCalcolato;
        $pricingSource = 'explicit_pricing';

        if ($this->shouldUseMaterialListinoPricingFallback()) {
            $prezzoCalcolato = round(max(0, (float) $this->prezzo_vendita_totale), 2);
            $prezzoFinale = $this->prezzo_finale_override !== null
                ? round(max(0, (float) $this->prezzo_finale_override), 2)
                : $prezzoCalcolato;
            $pricingSource = 'fallback_materiali_listino';
        }

        $this->prezzo_calcolato = $prezzoCalcolato;
        $this->prezzo_finale = $prezzoFinale;
        $this->pricing_snapshot = array_merge($pricing['pricing_snapshot'], [
            'componenti_manuali_totale' => $totaleManuali,
            'prezzo_calcolato_finale' => $this->prezzo_calcolato,
            'prezzo_finale_finale' => $this->prezzo_finale,
            'pricing_source' => $pricingSource,
        ]);
    }

    private function shouldUseMaterialListinoPricingFallback(): bool
    {
        if ($this->pricing_mode !== LottoPricingMode::TARIFFA_MC->value) {
            return false;
        }

        if ($this->tariffa_mc !== null) {
            return false;
        }

        if (! $this->isPricingVolumeReady()) {
            return false;
        }

        return $this->prezzo_vendita_totale > 0;
    }

    private function isPricingVolumeReady(): bool
    {
        if (! $this->lotto?->exists) {
            return false;
        }

        if ($this->lotto->materialiUsati()->exists()) {
            return true;
        }

        if (! $this->showOptimizerResults) {
            return false;
        }

        $preview = $this->getOptimizerPreviewTotals();

        return ($preview['volume_totale_mc'] ?? 0) > 0;
    }

    /**
     * @return array<int, array{
     *   prezzo_unitario_effettivo: float,
     *   totale_riga: float,
     *   sorgente_prezzo: string,
     *   nome_prodotto: string|null
     * }>
     */
    private function buildComponentiManualiDettaglio(): array
    {
        if ($this->componentiManuali === []) {
            return [];
        }

        $prodottoIds = collect($this->componentiManuali)
            ->pluck('prodotto_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $prodotti = $prodottoIds === []
            ? collect()
            : Prodotto::query()->whereIn('id', $prodottoIds)->get()->keyBy('id');

        $dettaglio = [];
        foreach ($this->componentiManuali as $index => $riga) {
            $quantita = round(max(0, (float) ($riga['quantita'] ?? 0)), 4);
            $prodotto = $prodotti->get((int) ($riga['prodotto_id'] ?? 0));

            $hasCustomPrice = (($riga['prezzo_unitario'] ?? null) !== null && $riga['prezzo_unitario'] !== '');
            $prezzoUnitarioEffettivo = $hasCustomPrice
                ? (float) $riga['prezzo_unitario']
                : (float) ($prodotto?->prezzo_unitario ?? 0);

            $prezzoUnitarioEffettivo = round(max(0, $prezzoUnitarioEffettivo), 4);

            $dettaglio[$index] = [
                'prezzo_unitario_effettivo' => $prezzoUnitarioEffettivo,
                'totale_riga' => round($quantita * $prezzoUnitarioEffettivo, 2),
                'sorgente_prezzo' => $hasCustomPrice ? 'manuale' : 'listino',
                'nome_prodotto' => $prodotto?->nome,
            ];
        }

        return $dettaglio;
    }

    private function calcolaScartoPonderatoPercentuale($materialiUsati): float
    {
        if ($materialiUsati->count() === 0) {
            return 0.0;
        }

        $scartoTotaleMm = (float) $materialiUsati->sum(
            fn ($materiale) => (float) ($materiale->scarto_totale_mm ?? 0)
        );

        $baseTaglioMm = (float) $materialiUsati->sum(function ($materiale) {
            $lunghezzaAsseMm = (float) ($materiale->lunghezza_mm ?? 0);
            $assiNecessarie = max(1, (int) ($materiale->assi_necessarie ?? 1));

            return $lunghezzaAsseMm * $assiNecessarie;
        });

        if ($baseTaglioMm <= 0) {
            return 0.0;
        }

        return round(($scartoTotaleMm / $baseTaglioMm) * 100, 2);
    }

    public function tornaAlPreventivo(): void
    {
        $this->ensureAdminEditor();

        if ($this->lotto?->exists && ! $this->lotto->canBeModified()) {
            if ($this->preventivoId) {
                $preventivo = Preventivo::find($this->preventivoId);
                if ($preventivo?->exists) {
                    $this->redirect(route('preventivi.edit', $this->preventivoId));

                    return;
                }
            }

            $this->redirect(route('preventivi.index'));

            return;
        }

        // Auto-save changes before returning
        if ($this->lotto?->exists) {
            $this->syncCassaSpessoreSnapshots();
            $validated = $this->validate();
            $this->ricalcolaTotali();
            [$selectedPreventivoId, $selectedOrdineId] = $this->resolveAssociazioni($validated);

            $data = [
                'codice_lotto' => $validated['codice_lotto'] ?: null,
                'prodotto_finale' => $validated['prodotto_finale'],
                'descrizione' => $validated['descrizione'] ?: null,
                'stato' => $validated['stato'],
                'larghezza_cm' => $validated['larghezza_cm'] ?: null,
                'profondita_cm' => $validated['profondita_cm'] ?: null,
                'altezza_cm' => $validated['altezza_cm'] ?: null,
                'tipo_prodotto' => $validated['tipo_prodotto'] ?: null,
                'spessore_base_mm' => $validated['spessore_base_mm'] ?: null,
                'spessore_fondo_mm' => $validated['spessore_fondo_mm'] ?: null,
                'numero_pezzi' => $validated['numero_pezzi'] ?: 1,
                'numero_univoco' => $validated['numero_univoco'] ?: null,
                'costruzione_id' => $validated['costruzione_id'] ?: null,
                'preventivo_id' => $selectedPreventivoId,
                'ordine_id' => $selectedOrdineId,
                'cliente_id' => $this->resolveClienteId($selectedPreventivoId, $selectedOrdineId),
                'pricing_mode' => $validated['pricing_mode'],
                'tariffa_mc' => $validated['tariffa_mc'] ?? null,
                'ricarico_percentuale' => $validated['ricarico_percentuale'] ?? 0,
                'prezzo_finale_override' => $validated['prezzo_finale_override'] ?? null,
                'prezzo_calcolato' => $this->prezzo_calcolato,
                'prezzo_finale' => $this->prezzo_finale,
                'prezzo_calcolato_at' => now(),
                'pricing_snapshot' => $this->pricing_snapshot ?: null,
                'optimizer_result' => OptimizerResultPayload::normalizeForPersistence($this->optimizerResult),
            ];

            $this->lotto->update($data);
            $this->savePrimaryMaterialProfiles($this->lotto);

            // Auto-persist optimizer materiali before leaving
            if ($this->optimizerResult && isset($this->optimizerResult['bins'])) {
                $this->lotto->materialiUsati()->delete();
                $this->generaMaterialiDaOptimizer($this->lotto);
            }

            $this->salvaComponentiManuali($this->lotto);
            $this->ricalcolaTotali();

            // Persist final pricing after materiali/componenti sync
            $this->lotto->update([
                'prezzo_calcolato' => $this->prezzo_calcolato,
                'prezzo_finale' => $this->prezzo_finale,
                'prezzo_calcolato_at' => now(),
                'pricing_snapshot' => $this->pricing_snapshot ?: null,
            ]);

            // Update linked preventivo riga
            $this->aggiornaRigaPreventivo($this->lotto);
        }

        // Redirect to preventivo
        if ($this->preventivoId) {
            $preventivo = Preventivo::find($this->preventivoId);
            if ($preventivo?->exists) {
                $this->redirect(route('preventivi.edit', $this->preventivoId));

                return;
            }
        }

        // Fallback to preventivi index
        $this->redirect(route('preventivi.index'));
    }

    public function render()
    {
        $hasSavedCuttingPlan = $this->lotto?->exists
            ? $this->lotto->materialiUsati()->exists()
            : false;
        $optimizerResolver = app(ConstructionOptimizerResolver::class);
        $selectedCostruzione = $this->costruzione_id
            ? Costruzione::query()->find($this->costruzione_id)
            : null;
        $cassaVariant = $selectedCostruzione && strtolower((string) $selectedCostruzione->categoria) === 'cassa'
            ? app(CassaVariantResolver::class)->resolve($selectedCostruzione)
            : null;
        $materialCostState = $this->resolveMaterialCostDisplayState();
        $materialiAsse = $this->loadMaterialiAsseDisponibili($optimizerResolver);
        $materialiCassaPerProfilo = $this->buildMaterialiCassaPerProfilo($materialiAsse, $cassaVariant);
        $materialiSostituzioneCompatibili = $this->compatibleSubstitutionMaterialIds === []
            ? collect()
            : Prodotto::query()
                ->whereIn('id', $this->compatibleSubstitutionMaterialIds)
                ->orderBy('nome')
                ->get();

        return view('livewire.forms.lotto-produzione-form', [
            'stati' => StatoLottoProduzione::cases(),
            'isEditing' => $this->lotto?->exists,
            'canChangeStato' => ($this->lotto?->canBeModified() ?? true) && (auth()->user()?->isAdmin() ?? false),
            'hasSavedCuttingPlan' => $hasSavedCuttingPlan,
            'pricingVolumeReady' => $this->isPricingVolumeReady(),
            'pricingFallbackActive' => $this->shouldUseMaterialListinoPricingFallback(),
            'materialCostState' => $materialCostState,
            'costruzioni' => Costruzione::active()->orderBy('nome')->get(),
            'materiali' => Prodotto::where('is_active', true)
                ->whereIn('categoria', \App\Enums\Categoria::materiali())
                ->orderBy('nome')
                ->get(),
            'materialiAsse' => $materialiAsse,
            'selectedCostruzioneRuntime' => $selectedCostruzione,
            'cassaVariant' => $cassaVariant,
            'materialiCassaPerProfilo' => $materialiCassaPerProfilo,
            'materialiSostituzioneCompatibili' => $materialiSostituzioneCompatibili,
            'componentiManualiDettaglio' => $this->buildComponentiManualiDettaglio(),
            'scartiRiutilizzati' => $this->usedScrapRowsForCurrentLotto(),
            'unitaMisura' => UnitaMisura::cases(),
            'pricingModes' => LottoPricingMode::cases(),
            'ordiniDisponibili' => $this->loadOrdiniDisponibili(),
            'preventiviDisponibili' => $this->loadPreventiviDisponibili(),
        ]);
    }

    private function loadMaterialiAsseDisponibili(ConstructionOptimizerResolver $optimizerResolver)
    {
        $stockByProduct = LottoMateriale::query()
            ->leftJoin('movimenti_magazzino', 'lotti_materiale.id', '=', 'movimenti_magazzino.lotto_materiale_id')
            ->whereNull('lotti_materiale.deleted_at')
            ->selectRaw('lotti_materiale.id, lotti_materiale.prodotto_id, COALESCE(lotti_materiale.quantita_iniziale, 0) as quantita_iniziale')
            ->selectRaw("COALESCE(SUM(CASE WHEN movimenti_magazzino.tipo IN ('carico', 'rettifica_positiva') THEN movimenti_magazzino.quantita ELSE -movimenti_magazzino.quantita END), 0) as saldo_movimenti")
            ->selectRaw("MAX(CASE WHEN movimenti_magazzino.tipo = 'carico' THEN 1 ELSE 0 END) as ha_carico")
            ->groupBy('lotti_materiale.id', 'lotti_materiale.prodotto_id', 'lotti_materiale.quantita_iniziale')
            ->get()
            ->groupBy('prodotto_id')
            ->map(function ($rows) {
                return round($rows->sum(function ($row) {
                    $saldoMovimenti = (float) ($row->saldo_movimenti ?? 0);
                    $haCarico = (int) ($row->ha_carico ?? 0) === 1;
                    $baseline = (float) ($row->quantita_iniziale ?? 0);

                    return $haCarico ? $saldoMovimenti : ($baseline + $saldoMovimenti);
                }), 4);
            });

        $productIds = $stockByProduct
            ->filter(fn ($qty) => (float) $qty > 0)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($this->materiale_id !== null && ! in_array((int) $this->materiale_id, $productIds, true)) {
            $productIds[] = (int) $this->materiale_id;
        }

        foreach ($this->primaryMaterialProfiles as $profileMaterialId) {
            if (is_numeric($profileMaterialId) && ! in_array((int) $profileMaterialId, $productIds, true)) {
                $productIds[] = (int) $profileMaterialId;
            }
        }

        return Prodotto::query()
            ->where('is_active', true)
            ->whereIn('categoria', $optimizerResolver->allowedPrimaryMaterialCategoryValues())
            ->where(function ($query) use ($productIds) {
                if ($productIds !== []) {
                    $query->whereIn('id', $productIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('nome')
            ->get();
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, Prodotto>>
     */
    private function buildMaterialiCassaPerProfilo($materialiAsse, mixed $cassaVariant): array
    {
        if (! is_array($cassaVariant) || ! ($cassaVariant['uses_excel_builder'] ?? false)) {
            return [];
        }

        $options = [];
        foreach ($cassaVariant['required_profiles'] as $profile) {
            if (! is_array($profile)) {
                continue;
            }

            $profileKey = (string) ($profile['key'] ?? 'base');
            $requiredThickness = (float) ($profile['thickness_mm'] ?? 0);
            $requiredWidth = (float) ($profile['min_width_mm'] ?? 0);

            $options[$profileKey] = $materialiAsse
                ->filter(function (Prodotto $materiale) use ($requiredThickness, $requiredWidth): bool {
                    $thickness = round((float) ($materiale->spessore_mm ?? 0), 2);
                    $width = round((float) ($materiale->larghezza_mm ?? 0), 2);

                    return $thickness > 0
                        && ($requiredThickness <= 0 || $thickness === round($requiredThickness, 2))
                        && ($requiredWidth <= 0 || $width >= $requiredWidth);
                })
                ->values();
        }

        return $options;
    }

    /**
     * @param  array<string, Prodotto>  $selectedPrimaryMaterials
     * @return array<string, array<string, mixed>>
     */
    private function buildMaterialProfilesTracePayload(array $selectedPrimaryMaterials): array
    {
        $payload = [];

        foreach ($selectedPrimaryMaterials as $profileKey => $materiale) {
            $payload[$profileKey] = [
                'id' => $materiale->id,
                'nome' => $materiale->nome,
                'codice' => $materiale->codice,
                'lunghezza_mm' => (float) ($materiale->lunghezza_mm ?? 0),
                'larghezza_mm' => (float) ($materiale->larghezza_mm ?? 0),
                'spessore_mm' => (float) ($materiale->spessore_mm ?? 0),
                'unita_misura' => $materiale->unita_misura?->value ?? 'mc',
            ];
        }

        return $payload;
    }

    private function resolveSelectedSubstitutionMaterial(): ?Prodotto
    {
        $materialId = (int) ($this->substitutionMaterialId ?? 0);
        if ($materialId <= 0) {
            return null;
        }

        return Prodotto::query()->find($materialId);
    }

    /**
     * @return array<int, int>
     */
    private function loadCompatibleSubstitutionMaterialIds(
        OptimizerBinSubstitutionService $substitutionService,
        ConstructionOptimizerResolver $optimizerResolver
    ): array {
        if (! is_array($this->optimizerResult) || $this->selectedOptimizerBins === []) {
            return [];
        }

        $primaryThickness = round((float) data_get($this->optimizerResult, 'materiale.spessore_mm', 0), 2);
        $primaryWidth = round((float) data_get($this->optimizerResult, 'materiale.larghezza_mm', 0), 2);
        if ($primaryThickness <= 0 || $primaryWidth <= 0) {
            return [];
        }

        $primaryMaterialId = (int) data_get($this->optimizerResult, 'materiale.id', 0);

        $materiali = $this->loadMaterialiAsseDisponibili($optimizerResolver)
            ->filter(function (Prodotto $materiale) use ($primaryThickness, $primaryWidth, $primaryMaterialId): bool {
                return (int) $materiale->id !== $primaryMaterialId
                    && round((float) ($materiale->spessore_mm ?? 0), 2) === $primaryThickness
                    && round((float) ($materiale->larghezza_mm ?? 0), 2) === $primaryWidth;
            })
            ->values();

        $compatibili = $substitutionService->compatibleCandidatesForSelection(
            $this->optimizerResult,
            $this->selectedOptimizerBins,
            $materiali
        );

        return collect($compatibili)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function buildOptimizerMaterialAvailabilitySummary(array $payload): array
    {
        $grouped = collect(is_array($payload['bins'] ?? null) ? $payload['bins'] : [])
            ->filter(fn ($bin) => is_array($bin))
            ->groupBy(fn (array $bin) => (int) data_get($bin, 'source_material_id', data_get($payload, 'materiale.id', 0)));

        return $grouped->map(function ($bins, int $materialId): array {
            $materiale = Prodotto::query()->find($materialId);
            if (! $materiale) {
                return [
                    'material_id' => $materialId,
                    'material_name' => 'Materiale sconosciuto',
                    'required_qty' => 0.0,
                    'available_qty' => 0.0,
                    'uom' => UnitaMisura::MC->value,
                    'uom_label' => UnitaMisura::MC->abbreviation(),
                    'enough' => false,
                ];
            }

            $requiredQty = round($bins->sum(fn (array $bin): float => $this->resolveRequiredMaterialQuantityForBin($materiale, $bin)), 4);
            $availableQty = $this->resolveAvailablePrimaryMaterialQuantity($materiale);

            return [
                'material_id' => $materialId,
                'material_name' => $materiale->nome,
                'required_qty' => $requiredQty,
                'available_qty' => $availableQty,
                'uom' => strtolower((string) ($materiale->unita_misura?->value ?? UnitaMisura::MC->value)),
                'uom_label' => $materiale->unita_misura?->abbreviation() ?? 'm³',
                'enough' => $availableQty >= $requiredQty,
            ];
        })->values()->all();
    }

    private function resolveRequiredMaterialQuantityForBin(Prodotto $materiale, array $bin): float
    {
        $capacityMm = round((float) ($bin['capacity'] ?? $bin['bin_length'] ?? $materiale->lunghezza_mm ?? 0), 2);
        $derivedVolumeLordoMc = 0.0;
        if ($capacityMm > 0) {
            $derivedVolumeLordoMc = round(max(
                0,
                ($capacityMm * (float) ($materiale->larghezza_mm ?? 0) * (float) ($materiale->spessore_mm ?? 0)) / 1000000000
            ), 6);
        }

        $volumeLordoMc = round((float) ($bin['volume_lordo_mc'] ?? $derivedVolumeLordoMc), 6);
        $uom = $materiale->unita_misura ?? UnitaMisura::MC;

        return match ($uom) {
            UnitaMisura::PZ => 1.0,
            UnitaMisura::ML => round(max(0, $capacityMm / 1000), 4),
            UnitaMisura::MQ => round(
                max(0, ($capacityMm / 1000) * ((float) ($materiale->larghezza_mm ?? 0) / 1000)),
                4
            ),
            UnitaMisura::KG => round(
                max(0, $volumeLordoMc * (float) ($materiale->peso_specifico_kg_mc ?? 0)),
                4
            ),
            default => round(max(0, $volumeLordoMc), 4),
        };
    }

    /**
     * @return array{available: bool, display: string, message: string|null}
     */
    private function resolveMaterialCostDisplayState(): array
    {
        $hasKnownCost = false;
        $hasMissingCost = false;

        if ($this->lotto?->exists) {
            $materialiUsati = $this->lotto->materialiUsati()
                ->with('prodotto:id,costo_unitario,prezzo_unitario,prezzo_mc,unita_misura')
                ->get();

            foreach ($materialiUsati as $riga) {
                $hasSignal = ((float) ($riga->volume_mc ?? 0) > 0)
                    || ((int) ($riga->quantita_pezzi ?? 0) > 0)
                    || ((float) ($riga->prezzo_vendita ?? 0) > 0);

                if (! $hasSignal) {
                    continue;
                }

                if ((float) ($riga->costo_materiale ?? 0) > 0 || $this->productHasDefinedCost($riga->prodotto)) {
                    $hasKnownCost = true;

                    continue;
                }

                if ($riga->prodotto_id !== null || (float) ($riga->prezzo_vendita ?? 0) > 0) {
                    $hasMissingCost = true;
                }
            }
        } elseif (is_array($this->optimizerResult)) {
            $previewHasSignal = ((float) data_get($this->optimizerResult, 'totali.volume_totale_mc', 0) > 0)
                || ((float) data_get($this->optimizerResult, 'totali.prezzo_totale', 0) > 0);

            if ($previewHasSignal) {
                $materialIds = collect(is_array($this->optimizerResult['bins'] ?? null) ? $this->optimizerResult['bins'] : [])
                    ->map(fn (array $bin) => (int) data_get($bin, 'source_material_id', data_get($this->optimizerResult, 'materiale.id', 0)))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $materiali = $materialIds === []
                    ? collect()
                    : Prodotto::query()
                        ->select(['id', 'costo_unitario', 'prezzo_unitario', 'prezzo_mc', 'unita_misura'])
                        ->whereIn('id', $materialIds)
                        ->get()
                        ->keyBy('id');

                foreach ($materialIds as $materialId) {
                    $materiale = $materiali->get($materialId);

                    if ($this->productHasDefinedCost($materiale)) {
                        $hasKnownCost = true;

                        continue;
                    }

                    if ($materiale !== null) {
                        $hasMissingCost = true;
                    }
                }

                if (! $hasKnownCost && (float) data_get($this->optimizerResult, 'totali.costo_totale', 0) > 0) {
                    $hasKnownCost = true;
                }
            }
        }

        [$manualHasKnownCost, $manualHasMissingCost] = $this->resolveManualComponentCostState();
        $hasKnownCost = $hasKnownCost || $manualHasKnownCost;
        $hasMissingCost = $hasMissingCost || $manualHasMissingCost;

        if ($hasMissingCost) {
            return [
                'available' => false,
                'display' => 'N/D',
                'message' => 'Costo materiali non disponibile: manca il costo listino per uno o piu materiali del lotto.',
            ];
        }

        return [
            'available' => true,
            'display' => '€ '.number_format($this->costo_totale, 2),
            'message' => null,
        ];
    }

    /**
     * @return array{0: bool, 1: bool}
     */
    private function resolveManualComponentCostState(): array
    {
        $hasKnownCost = false;
        $hasMissingCost = false;

        if ($this->componentiManuali !== []) {
            $prodotti = Prodotto::query()
                ->whereIn('id', collect($this->componentiManuali)->pluck('prodotto_id')->filter()->all())
                ->get(['id', 'costo_unitario', 'prezzo_unitario', 'prezzo_mc', 'unita_misura'])
                ->keyBy('id');

            foreach ($this->componentiManuali as $riga) {
                $quantita = (float) ($riga['quantita'] ?? 0);
                $prodotto = $prodotti->get((int) ($riga['prodotto_id'] ?? 0));

                if ($quantita <= 0 || $prodotto === null) {
                    continue;
                }

                if ($this->productHasDefinedCost($prodotto)) {
                    $hasKnownCost = true;

                    continue;
                }

                if ((float) ($riga['prezzo_unitario'] ?? 0) > 0 || $prodotto->prezzoListinoPerUnita($prodotto->unita_misura) > 0) {
                    $hasMissingCost = true;
                }
            }

            return [$hasKnownCost, $hasMissingCost];
        }

        $righePersistite = $this->lotto?->componentiManuali()->with('prodotto')->get() ?? collect();
        foreach ($righePersistite as $riga) {
            $quantita = (float) ($riga->quantita ?? 0);
            $prodotto = $riga->prodotto;

            if ($quantita <= 0 || $prodotto === null) {
                continue;
            }

            if ($this->productHasDefinedCost($prodotto)) {
                $hasKnownCost = true;

                continue;
            }

            $prezzoUnitario = $riga->prezzo_unitario !== null
                ? (float) $riga->prezzo_unitario
                : $prodotto->prezzoListinoPerUnita($prodotto->unita_misura);

            if ($prezzoUnitario > 0) {
                $hasMissingCost = true;
            }
        }

        return [$hasKnownCost, $hasMissingCost];
    }

    private function productHasDefinedCost(?Prodotto $prodotto): bool
    {
        if (! $prodotto instanceof Prodotto) {
            return false;
        }

        return $prodotto->getRawOriginal('costo_unitario') !== null;
    }

    private function loadOrdiniDisponibili()
    {
        $query = Ordine::query()
            ->whereNull('deleted_at')
            ->where(function ($builder) {
                $builder->whereIn('stato', ['confermato', 'in_produzione'])
                    ->when($this->ordineId, fn ($q) => $q->orWhere('id', $this->ordineId));
            })
            ->with('cliente:id,ragione_sociale')
            ->orderByDesc('data_ordine')
            ->limit(200);

        return $query->get(['id', 'numero', 'cliente_id', 'preventivo_id', 'stato']);
    }

    private function loadPreventiviDisponibili()
    {
        $query = Preventivo::query()
            ->whereNull('deleted_at')
            ->where(function ($builder) {
                $builder->whereIn('stato', ['bozza', 'inviato', 'accettato'])
                    ->when($this->preventivoId, fn ($q) => $q->orWhere('id', $this->preventivoId));
            })
            ->with('cliente:id,ragione_sociale')
            ->orderByDesc('data')
            ->limit(200);

        return $query->get(['id', 'numero', 'cliente_id', 'stato']);
    }
}
