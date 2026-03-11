<?php

namespace App\Support;

use App\Enums\StatoConsumoMateriale;
use App\Enums\StatoPreventivo;
use App\Models\Bom;
use App\Models\ConsumoMateriale;
use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Preventivo;
use Illuminate\Support\Collection;

class ProductionFlowStepper
{
    /**
     * @return array{steps: array<int, array<string, mixed>>, context_label: string}
     */
    public function forPreventivo(Preventivo $preventivo): array
    {
        return $this->build(preventivo: $preventivo);
    }

    /**
     * @return array{steps: array<int, array<string, mixed>>, context_label: string}
     */
    public function forOrdine(Ordine $ordine): array
    {
        return $this->build(ordine: $ordine);
    }

    /**
     * @return array{steps: array<int, array<string, mixed>>, context_label: string}
     */
    public function forLotto(LottoProduzione $lotto): array
    {
        return $this->build(lotto: $lotto);
    }

    /**
     * @return array{steps: array<int, array<string, mixed>>, context_label: string}
     */
    private function build(
        ?Preventivo $preventivo = null,
        ?Ordine $ordine = null,
        ?LottoProduzione $lotto = null
    ): array {
        $isLottoContext = $lotto?->exists ?? false;

        if ($lotto?->exists) {
            $lotto->loadMissing('ordine.preventivo', 'preventivo');
            $ordine ??= $lotto->ordine;
            $preventivo ??= $lotto->preventivo ?: $lotto->ordine?->preventivo;
        }

        if ($ordine?->exists) {
            $ordine->loadMissing('preventivo', 'lottiProduzione');
            $preventivo ??= $ordine->preventivo;
        }

        if ($preventivo?->exists) {
            $preventivo->loadMissing('ordine', 'lottoProduzione', 'righe');
            if (! $isLottoContext) {
                $ordine ??= $preventivo->ordine;
            }
        }

        $lotti = $this->resolveLotti($preventivo, $ordine, $lotto);
        $lottiOperativi = $lotti->filter(fn (LottoProduzione $item) => ! $item->isPlaceholderBozza())->values();
        $bom = $this->resolveBom($ordine, $lotti);
        $magazzinoStats = $this->resolveMagazzinoStats($lotti);

        $steps = [
            [
                'key' => 'preventivo',
                'label' => 'Preventivo',
                'optional' => ! $preventivo,
                'done' => $preventivo?->stato === StatoPreventivo::ACCETTATO,
                'meta' => $preventivo
                    ? "{$preventivo->numero} · {$preventivo->stato->label()}"
                    : 'Non previsto in questo flusso',
                'url' => $preventivo ? $this->adminOnlyUrl(route('preventivi.show', $preventivo)) : null,
            ],
            [
                'key' => 'ordine',
                'label' => 'Ordine',
                'optional' => false,
                'done' => (bool) $ordine,
                'meta' => $ordine
                    ? "{$ordine->numero} · {$ordine->stato->label()}"
                    : 'Da generare da preventivo accettato',
                'url' => $ordine ? $this->adminOnlyUrl(route('ordini.show', $ordine)) : null,
            ],
            [
                'key' => 'lotto',
                'label' => 'Lotto',
                'optional' => false,
                'done' => $lottiOperativi->isNotEmpty(),
                'meta' => $lotti->isNotEmpty()
                    ? $this->buildLottiMeta($lotti, $lottiOperativi)
                    : 'Da pianificare sull\'ordine',
                'url' => $this->resolveLottiUrl($lotti),
            ],
            [
                'key' => 'bom',
                'label' => 'BOM',
                'optional' => false,
                'done' => (bool) $bom,
                'meta' => $bom
                    ? "{$bom->codice} · Distinta generata"
                    : 'Distinta da generare',
                'url' => $bom ? route('bom.show', $bom) : route('bom.index'),
            ],
            [
                'key' => 'magazzino',
                'label' => 'Magazzino',
                'optional' => false,
                'done' => ($magazzinoStats['opzionato'] + $magazzinoStats['consumato']) > 0,
                'meta' => $this->buildMagazzinoMeta($magazzinoStats),
                'url' => route('magazzino.aggregato'),
            ],
        ];

        $steps = $this->resolveStatuses($steps);

        return [
            'steps' => $steps,
            'context_label' => $this->resolveContextLabel($preventivo, $ordine, $lotto),
        ];
    }

    /**
     * @return Collection<int, LottoProduzione>
     */
    private function resolveLotti(?Preventivo $preventivo, ?Ordine $ordine, ?LottoProduzione $lotto): Collection
    {
        if ($ordine) {
            return $ordine->lottiProduzione()->get();
        }

        if ($lotto) {
            return collect([$lotto]);
        }

        if (! $preventivo) {
            return collect();
        }

        $ids = $preventivo->righe()
            ->whereNotNull('lotto_produzione_id')
            ->pluck('lotto_produzione_id');

        if ($preventivo->lottoProduzione?->id) {
            $ids->push($preventivo->lottoProduzione->id);
        }

        $ids = $ids
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return LottoProduzione::query()
            ->whereIn('id', $ids->all())
            ->get();
    }

    private function resolveBom(?Ordine $ordine, Collection $lotti): ?Bom
    {
        if ($ordine) {
            return Bom::query()
                ->where('ordine_id', $ordine->id)
                ->latest('id')
                ->first();
        }

        if ($lotti->isEmpty()) {
            return null;
        }

        return Bom::query()
            ->whereIn('lotto_produzione_id', $lotti->pluck('id')->all())
            ->latest('id')
            ->first();
    }

    /**
     * @return array{opzionato: int, consumato: int, pianificato: int}
     */
    private function resolveMagazzinoStats(Collection $lotti): array
    {
        if ($lotti->isEmpty()) {
            return ['opzionato' => 0, 'consumato' => 0, 'pianificato' => 0];
        }

        $lottoIds = $lotti->pluck('id')->all();

        return [
            'opzionato' => ConsumoMateriale::query()
                ->whereIn('lotto_produzione_id', $lottoIds)
                ->where('stato', StatoConsumoMateriale::OPZIONATO->value)
                ->count(),
            'consumato' => ConsumoMateriale::query()
                ->whereIn('lotto_produzione_id', $lottoIds)
                ->where('stato', StatoConsumoMateriale::CONSUMATO->value)
                ->count(),
            'pianificato' => ConsumoMateriale::query()
                ->whereIn('lotto_produzione_id', $lottoIds)
                ->where('stato', StatoConsumoMateriale::PIANIFICATO->value)
                ->count(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return array<int, array<string, mixed>>
     */
    private function resolveStatuses(array $steps): array
    {
        $blockingIndex = null;

        foreach ($steps as $index => $step) {
            if (($step['optional'] ?? false) === true) {
                $steps[$index]['status'] = 'skipped';
                $steps[$index]['status_label'] = 'Non previsto';
                continue;
            }

            if (($step['done'] ?? false) === true) {
                $steps[$index]['status'] = 'completed';
                $steps[$index]['status_label'] = 'Completato';
                continue;
            }

            if ($blockingIndex === null) {
                $blockingIndex = $index;
                $steps[$index]['status'] = 'current';
                $steps[$index]['status_label'] = 'In corso';
                continue;
            }

            $steps[$index]['status'] = 'pending';
            $steps[$index]['status_label'] = 'In attesa';
        }

        if ($blockingIndex !== null) {
            foreach ($steps as $index => $step) {
                if ($index <= $blockingIndex) {
                    continue;
                }

                if (($step['optional'] ?? false) === true) {
                    continue;
                }

                if (($step['done'] ?? false) === true) {
                    $steps[$index]['status'] = 'inconsistent';
                    $steps[$index]['status_label'] = 'Fuori sequenza';
                }
            }
        }

        return $steps;
    }

    private function resolveLottiUrl(Collection $lotti): ?string
    {
        if ($lotti->isEmpty()) {
            return route('lotti.index');
        }

        if ($lotti->count() === 1) {
            return route('lotti.show', $lotti->first());
        }

        return route('lotti.index');
    }

    private function buildLottiMeta(Collection $lotti, Collection $lottiOperativi): string
    {
        if ($lottiOperativi->isEmpty()) {
            return "{$lotti->count()} bozza da completare tecnicamente";
        }

        $completati = $lotti->filter(function (LottoProduzione $lotto) {
            return in_array($lotto->stato->value, ['completato', 'annullato'], true);
        })->count();

        return "{$lottiOperativi->count()} lotto · {$completati} chiusi";
    }

    /**
     * @param  array{opzionato: int, consumato: int, pianificato: int}  $stats
     */
    private function buildMagazzinoMeta(array $stats): string
    {
        if (($stats['opzionato'] + $stats['consumato'] + $stats['pianificato']) === 0) {
            return 'Nessuna movimentazione associata';
        }

        return implode(' · ', [
            "Opzionati: {$stats['opzionato']}",
            "Consumati: {$stats['consumato']}",
            "Pianificati: {$stats['pianificato']}",
        ]);
    }

    private function resolveContextLabel(
        ?Preventivo $preventivo,
        ?Ordine $ordine,
        ?LottoProduzione $lotto
    ): string {
        if ($lotto) {
            return "Lotto {$lotto->codice_lotto}";
        }

        if ($ordine) {
            return "Ordine {$ordine->numero}";
        }

        if ($preventivo) {
            return "Preventivo {$preventivo->numero}";
        }

        return 'Nuovo flusso';
    }

    private function adminOnlyUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return auth()->user()?->isAdmin() ? $url : null;
    }
}
