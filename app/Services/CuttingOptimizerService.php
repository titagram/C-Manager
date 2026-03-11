<?php

namespace App\Services;

use InvalidArgumentException;

class CuttingOptimizerService
{
    /**
     * Optimize material cutting using Best-Fit Decreasing algorithm.
     *
     * This method calculates how many pieces can be cut from boards (assi)
     * while minimizing waste. It considers saw blade kerf (larghezza lama)
     * when calculating fits.
     *
     * @param float $lunghezzaAsseDisponibile Length of available board in mm
     * @param float $lunghezzaPezzoRichiesto Length of required piece in mm
     * @param int $quantitaPezziRichiesti Number of pieces required
     * @param float $larghezzaLama Saw blade kerf in mm (default: 0)
     * @return array{
     *     pezzi_per_asse: int,
     *     assi_necessarie: int,
     *     scarto_per_asse_mm: float,
     *     scarto_totale_mm: float,
     *     scarto_percentuale: float
     * }
     * @throws InvalidArgumentException
     */
    public function optimize(
        float $lunghezzaAsseDisponibile,
        float $lunghezzaPezzoRichiesto,
        int $quantitaPezziRichiesti,
        float $larghezzaLama = 0
    ): array {
        // Validate inputs
        $this->validateInputs(
            $lunghezzaAsseDisponibile,
            $lunghezzaPezzoRichiesto,
            $quantitaPezziRichiesti,
            $larghezzaLama
        );

        // Calculate how many pieces fit per board
        $pezziPerAsse = $this->calculatePiecesPerBoard(
            $lunghezzaAsseDisponibile,
            $lunghezzaPezzoRichiesto,
            $larghezzaLama
        );

        // Calculate how many boards are needed
        $assiNecessarie = (int) ceil($quantitaPezziRichiesti / $pezziPerAsse);

        // Calculate waste per board
        $scartoPerAsseMm = $this->calculateWastePerBoard(
            $lunghezzaAsseDisponibile,
            $lunghezzaPezzoRichiesto,
            $pezziPerAsse,
            $larghezzaLama
        );

        // Calculate total waste
        $scartoTotaleMm = $scartoPerAsseMm * $assiNecessarie;

        // Calculate waste percentage
        $scartoPercentuale = ($scartoPerAsseMm / $lunghezzaAsseDisponibile) * 100;

        return [
            'pezzi_per_asse' => $pezziPerAsse,
            'assi_necessarie' => $assiNecessarie,
            'scarto_per_asse_mm' => $scartoPerAsseMm,
            'scarto_totale_mm' => $scartoTotaleMm,
            'scarto_percentuale' => $scartoPercentuale,
        ];
    }

    /**
     * Validate input parameters.
     *
     * @param float $lunghezzaAsseDisponibile
     * @param float $lunghezzaPezzoRichiesto
     * @param int $quantitaPezziRichiesti
     * @param float $larghezzaLama
     * @throws InvalidArgumentException
     */
    private function validateInputs(
        float $lunghezzaAsseDisponibile,
        float $lunghezzaPezzoRichiesto,
        int $quantitaPezziRichiesti,
        float $larghezzaLama
    ): void {
        if ($lunghezzaAsseDisponibile <= 0) {
            throw new InvalidArgumentException(
                'La lunghezza dell\'asse deve essere maggiore di zero'
            );
        }

        if ($lunghezzaPezzoRichiesto <= 0) {
            throw new InvalidArgumentException(
                'La lunghezza del pezzo deve essere maggiore di zero'
            );
        }

        if ($quantitaPezziRichiesti <= 0) {
            throw new InvalidArgumentException(
                'La quantità di pezzi deve essere maggiore di zero'
            );
        }

        if ($larghezzaLama < 0) {
            throw new InvalidArgumentException(
                'La larghezza della lama non può essere negativa'
            );
        }

        if ($lunghezzaPezzoRichiesto > $lunghezzaAsseDisponibile) {
            throw new InvalidArgumentException(
                sprintf(
                    'Il pezzo richiesto (%.0fmm) è più lungo dell\'asse disponibile (%.0fmm)',
                    $lunghezzaPezzoRichiesto,
                    $lunghezzaAsseDisponibile
                )
            );
        }

        // Check if even one piece with kerf can fit
        if ($lunghezzaPezzoRichiesto + $larghezzaLama > $lunghezzaAsseDisponibile) {
            throw new InvalidArgumentException(
                sprintf(
                    'Il pezzo richiesto con larghezza lama (%.0fmm) supera la lunghezza dell\'asse (%.0fmm)',
                    $lunghezzaPezzoRichiesto + $larghezzaLama,
                    $lunghezzaAsseDisponibile
                )
            );
        }
    }

    /**
     * Calculate how many pieces can fit on one board.
     *
     * Algorithm:
     * - First piece takes: lunghezzaPezzoRichiesto
     * - Each subsequent piece takes: lunghezzaPezzoRichiesto + larghezzaLama
     * - Continue until board length is exceeded
     *
     * @param float $lunghezzaAsseDisponibile
     * @param float $lunghezzaPezzoRichiesto
     * @param float $larghezzaLama
     * @return int
     */
    private function calculatePiecesPerBoard(
        float $lunghezzaAsseDisponibile,
        float $lunghezzaPezzoRichiesto,
        float $larghezzaLama
    ): int {
        if ($larghezzaLama == 0) {
            // Simple case: no kerf to consider
            return (int) floor($lunghezzaAsseDisponibile / $lunghezzaPezzoRichiesto);
        }

        // With kerf: first piece + (n-1) * (piece + kerf) <= board length
        // Rearranging: piece + (n-1) * piece + (n-1) * kerf <= board
        //              n * piece + (n-1) * kerf <= board
        //              n * (piece + kerf) - kerf <= board
        //              n <= (board + kerf) / (piece + kerf)

        $pezziPerAsse = (int) floor(
            ($lunghezzaAsseDisponibile + $larghezzaLama) /
            ($lunghezzaPezzoRichiesto + $larghezzaLama)
        );

        return max(1, $pezziPerAsse); // At least 1 piece should fit (validation ensures this)
    }

    /**
     * Calculate waste (scarto) per board in mm.
     *
     * @param float $lunghezzaAsseDisponibile
     * @param float $lunghezzaPezzoRichiesto
     * @param int $pezziPerAsse
     * @param float $larghezzaLama
     * @return float
     */
    private function calculateWastePerBoard(
        float $lunghezzaAsseDisponibile,
        float $lunghezzaPezzoRichiesto,
        int $pezziPerAsse,
        float $larghezzaLama
    ): float {
        // Calculate total material used
        // Material used = (pieces * piece_length) + ((pieces - 1) * kerf)
        $materialUsed = ($pezziPerAsse * $lunghezzaPezzoRichiesto) +
                       (($pezziPerAsse - 1) * $larghezzaLama);

        // Waste is what's left
        $scarto = $lunghezzaAsseDisponibile - $materialUsed;

        return round($scarto, 2);
    }

    /**
     * Get standard board lengths commonly used in Italy.
     *
     * @return array<int>
     */
    public static function getStandardBoardLengths(): array
    {
        return [3000, 4000, 6000];
    }

    /**
     * Get typical saw blade kerf widths in mm.
     *
     * @return array{min: float, max: float, typical: float}
     */
    public static function getTypicalKerfWidths(): array
    {
        return [
            'min' => 3.0,
            'max' => 5.0,
            'typical' => 4.0,
        ];
    }

    /**
     * Recommend optimal board length for given piece requirements.
     *
     * This method finds the standard board length that minimizes waste
     * while meeting the quantity requirements.
     *
     * @param float $lunghezzaPezzoRichiesto
     * @param int $quantitaPezziRichiesti
     * @param float $larghezzaLama
     * @return array{board_length: int, optimization_result: array}
     */
    public function recommendOptimalBoardLength(
        float $lunghezzaPezzoRichiesto,
        int $quantitaPezziRichiesti,
        float $larghezzaLama = 0
    ): array {
        $standardLengths = self::getStandardBoardLengths();
        $bestOption = null;
        $lowestWastePercentage = 100;

        foreach ($standardLengths as $boardLength) {
            // Skip if piece doesn't fit
            if ($lunghezzaPezzoRichiesto > $boardLength) {
                continue;
            }

            try {
                $result = $this->optimize(
                    $boardLength,
                    $lunghezzaPezzoRichiesto,
                    $quantitaPezziRichiesti,
                    $larghezzaLama
                );

                if ($result['scarto_percentuale'] < $lowestWastePercentage) {
                    $lowestWastePercentage = $result['scarto_percentuale'];
                    $bestOption = [
                        'board_length' => $boardLength,
                        'optimization_result' => $result,
                    ];
                }
            } catch (InvalidArgumentException $e) {
                // Skip this board length if it causes validation errors
                continue;
            }
        }

        if ($bestOption === null) {
            throw new InvalidArgumentException(
                'Nessuna lunghezza di asse standard è adatta per questo pezzo'
            );
        }

        return $bestOption;
    }
}
