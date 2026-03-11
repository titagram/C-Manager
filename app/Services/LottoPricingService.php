<?php

namespace App\Services;

use App\Enums\LottoPricingMode;

class LottoPricingService
{
    /**
     * @return array{
     *   pricing_mode: string,
     *   tariffa_mc: float|null,
     *   ricarico_percentuale: float,
     *   prezzo_calcolato: float,
     *   prezzo_finale: float,
     *   pricing_snapshot: array<string, float|string|null>
     * }
     */
    public function calcola(
        float $volumeTotaleMc,
        float $costoTotale,
        string $pricingMode,
        ?float $tariffaMc,
        ?float $ricaricoPercentuale,
        ?float $prezzoFinaleOverride
    ): array {
        $mode = LottoPricingMode::tryFrom($pricingMode) ?? LottoPricingMode::TARIFFA_MC;
        $volumeTotaleMc = round(max(0, $volumeTotaleMc), 6);
        $costoTotale = round(max(0, $costoTotale), 2);
        $tariffaMc = $tariffaMc !== null ? round(max(0, $tariffaMc), 2) : null;
        $ricarico = round(max(0, (float) ($ricaricoPercentuale ?? 0)), 2);

        $prezzoCalcolato = match ($mode) {
            LottoPricingMode::TARIFFA_MC => round($volumeTotaleMc * (float) ($tariffaMc ?? 0), 2),
            LottoPricingMode::COSTO_RICARICO => round($costoTotale * (1 + ($ricarico / 100)), 2),
        };

        $prezzoFinale = $prezzoFinaleOverride !== null
            ? round(max(0, (float) $prezzoFinaleOverride), 2)
            : $prezzoCalcolato;

        return [
            'pricing_mode' => $mode->value,
            'tariffa_mc' => $tariffaMc,
            'ricarico_percentuale' => $ricarico,
            'prezzo_calcolato' => $prezzoCalcolato,
            'prezzo_finale' => $prezzoFinale,
            'pricing_snapshot' => [
                'mode' => $mode->value,
                'volume_totale_mc' => $volumeTotaleMc,
                'costo_totale' => $costoTotale,
                'tariffa_mc' => $tariffaMc,
                'ricarico_percentuale' => $ricarico,
                'prezzo_finale_override' => $prezzoFinaleOverride !== null
                    ? round(max(0, (float) $prezzoFinaleOverride), 2)
                    : null,
                'prezzo_calcolato' => $prezzoCalcolato,
                'prezzo_finale' => $prezzoFinale,
            ],
        ];
    }
}
