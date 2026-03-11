<?php

namespace App\Services\Calcolo;

use App\Services\Calcolo\DTO\PreventivoInput;
use App\Services\Calcolo\DTO\PreventivoOutput;
use App\Services\Calcolo\DTO\RigaInput;
use App\Services\Calcolo\DTO\RigaOutput;

class PreventivoCalculator
{
    public const VERSION = '1.0.0';

    /**
     * Calcola un preventivo completo
     */
    public function calcola(PreventivoInput $input): PreventivoOutput
    {
        $righeOutput = [];
        $totaleMateriali = 0;

        foreach ($input->righe as $rigaInput) {
            $rigaOutput = $this->calcolaRiga($rigaInput);
            $righeOutput[] = $rigaOutput;
            $totaleMateriali += $rigaOutput->totale;
        }

        // Per ora le lavorazioni sono a zero (da implementare in futuro)
        $totaleLavorazioni = 0;

        return new PreventivoOutput(
            righe: $righeOutput,
            totaleMateriali: round($totaleMateriali, 2),
            totaleLavorazioni: round($totaleLavorazioni, 2),
            totale: round($totaleMateriali + $totaleLavorazioni, 2),
            engineVersion: self::VERSION,
        );
    }

    /**
     * Calcola una singola riga del preventivo
     */
    public function calcolaRiga(RigaInput $input): RigaOutput
    {
        $unita = strtolower($input->unitaMisura ?: 'mc');

        // Conversione in metri
        $lunghezza_m = $input->lunghezza_mm / 1000;
        $larghezza_m = $input->larghezza_mm / 1000;
        $spessore_m = $input->spessore_mm / 1000;

        // Superficie singolo pezzo (mq)
        $superficieSingola = $lunghezza_m * $larghezza_m;

        // Volume singolo pezzo (mc)
        $volumeSingolo = $superficieSingola * $spessore_m;

        // Totali per quantita
        $superficieTotale = $superficieSingola * $input->quantita;
        $volumeTotale = $volumeSingolo * $input->quantita;

        [$materialeNetto, $materialeLordo] = match ($unita) {
            'pz' => [
                (float) $input->quantita,
                (float) $input->quantita,
            ],
            'kg' => [
                (float) $input->quantita,
                $this->calcolaMaterialeConScarto((float) $input->quantita, $input->coefficienteScarto),
            ],
            'ml' => [
                (float) ($lunghezza_m * $input->quantita),
                $this->calcolaMaterialeConScarto((float) ($lunghezza_m * $input->quantita), $input->coefficienteScarto),
            ],
            'mq' => [
                (float) $superficieTotale,
                $this->calcolaMaterialeConScarto((float) $superficieTotale, $input->coefficienteScarto),
            ],
            default => [
                (float) $volumeTotale,
                $this->calcolaMaterialeConScarto((float) $volumeTotale, $input->coefficienteScarto),
            ],
        };

        // Calcolo totale riga
        $totale = $materialeLordo * $input->prezzoUnitario;

        return new RigaOutput(
            superficie_mq: round($superficieTotale, 6),
            volume_mc: round($volumeTotale, 6),
            materiale_netto: round($materialeNetto, 4),
            materiale_lordo: $materialeLordo,
            totale: round($totale, 2),
        );
    }

    /**
     * Calcola solo superficie e volume (per preview rapida)
     */
    public function calcolaDimensioni(
        float $lunghezza_mm,
        float $larghezza_mm,
        float $spessore_mm,
        int $quantita
    ): array {
        $lunghezza_m = $lunghezza_mm / 1000;
        $larghezza_m = $larghezza_mm / 1000;
        $spessore_m = $spessore_mm / 1000;

        $superficie = $lunghezza_m * $larghezza_m * $quantita;
        $volume = $superficie * $spessore_m;

        return [
            'superficie_mq' => round($superficie, 6),
            'volume_mc' => round($volume, 6),
        ];
    }

    /**
     * Calcola il materiale necessario con scarto
     */
    public function calcolaMaterialeConScarto(
        float $materialeNetto,
        float $coefficienteScarto
    ): float {
        $lordo = $materialeNetto * (1 + $coefficienteScarto);

        // Guard against floating-point overshoot (e.g. 3.3 represented as 3.3000000000000003)
        // which would incorrectly round up to 3.301 when applying the ceil-at-3-decimals rule.
        return ceil(($lordo * 1000) - 1e-9) / 1000;
    }

    /**
     * Ottiene la versione del motore di calcolo
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }
}
