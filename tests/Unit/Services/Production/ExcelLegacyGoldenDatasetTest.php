<?php

namespace Tests\Unit\Services\Production;

use Tests\TestCase;

class ExcelLegacyGoldenDatasetTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function goldenCaseProvider(): array
    {
        $fixture = self::loadFixture();
        $cases = $fixture['cases'] ?? [];

        $dataset = [];
        foreach ($cases as $case) {
            if (!is_array($case)) {
                continue;
            }

            $id = (string) ($case['id'] ?? 'case');
            $dataset[$id] = [$case];
        }

        return $dataset;
    }

    public function test_fixture_contains_expected_number_of_real_excel_cases(): void
    {
        $fixture = self::loadFixture();
        $cases = is_array($fixture['cases'] ?? null) ? $fixture['cases'] : [];

        // 4 schede standard + 1 scheda legacci (tutte da excel_analysis reali).
        $this->assertGreaterThanOrEqual(5, count($cases));
    }

    /**
     * @dataProvider goldenCaseProvider
     *
     * @param array<string, mixed> $case
     */
    public function test_legacy_excel_rows_respect_formula_contract(array $case): void
    {
        $rows = is_array($case['rows'] ?? null) ? $case['rows'] : [];
        $expected = is_array($case['expected'] ?? null) ? $case['expected'] : [];

        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('volume_total_m3', $expected);

        $sumFromRows = 0.0;

        foreach ($rows as $row) {
            $a = (float) ($row['A_raw'] ?? 0.0);
            $b = (float) ($row['B_raw'] ?? 0.0);
            $c = (float) ($row['C_raw'] ?? 0.0);
            $d = (float) ($row['D_raw'] ?? 0.0);
            $e = (float) ($row['E_volume_m3'] ?? 0.0);

            $computed = ($a * $b * $c * $d) / 10000000.0;

            $this->assertEqualsWithDelta(
                $e,
                $computed,
                0.000001,
                sprintf(
                    'Formula mismatch in case %s row %s',
                    (string) ($case['id'] ?? 'unknown'),
                    (string) ($row['row'] ?? '?')
                )
            );

            $sumFromRows += $e;
        }

        $expectedTotal = (float) ($expected['volume_total_m3'] ?? 0.0);
        $this->assertEqualsWithDelta($expectedTotal, $sumFromRows, 0.000001);

        $pricePerMc = $expected['price_per_m3'] ?? null;
        $priceTotal = $expected['price_total'] ?? null;
        if (is_numeric($pricePerMc) && is_numeric($priceTotal)) {
            $this->assertEqualsWithDelta(
                (float) $priceTotal,
                $expectedTotal * (float) $pricePerMc,
                0.0001
            );
        }

        $weightPerMc = $expected['weight_kg_per_m3'] ?? null;
        $weightTotal = $expected['weight_total_kg'] ?? null;
        if (is_numeric($weightPerMc) && is_numeric($weightTotal)) {
            $this->assertEqualsWithDelta(
                (float) $weightTotal,
                $expectedTotal * (float) $weightPerMc,
                0.0001
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadFixture(): array
    {
        $path = dirname(__DIR__, 3).'/Fixtures/production/excel_legacy_golden_cases.json';
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Impossibile leggere fixture golden: {$path}");
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException("Fixture golden non valida: {$path}");
        }

        return $decoded;
    }
}
