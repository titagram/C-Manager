<?php

namespace App\Services\Production;

use App\Models\Costruzione;
use App\Models\Prodotto;
use App\Services\FormulaEngineRolloutService;

class ComponentRequirementsBuilder
{
    public function __construct(
        private readonly FormulaEngineRolloutService $formulaEngine
    ) {}

    /**
     * Builds normalized calculated component requirements (in mm) for the optimizer.
     *
     * @return array{
     *   pieces: array<int, array{
     *     id:int,
     *     description:string,
     *     length:float,
     *     quantity:int,
     *     width?:float,
     *     is_internal?:bool,
     *     allow_rotation?:bool
     *   }>,
     *   errors: array<int, string>
     * }
     */
    public function buildCalculatedPieces(
        Costruzione $costruzione,
        Prodotto $materiale,
        float $larghezzaCm,
        float $profonditaCm,
        float $altezzaCm,
        int $numeroPezzi,
        ?int $userId = null
    ): array {
        $piecesToPack = [];
        $formulaErrors = [];

        // Canonical variables in mm: L, W, H, T
        $L = $larghezzaCm * 10;
        $W = $profonditaCm * 10;
        $H = $altezzaCm * 10;
        $T = (float) ($materiale->spessore_mm ?? 0);

        $formulaVariables = $this->buildFormulaVariables($L, $W, $H, $T);
        $formulaRolloutKey = $this->buildFormulaRolloutKey(
            userId: $userId,
            costruzioneId: $costruzione->id,
            materialeId: $materiale->id
        );

        foreach ($costruzione->componenti as $componente) {
            if (!$componente->calcolato) {
                continue;
            }

            $lengthFormula = $this->prepareFormula($componente->formula_lunghezza);
            $widthFormula = $this->prepareFormula($componente->formula_larghezza);
            $qtyFormula = $this->prepareFormula($componente->formula_quantita);

            try {
                $formulaContext = [
                    'rollout_key' => $formulaRolloutKey,
                    'component_id' => $componente->id,
                    'component_name' => $componente->nome,
                ];

                $lengthMm = $this->evaluateFormula(
                    $lengthFormula,
                    $formulaVariables,
                    array_merge($formulaContext, ['formula_type' => 'length'])
                );

                $widthMm = null;
                if (!empty(trim((string) $componente->formula_larghezza))) {
                    $widthMm = $this->evaluateFormula(
                        $widthFormula,
                        $formulaVariables,
                        array_merge($formulaContext, ['formula_type' => 'width'])
                    );
                }

                $quantityPerUnit = $this->evaluateFormula(
                    $qtyFormula,
                    $formulaVariables,
                    array_merge($formulaContext, ['formula_type' => 'quantity'])
                );

                if ($lengthMm <= 0) {
                    throw new \InvalidArgumentException(
                        sprintf('formula_lunghezza deve produrre un valore > 0 (ottenuto %.4f).', $lengthMm)
                    );
                }

                if ($widthMm !== null && $widthMm <= 0) {
                    throw new \InvalidArgumentException(
                        sprintf('formula_larghezza deve produrre un valore > 0 (ottenuto %.4f).', $widthMm)
                    );
                }

                if ($quantityPerUnit <= 0) {
                    throw new \InvalidArgumentException(
                        sprintf('formula_quantita deve produrre un valore > 0 (ottenuto %.4f).', $quantityPerUnit)
                    );
                }

                if (!$this->isIntegerLike($quantityPerUnit)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'formula_quantita deve produrre un intero (ottenuto %.4f).',
                            $quantityPerUnit
                        )
                    );
                }

                $totalQuantityRaw = $quantityPerUnit * max(1, $numeroPezzi);
                if (!$this->isIntegerLike($totalQuantityRaw)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'quantita totale deve essere intera dopo moltiplicazione per numero_pezzi (ottenuto %.4f).',
                            $totalQuantityRaw
                        )
                    );
                }

                $totalQuantity = (int) round($totalQuantityRaw);
                if ($totalQuantity <= 0) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'quantita totale deve essere > 0 dopo moltiplicazione per numero_pezzi (ottenuto %d).',
                            $totalQuantity
                        )
                    );
                }

                $piece = [
                    'id' => $componente->id,
                    'description' => $componente->nome,
                    'length' => $lengthMm,
                    'quantity' => $totalQuantity,
                    'is_internal' => (bool) ($componente->is_internal ?? false),
                    'allow_rotation' => (bool) ($componente->allow_rotation ?? false),
                ];

                if ($widthMm !== null && $widthMm > 0) {
                    $piece['width'] = $widthMm;
                }

                $piecesToPack[] = $piece;
            } catch (\Throwable $e) {
                $formulaErrors[] = sprintf(
                    'Componente "%s": %s',
                    $componente->nome,
                    $e->getMessage()
                );
            }
        }

        return [
            'pieces' => $piecesToPack,
            'errors' => $formulaErrors,
        ];
    }

    private function isIntegerLike(float $value): bool
    {
        return abs($value - round($value)) < 0.000001;
    }

    private function prepareFormula(?string $formula): string
    {
        if (empty($formula)) {
            return '0';
        }

        // Support legacy placeholders like $L, $P, etc. while keeping expression intact.
        return preg_replace('/\$(?=[A-Za-z_])/', '', trim($formula)) ?: '0';
    }

    /**
     * @param array<string, float|int> $variables
     * @param array<string, mixed> $context
     */
    private function evaluateFormula(string $formula, array $variables = [], array $context = []): float
    {
        return $this->formulaEngine->evaluate($formula, $variables, $context);
    }

    /**
     * @return array<string, float>
     */
    private function buildFormulaVariables(float $L, float $W, float $H, float $T): array
    {
        return [
            // Canonical names
            'L' => $L,
            'W' => $W,
            'H' => $H,
            'T' => $T,
            // Legacy aliases
            'P' => $W,
            'A' => $H,
            'S' => $T,
        ];
    }

    private function buildFormulaRolloutKey(?int $userId, int $costruzioneId, int $materialeId): string
    {
        return implode('|', [
            'formula-engine',
            'user:' . (string) ($userId ?? 0),
            'costruzione:' . (string) $costruzioneId,
            'materiale:' . (string) $materialeId,
        ]);
    }
}
