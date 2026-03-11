<?php

namespace App\Livewire\Reports;

use App\Services\FitokReportService;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class FitokReport extends Component
{
    use WithPagination;

    public string $dataInizio;

    public string $dataFine;

    public string $periodo = 'mese_corrente';

    public string $filtroLottoMateriale = '';

    public string $filtroLottoProduzione = '';

    protected FitokReportService $fitokService;

    public function boot(FitokReportService $fitokService): void
    {
        $this->fitokService = $fitokService;
    }

    public function mount(): void
    {
        $this->applicaPeriodo('mese_corrente');
    }

    public function updatedPeriodo(): void
    {
        $this->applicaPeriodo($this->periodo);
    }

    public function applicaPeriodo(string $periodo): void
    {
        $this->periodo = $periodo;

        switch ($periodo) {
            case 'oggi':
                $this->dataInizio = now()->format('Y-m-d');
                $this->dataFine = now()->format('Y-m-d');
                break;
            case 'settimana_corrente':
                $this->dataInizio = now()->startOfWeek()->format('Y-m-d');
                $this->dataFine = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'mese_corrente':
                $this->dataInizio = now()->startOfMonth()->format('Y-m-d');
                $this->dataFine = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'trimestre_corrente':
                $this->dataInizio = now()->startOfQuarter()->format('Y-m-d');
                $this->dataFine = now()->endOfQuarter()->format('Y-m-d');
                break;
            case 'anno_corrente':
                $this->dataInizio = now()->startOfYear()->format('Y-m-d');
                $this->dataFine = now()->endOfYear()->format('Y-m-d');
                break;
            case 'mese_precedente':
                $this->dataInizio = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dataFine = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'anno_precedente':
                $this->dataInizio = now()->subYear()->startOfYear()->format('Y-m-d');
                $this->dataFine = now()->subYear()->endOfYear()->format('Y-m-d');
                break;
            case 'personalizzato':
                // Keep current dates
                break;
        }
    }

    public function updatedDataInizio(): void
    {
        $this->periodo = 'personalizzato';
    }

    public function updatedDataFine(): void
    {
        $this->periodo = 'personalizzato';
    }

    public function exportPdf()
    {
        return redirect()->route('fitok.export.pdf', [
            'data_inizio' => $this->dataInizio,
            'data_fine' => $this->dataFine,
        ]);
    }

    public function exportExcel()
    {
        return redirect()->route('fitok.export.excel', [
            'data_inizio' => $this->dataInizio,
            'data_fine' => $this->dataFine,
        ]);
    }

    public function render()
    {
        $dataInizio = Carbon::parse($this->dataInizio)->startOfDay();
        $dataFine = Carbon::parse($this->dataFine)->endOfDay();

        $registro = $this->fitokService->getRegistro($dataInizio, $dataFine);
        $movimenti = $this->decorateMovimenti(
            $this->applyLottoFilters($registro)
        );

        $lottoMaterialeSuggestions = $registro
            ->pluck('codice_lotto')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->sort()
            ->values();
        $lottoProduzioneSuggestions = $registro
            ->pluck('lotto_produzione_codice')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->sort()
            ->values();

        $riepilogo = $this->buildRiepilogoFromMovimenti($movimenti);
        $perProdotto = $this->buildPerProdottoFromMovimenti($movimenti);
        $mappaDestinazioniFitok = $this->fitokService->buildFitokDestinationMapFromMovimenti($movimenti);
        $riepilogoProduzione = $this->fitokService->getRiepilogoFitokProduzione($dataInizio, $dataFine);
        $lottiInScadenza = $this->fitokService->getLottiInScadenza(30);

        return view('livewire.reports.fitok-report', [
            'movimenti' => $movimenti,
            'riepilogo' => $riepilogo,
            'perProdotto' => $perProdotto,
            'mappaDestinazioniFitok' => $mappaDestinazioniFitok,
            'riepilogoProduzione' => $riepilogoProduzione,
            'lottoMaterialeSuggestions' => $lottoMaterialeSuggestions,
            'lottoProduzioneSuggestions' => $lottoProduzioneSuggestions,
            'lottiInScadenza' => $lottiInScadenza,
        ]);
    }

    private function applyLottoFilters(Collection $movimenti): Collection
    {
        $filtered = $movimenti;

        if (trim($this->filtroLottoMateriale) !== '') {
            $needle = mb_strtolower(trim($this->filtroLottoMateriale));
            $filtered = $filtered
                ->filter(function ($movimento) use ($needle) {
                    $codiceLotto = mb_strtolower((string) ($movimento->codice_lotto ?? ''));

                    return str_contains($codiceLotto, $needle);
                })
                ->values();
        }

        if (trim($this->filtroLottoProduzione) !== '') {
            $needle = mb_strtolower(trim($this->filtroLottoProduzione));
            $filtered = $filtered
                ->filter(function ($movimento) use ($needle) {
                    $codiceLottoProduzione = mb_strtolower((string) ($movimento->lotto_produzione_codice ?? ''));

                    return str_contains($codiceLottoProduzione, $needle);
                })
                ->values();
        }

        return $filtered;
    }

    private function buildRiepilogoFromMovimenti(Collection $movimenti): array
    {
        return [
            'carichi' => (float) $movimenti->filter(
                fn ($movimento) => $this->movementTypeValue($movimento->tipo ?? null) === 'carico'
            )->sum('quantita'),
            'scarichi' => (float) $movimenti->filter(
                fn ($movimento) => $this->movementTypeValue($movimento->tipo ?? null) === 'scarico'
            )->sum('quantita'),
            'rettifiche_positive' => (float) $movimenti->filter(
                fn ($movimento) => $this->movementTypeValue($movimento->tipo ?? null) === 'rettifica_positiva'
            )->sum('quantita'),
            'rettifiche_negative' => (float) $movimenti->filter(
                fn ($movimento) => $this->movementTypeValue($movimento->tipo ?? null) === 'rettifica_negativa'
            )->sum('quantita'),
            'saldo' => (float) $movimenti->sum(function ($m) {
                return in_array($this->movementTypeValue($m->tipo ?? null), ['carico', 'rettifica_positiva'], true)
                    ? $m->quantita
                    : -$m->quantita;
            }),
        ];
    }

    private function buildPerProdottoFromMovimenti(Collection $movimenti): Collection
    {
        return $movimenti
            ->groupBy('prodotto_codice')
            ->map(function (Collection $items, string $codice) {
                $primo = $items->first();

                return [
                    'codice' => $codice,
                    'nome' => $primo->prodotto_nome,
                    'unita_misura' => $primo->unita_misura,
                    'totale_carichi' => (float) $items->filter(
                        fn ($item) => $this->movementTypeValue($item->tipo ?? null) === 'carico'
                    )->sum('quantita'),
                    'totale_scarichi' => (float) $items->filter(
                        fn ($item) => $this->movementTypeValue($item->tipo ?? null) === 'scarico'
                    )->sum('quantita'),
                    'movimenti_count' => $items->count(),
                ];
            });
    }

    private function decorateMovimenti(Collection $movimenti): Collection
    {
        return $movimenti->map(function ($movimento) {
            $tipoValue = $this->movementTypeValue($movimento->tipo ?? null);
            $movimento->tipo_value = $tipoValue;
            $movimento->tipo_label = $this->movementTypeLabel($tipoValue);
            $movimento->tipo_color = $this->movementTypeColor($tipoValue);

            return $movimento;
        });
    }

    private function movementTypeValue(mixed $tipo): ?string
    {
        if ($tipo instanceof BackedEnum) {
            return $tipo->value;
        }

        if (is_string($tipo) && $tipo !== '') {
            return $tipo;
        }

        return null;
    }

    private function movementTypeLabel(?string $tipo): string
    {
        return match ($tipo) {
            'carico' => 'Carico',
            'scarico' => 'Scarico',
            'rettifica_positiva' => 'Rett. +',
            'rettifica_negativa' => 'Rett. -',
            default => 'Sconosciuto',
        };
    }

    private function movementTypeColor(?string $tipo): string
    {
        return match ($tipo) {
            'carico' => 'green',
            'scarico' => 'red',
            'rettifica_positiva' => 'blue',
            'rettifica_negativa' => 'orange',
            default => 'gray',
        };
    }
}
