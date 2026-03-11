<?php

namespace App\Services\Production;

class CassaLegacyQuantitiesCalculator
{
    public function __construct(
        private readonly GabbiaLegacyHeightQuantityTable $heightTable
    ) {}

    /**
     * @return array<string, int>
     */
    public function calculate(string $routine, float $Lcm, float $Wcm, float $Hcm): array
    {
        $routine = strtolower(trim($routine));

        return match ($routine) {
            'cassasp25' => [
                'D8' => $this->sp25WidthQuantity($Wcm),
                'D9' => $this->heightQuantity($Hcm),
                'D10' => $this->heightQuantity($Hcm),
                'D11' => $Lcm >= 200 ? 4 : 3,
                'D12' => $Lcm >= 200 ? 16 : 14,
                'D13' => $Lcm >= 200 ? 4 : 3,
            ],
            'cassasp25fondo40' => [
                'D8' => $this->sp25Fondo40WidthQuantity($Wcm),
                'D9' => $this->heightQuantity($Hcm),
                'D10' => $this->heightQuantity($Hcm),
                'D11' => $Lcm >= 200 ? 4 : 3,
                'D12' => $Lcm >= 200 ? 16 : 14,
                'D13' => $Lcm >= 200 ? 4 : 3,
                'D14' => $this->sp25Fondo40WidthQuantity($Wcm),
            ],
            default => throw new \InvalidArgumentException("Routine cassa non supportata dal calcolatore legacy: {$routine}"),
        };
    }

    public function observedWorkbookCase(string $sheetLabel): ?string
    {
        $label = strtolower(trim($sheetLabel));

        return match ($label) {
            'cassa sp 25 fondo 40' => 'cassasp25',
            default => null,
        };
    }

    private function sp25WidthQuantity(float $widthCm): int
    {
        return max(0, (int) round((($widthCm / 10) + 0.5) * 2, 0));
    }

    private function sp25Fondo40WidthQuantity(float $widthCm): int
    {
        if ($widthCm < 10) {
            return max(0, (int) round($widthCm + 0.5, 0));
        }

        return max(0, (int) ceil($widthCm / 10));
    }

    private function heightQuantity(float $heightCm): int
    {
        $raw = ($heightCm / 10) * 2;

        if (abs(fmod($raw, 1.0) - 0.4) < 0.0001) {
            return max(0, (int) round($raw + 0.6, 0));
        }

        if (abs(fmod($raw, 1.0)) < 0.0001) {
            return max(0, (int) round($raw, 0));
        }

        return $this->heightTable->qtyFromHeightCm($heightCm);
    }
}
