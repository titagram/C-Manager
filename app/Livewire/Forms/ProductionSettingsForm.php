<?php

namespace App\Livewire\Forms;

use App\Services\InventoryAnomalyService;
use App\Services\Production\ProductionSettingsService;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductionSettingsForm extends Component
{
    private const DEBUG_RESET_CONFIRMATION_TOKEN = 'RESET DB';

    /**
     * @var array<int, string>
     */
    private const CRITICAL_SETTING_KEYS = [
        'cutting_kerf_mm',
        'cassa_optimizer_mode',
        'gabbia_excel_mode',
        'bancale_excel_mode',
        'legaccio_excel_mode',
    ];

    public string $cutting_kerf_mm = '0';

    public string $cassa_optimizer_mode = 'physical';

    public string $gabbia_excel_mode = 'preview';

    public string $bancale_excel_mode = 'preview';

    public string $legaccio_excel_mode = 'preview';

    public string $scrap_reusable_min_length_mm = '500';

    public string $change_reason = '';

    /** @var array<int, string> */
    public array $lockedKeys = [];

    public bool $lockPolicyActive = false;

    /**
     * @var array<string, array{label:string, help:string, env:string}>
     */
    public array $fieldMeta = [];

    /**
     * @var array<int, string>
     */
    public array $activePreviewModes = [];

    public string $debugResetConfirmation = '';

    public function mount(ProductionSettingsService $settings): void
    {
        $this->ensureAdmin();
        $this->hydrateFromSettings($settings);
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'cutting_kerf_mm' => 'required|numeric|min:0|max:50',
            'cassa_optimizer_mode' => 'required|in:physical,excel_strict,legacy,category',
            'gabbia_excel_mode' => 'required|in:preview,compatibility,strict',
            'bancale_excel_mode' => 'required|in:preview,compatibility,strict',
            'legaccio_excel_mode' => 'required|in:preview,compatibility,strict',
            'scrap_reusable_min_length_mm' => 'required|integer|min:0|max:50000',
            'change_reason' => 'nullable|string|max:500',
        ];
    }

    public function save(ProductionSettingsService $settings): void
    {
        $this->ensureAdmin();
        $validated = $this->validate();

        try {
            $result = $settings->updateMany([
                'cutting_kerf_mm' => $validated['cutting_kerf_mm'],
                'cassa_optimizer_mode' => $validated['cassa_optimizer_mode'],
                'gabbia_excel_mode' => $validated['gabbia_excel_mode'],
                'bancale_excel_mode' => $validated['bancale_excel_mode'],
                'legaccio_excel_mode' => $validated['legaccio_excel_mode'],
                'scrap_reusable_min_length_mm' => $validated['scrap_reusable_min_length_mm'],
            ], auth()->id(), $validated['change_reason'] ?: null);

            $this->hydrateFromSettings($settings);
            $this->change_reason = '';

            $savedCount = count($result['saved']);
            $lockedCount = count($result['locked']);

            if ($savedCount > 0) {
                session()->flash('success', "Impostazioni salvate ({$savedCount} aggiornate).");
            } else {
                session()->flash('warning', 'Nessuna impostazione aggiornata.');
            }

            $criticalChanged = $this->criticalChangedKeys($result['saved']);
            if ($criticalChanged !== []) {
                session()->flash(
                    'critical',
                    'Attenzione: modificati parametri critici ('.implode(', ', $criticalChanged).').'
                );
            }

            if ($lockedCount > 0) {
                session()->flash(
                    'warning',
                    'Alcune chiavi sono bloccate da policy ambiente: '.implode(', ', $result['locked'])
                );
            }
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function debugResetDatabase(): void
    {
        $this->ensureAdmin();

        if (! (bool) config('app.debug')) {
            session()->flash('error', 'Operazione non disponibile: APP_DEBUG deve essere TRUE.');

            return;
        }

        $normalizedConfirmation = trim(preg_replace('/\s+/', ' ', $this->debugResetConfirmation) ?? '');
        if ($normalizedConfirmation !== self::DEBUG_RESET_CONFIRMATION_TOKEN) {
            $this->addError(
                'debugResetConfirmation',
                'Conferma non valida: inserisci esattamente "RESET DB".'
            );

            return;
        }

        $this->resetValidation('debugResetConfirmation');

        $exitCode = Artisan::call('app:debug-reset-db', [
            '--confirmed' => true,
            '--requested-by' => auth()->id(),
        ]);

        if ($exitCode === 0) {
            session()->flash('success', 'Reset database completato con successo.');
            $this->debugResetConfirmation = '';

            return;
        }

        session()->flash('error', 'Reset database fallito. Verifica i log applicativi.');
    }

    public function render()
    {
        $this->ensureAdmin();

        /** @var ProductionSettingsService $settings */
        $settings = app(ProductionSettingsService::class);
        /** @var InventoryAnomalyService $inventoryAnomalyService */
        $inventoryAnomalyService = app(InventoryAnomalyService::class);

        try {
            $inventoryAnomalyReport = $inventoryAnomalyService->analyzeLastDays(30);
        } catch (\Throwable) {
            $inventoryAnomalyReport = [
                'period' => [
                    'from' => now()->subDays(29)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'kpis' => [
                    'rettifiche_negative_count' => 0,
                    'rettifiche_negative_qty' => 0.0,
                    'rettifiche_negative_without_reason_code_count' => 0,
                    'rettifiche_negative_reason_coverage_percent' => 100.0,
                    'rettifiche_sospetto_ammanco_qty' => 0.0,
                    'scarti_mismatch_lotti_count' => 0,
                    'scarti_mismatch_delta_mc' => 0.0,
                    'consumi_senza_movimento_count' => 0,
                ],
                'top_lotti_rischio' => [],
                'top_materiali_rettifiche' => [],
            ];
        }

        return view('livewire.forms.production-settings-form', [
            'cassaModeOptions' => [
                'physical' => 'physical',
                'excel_strict' => 'excel_strict',
                'legacy' => 'legacy',
            ],
            'modeOptions' => [
                'preview' => 'preview',
                'compatibility' => 'compatibility',
                'strict' => 'strict',
            ],
            'showDebugResetSection' => (bool) config('app.debug'),
            'inventoryAnomalyReport' => $inventoryAnomalyReport,
            'historyRows' => $settings->recentHistory(20),
        ]);
    }

    private function hydrateFromSettings(ProductionSettingsService $settings): void
    {
        $values = $settings->all();

        $this->cutting_kerf_mm = (string) ($values['cutting_kerf_mm'] ?? '0');
        $this->cassa_optimizer_mode = (string) ($values['cassa_optimizer_mode'] ?? 'physical');
        $this->gabbia_excel_mode = (string) ($values['gabbia_excel_mode'] ?? 'preview');
        $this->bancale_excel_mode = (string) ($values['bancale_excel_mode'] ?? 'preview');
        $this->legaccio_excel_mode = (string) ($values['legaccio_excel_mode'] ?? 'preview');
        $this->scrap_reusable_min_length_mm = (string) ($values['scrap_reusable_min_length_mm'] ?? '500');
        $this->activePreviewModes = array_values(array_filter([
            $this->gabbia_excel_mode === 'preview' ? 'Gabbia Excel mode' : null,
            $this->bancale_excel_mode === 'preview' ? 'Bancale Excel mode' : null,
            $this->legaccio_excel_mode === 'preview' ? 'Legaccio Excel mode' : null,
        ]));

        $this->lockPolicyActive = $settings->isLockPolicyActive();
        $this->lockedKeys = $settings->lockedKeys();

        $definitions = $settings->definitions();
        $this->fieldMeta = [
            'cutting_kerf_mm' => [
                'label' => 'Kerf di taglio (mm)',
                'help' => 'Spessore lama usato negli optimizer di taglio.',
                'env' => (string) ($definitions['cutting_kerf_mm']['env'] ?? ''),
            ],
            'gabbia_excel_mode' => [
                'label' => 'Gabbia Excel mode',
                'help' => 'Strategia di attivazione dei pezzi Excel per categoria gabbia.',
                'env' => (string) ($definitions['gabbia_excel_mode']['env'] ?? ''),
            ],
            'cassa_optimizer_mode' => [
                'label' => 'Cassa optimizer mode',
                'help' => 'physical = piano di taglio reale, excel_strict = parita storica Excel, legacy = fallback 1D.',
                'env' => (string) ($definitions['cassa_optimizer_mode']['env'] ?? ''),
            ],
            'bancale_excel_mode' => [
                'label' => 'Bancale Excel mode',
                'help' => 'Strategia di attivazione dei pezzi Excel per categoria bancale.',
                'env' => (string) ($definitions['bancale_excel_mode']['env'] ?? ''),
            ],
            'legaccio_excel_mode' => [
                'label' => 'Legaccio Excel mode',
                'help' => 'Strategia di attivazione dei pezzi Excel per categoria legaccio.',
                'env' => (string) ($definitions['legaccio_excel_mode']['env'] ?? ''),
            ],
            'scrap_reusable_min_length_mm' => [
                'label' => 'Soglia scarto riutilizzabile (mm)',
                'help' => 'Lunghezza minima scarto per marcarlo come riutilizzabile.',
                'env' => (string) ($definitions['scrap_reusable_min_length_mm']['env'] ?? ''),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $savedValues
     * @return array<int, string>
     */
    private function criticalChangedKeys(array $savedValues): array
    {
        return array_values(array_intersect(array_keys($savedValues), self::CRITICAL_SETTING_KEYS));
    }

    private function ensureAdmin(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isAdmin()) {
            throw new HttpException(403, 'Non autorizzato.');
        }
    }
}
