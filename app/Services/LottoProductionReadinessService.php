<?php

namespace App\Services;

use App\Models\ComponenteCostruzione;
use App\Models\LottoProduzione;
use App\Models\Ordine;

class LottoProductionReadinessService
{
    /**
     * @return array{
     *   ready: bool,
     *   message: string,
     *   reasons: array<int, string>,
     *   missing_manual_components: array<int, string>,
     *   requires_calculated_materials: bool
     * }
     */
    public function evaluate(LottoProduzione $lotto): array
    {
        $lotto->loadMissing([
            'costruzione.componenti',
            'materialiUsati',
            'componentiManuali',
        ]);

        $reasons = [];
        $missingManualComponents = [];

        if (! $lotto->hasTechnicalDefinition()) {
            $reasons[] = 'manca la definizione tecnica del lotto';
        }

        $componenti = $lotto->costruzione?->componenti ?? collect();

        $requiresCalculatedMaterials = $componenti->contains(
            fn(ComponenteCostruzione $componente) => $this->isCalculated($componente)
        );

        if ($requiresCalculatedMaterials && $lotto->materialiUsati->isEmpty()) {
            $reasons[] = 'mancano i materiali calcolati';
        }

        $componentiManualiRichiesti = $componenti->filter(
            fn(ComponenteCostruzione $componente) => $this->isManual($componente)
        );

        if ($componentiManualiRichiesti->isNotEmpty()) {
            $righeManuali = $lotto->componentiManuali->keyBy('componente_costruzione_id');

            foreach ($componentiManualiRichiesti as $componente) {
                $riga = $righeManuali->get($componente->id);

                if (!$riga || !$riga->prodotto_id || (float) $riga->quantita <= 0) {
                    $missingManualComponents[] = $componente->nome;
                }
            }
        }

        if ($missingManualComponents !== []) {
            $reasons[] = 'componenti manuali incompleti: ' . implode(', ', $missingManualComponents);
        }

        $ready = $reasons === [];

        return [
            'ready' => $ready,
            'message' => $ready
                ? 'Pronto per avvio produzione'
                : 'Lotto non pronto: ' . implode('; ', $reasons),
            'reasons' => $reasons,
            'missing_manual_components' => $missingManualComponents,
            'requires_calculated_materials' => $requiresCalculatedMaterials,
        ];
    }

    /**
     * @return array{
     *   ready: bool,
     *   total_lotti: int,
     *   lotti_pronti: int,
     *   lotti_non_pronti: int,
     *   issues: array<int, array{lotto_id:int, codice_lotto:string, message:string}>
     * }
     */
    public function evaluateOrder(Ordine $ordine): array
    {
        $ordine->loadMissing([
            'lottiProduzione.costruzione.componenti',
            'lottiProduzione.materialiUsati',
            'lottiProduzione.componentiManuali',
        ]);

        $totalLotti = $ordine->lottiProduzione->count();
        $lottiPronti = 0;
        $issues = [];

        foreach ($ordine->lottiProduzione as $lotto) {
            $result = $this->evaluate($lotto);

            if ($result['ready']) {
                $lottiPronti++;
                continue;
            }

            $issues[] = [
                'lotto_id' => $lotto->id,
                'codice_lotto' => $lotto->codice_lotto,
                'message' => $result['message'],
            ];
        }

        return [
            'ready' => $issues === [],
            'total_lotti' => $totalLotti,
            'lotti_pronti' => $lottiPronti,
            'lotti_non_pronti' => max(0, $totalLotti - $lottiPronti),
            'issues' => $issues,
        ];
    }

    public function assertReady(LottoProduzione $lotto): void
    {
        $result = $this->evaluate($lotto);

        if (!$result['ready']) {
            throw new \RuntimeException($result['message']);
        }
    }

    private function isCalculated(ComponenteCostruzione $componente): bool
    {
        if ($componente->tipo_dimensionamento) {
            return strtoupper($componente->tipo_dimensionamento) === 'CALCOLATO';
        }

        return (bool) $componente->calcolato;
    }

    private function isManual(ComponenteCostruzione $componente): bool
    {
        if ($componente->tipo_dimensionamento) {
            return strtoupper($componente->tipo_dimensionamento) === 'MANUALE';
        }

        return !(bool) $componente->calcolato;
    }
}
