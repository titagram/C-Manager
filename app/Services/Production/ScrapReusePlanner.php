<?php

namespace App\Services\Production;

use App\Models\Prodotto;
use App\Models\Scarto;
use Illuminate\Support\Collection;

class ScrapReusePlanner
{
    /**
     * @param  array<int, array{id?:int,description?:string,length?:float,quantity?:int,width?:float,is_internal?:bool|null,allow_rotation?:bool|null}>  $pieces
     * @return array{
     *   required_count:int,
     *   available_scraps_count:int,
     *   matched_count:int,
     *   used_scrap_ids:array<int,int>,
     *   used_piece_indexes:array<int,int>,
     *   matches:array<int,array<string,mixed>>,
     *   used:bool,
     *   pieces_after_reuse:array<int, array<string, mixed>>,
     *   source_summaries:array<int, array<string, mixed>>
     * }
     */
    public function plan(Prodotto $materiale, array $pieces, float $kerfMm, int $minReusableLengthMm = 0): array
    {
        $flattenedPieces = $this->flattenPieces($pieces)
            ->sort(function (array $left, array $right): int {
                $byLength = ((float) $right['length']) <=> ((float) $left['length']);
                if ($byLength !== 0) {
                    return $byLength;
                }

                return ((float) ($right['width'] ?? 0)) <=> ((float) ($left['width'] ?? 0));
            })
            ->values();

        $availableScraps = Scarto::query()
            ->with([
                'lottoProduzione:id,codice_lotto',
                'lottoMateriale:id,codice_lotto,prodotto_id',
                'lottoMateriale.prodotto:id,nome,peso_specifico_kg_mc',
            ])
            ->where('riutilizzabile', true)
            ->where('riutilizzato', false)
            ->whereHas('lottoMateriale', function ($query) use ($materiale) {
                $query->where('prodotto_id', $materiale->id);
            })
            ->orderByDesc('lunghezza_mm')
            ->orderByDesc('larghezza_mm')
            ->get();

        $scrapPool = $availableScraps
            ->map(fn (Scarto $scarto): array => $this->normalizeScrapPoolEntry($scarto))
            ->values()
            ->all();

        $usedPieceIndexes = [];
        $usedScrapIds = [];
        $matches = [];

        foreach ($flattenedPieces as $piece) {
            $bestFitIndex = $this->findBestCompatibleScrapIndex(
                scrapPool: $scrapPool,
                piece: $piece,
                materiale: $materiale,
                kerfMm: $kerfMm
            );

            if ($bestFitIndex === null) {
                continue;
            }

            $selectedScrap = $scrapPool[$bestFitIndex];
            unset($scrapPool[$bestFitIndex]);
            $scrapPool = array_values($scrapPool);

            $requiredLength = round((float) $piece['length'], 2);
            $residualLength = $this->calculateResidualLength(
                scrapLengthMm: (float) $selectedScrap['current_length_mm'],
                requiredLengthMm: $requiredLength,
                kerfMm: $kerfMm
            );

            $usedPieceIndexes[] = (int) $piece['piece_index'];
            $usedScrapIds[] = (int) $selectedScrap['source_scrap_id'];

            $matches[] = [
                'scrap_id' => (int) $selectedScrap['source_scrap_id'],
                'piece_index' => (int) $piece['piece_index'],
                'piece_label' => (string) ($piece['description'] ?? ('Pezzo '.((int) $piece['piece_index'] + 1))),
                'component_id' => $piece['component_id'],
                'required_length_mm' => $requiredLength,
                'required_width_mm' => round((float) ($piece['width'] ?? 0), 2),
                'materiale_nome' => (string) ($selectedScrap['materiale_nome'] ?? $materiale->nome ?? 'Materiale'),
                'source_lotto_materiale_code' => $selectedScrap['source_lotto_materiale_code'] ?? null,
                'source_lotto_produzione_code' => $selectedScrap['source_lotto_produzione_code'] ?? null,
                'dimensioni_label' => $selectedScrap['dimensioni_label'],
                'source_length_mm' => round((float) $selectedScrap['current_length_mm'], 2),
                'source_width_mm' => round((float) ($selectedScrap['width_mm'] ?? 0), 2),
                'volume_mc' => round((float) ($selectedScrap['current_volume_mc'] ?? 0), 6),
                'peso_kg' => round((float) ($selectedScrap['current_peso_kg'] ?? 0), 3),
                'remaining_length_mm' => $residualLength,
                'remaining_volume_mc' => $residualLength > 0
                    ? round($this->calculateVolumeMc(
                        lengthMm: $residualLength,
                        widthMm: (float) ($selectedScrap['width_mm'] ?? 0),
                        thicknessMm: (float) ($selectedScrap['thickness_mm'] ?? 0)
                    ), 6)
                    : 0.0,
                'remaining_riutilizzabile' => $residualLength >= $minReusableLengthMm,
                'used_from_residual' => (bool) ($selectedScrap['is_residual'] ?? false),
            ];

            if ($residualLength > 0) {
                $scrapPool[] = [
                    ...$selectedScrap,
                    'current_length_mm' => $residualLength,
                    'current_volume_mc' => $this->calculateVolumeMc(
                        lengthMm: $residualLength,
                        widthMm: (float) ($selectedScrap['width_mm'] ?? 0),
                        thicknessMm: (float) ($selectedScrap['thickness_mm'] ?? 0)
                    ),
                    'current_peso_kg' => $this->calculateWeightKg(
                        volumeMc: $this->calculateVolumeMc(
                            lengthMm: $residualLength,
                            widthMm: (float) ($selectedScrap['width_mm'] ?? 0),
                            thicknessMm: (float) ($selectedScrap['thickness_mm'] ?? 0)
                        ),
                        pesoSpecificoKgMc: (float) ($selectedScrap['peso_specifico_kg_mc'] ?? 0)
                    ),
                    'dimensioni_label' => $this->formatDimensioniLabel(
                        lengthMm: $residualLength,
                        widthMm: (float) ($selectedScrap['width_mm'] ?? 0),
                        thicknessMm: (float) ($selectedScrap['thickness_mm'] ?? 0)
                    ),
                    'is_residual' => true,
                ];
            }
        }

        $usedPieceIndexes = array_values(array_unique($usedPieceIndexes));
        $usedScrapIds = array_values(array_unique($usedScrapIds));

        $piecesAfterReuse = $this->groupPieces(
            $flattenedPieces
                ->reject(fn (array $piece) => in_array((int) $piece['piece_index'], $usedPieceIndexes, true))
                ->values()
        );

        return [
            'required_count' => $flattenedPieces->count(),
            'available_scraps_count' => $availableScraps->count(),
            'matched_count' => count($usedPieceIndexes),
            'used_scrap_ids' => $usedScrapIds,
            'used_piece_indexes' => $usedPieceIndexes,
            'matches' => $matches,
            'used' => false,
            'pieces_after_reuse' => $piecesAfterReuse,
            'source_summaries' => $this->buildSourceSummaries(
                availableScraps: $availableScraps,
                matches: collect($matches),
                scrapPool: collect($scrapPool),
                minReusableLengthMm: $minReusableLengthMm
            ),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $scrapPool
     * @param  array<string, mixed>  $piece
     */
    private function findBestCompatibleScrapIndex(array $scrapPool, array $piece, Prodotto $materiale, float $kerfMm): ?int
    {
        $bestIndex = null;
        $bestResidual = null;
        $bestCurrentLength = null;

        foreach ($scrapPool as $index => $scrap) {
            if (! $this->isCompatible($scrap, $piece, $materiale, $kerfMm)) {
                continue;
            }

            $residual = $this->calculateResidualLength(
                scrapLengthMm: (float) $scrap['current_length_mm'],
                requiredLengthMm: (float) $piece['length'],
                kerfMm: $kerfMm
            );

            if ($bestIndex === null
                || $residual < $bestResidual
                || ($residual === $bestResidual && (float) $scrap['current_length_mm'] < (float) $bestCurrentLength)) {
                $bestIndex = $index;
                $bestResidual = $residual;
                $bestCurrentLength = (float) $scrap['current_length_mm'];
            }
        }

        return $bestIndex;
    }

    /**
     * @param  array<string, mixed>  $scrap
     * @param  array<string, mixed>  $piece
     */
    private function isCompatible(array $scrap, array $piece, Prodotto $materiale, float $kerfMm): bool
    {
        $scrapLength = (float) ($scrap['current_length_mm'] ?? 0);
        $scrapWidth = (float) ($scrap['width_mm'] ?? 0);
        $scrapThickness = (float) ($scrap['thickness_mm'] ?? 0);

        $requiredLength = (float) ($piece['length'] ?? 0);
        $requiredWidth = (float) ($piece['width'] ?? 0);
        $requiredThickness = (float) ($materiale->spessore_mm ?? 0);

        if ($requiredLength <= 0) {
            return false;
        }

        $fitsLengthExactly = abs($scrapLength - $requiredLength) < 0.0001;
        $fitsLengthWithKerf = $scrapLength >= ($requiredLength + max(0, $kerfMm));

        if (! $fitsLengthExactly && ! $fitsLengthWithKerf) {
            return false;
        }

        if ($requiredWidth > 0 && $scrapWidth > 0 && $scrapWidth < $requiredWidth) {
            return false;
        }

        if ($requiredThickness > 0 && $scrapThickness > 0 && $scrapThickness < $requiredThickness) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, array{id?:int,description?:string,length?:float,quantity?:int,width?:float,is_internal?:bool|null,allow_rotation?:bool|null}>  $pieces
     * @return Collection<int, array<string, mixed>>
     */
    private function flattenPieces(array $pieces): Collection
    {
        $flat = [];
        $pieceIndex = 0;

        foreach ($pieces as $piece) {
            $quantity = max(0, (int) ($piece['quantity'] ?? 0));
            $length = (float) ($piece['length'] ?? 0);

            if ($quantity <= 0 || $length <= 0) {
                continue;
            }

            for ($i = 0; $i < $quantity; $i++) {
                $flat[] = [
                    'piece_index' => $pieceIndex++,
                    'component_id' => isset($piece['id']) ? (int) $piece['id'] : null,
                    'description' => (string) ($piece['description'] ?? 'Pezzo'),
                    'length' => round($length, 2),
                    'width' => isset($piece['width']) ? round((float) $piece['width'], 2) : null,
                    'is_internal' => array_key_exists('is_internal', $piece)
                        ? ($piece['is_internal'] !== null ? (bool) $piece['is_internal'] : null)
                        : null,
                    'allow_rotation' => array_key_exists('allow_rotation', $piece)
                        ? ($piece['allow_rotation'] !== null ? (bool) $piece['allow_rotation'] : null)
                        : null,
                ];
            }
        }

        return collect($flat);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $pieces
     * @return array<int, array<string, mixed>>
     */
    private function groupPieces(Collection $pieces): array
    {
        return $pieces
            ->groupBy(function (array $piece): string {
                return implode('|', [
                    (string) ($piece['component_id'] ?? ''),
                    (string) ($piece['description'] ?? ''),
                    (string) round((float) ($piece['length'] ?? 0), 2),
                    (string) round((float) ($piece['width'] ?? 0), 2),
                    array_key_exists('is_internal', $piece) ? json_encode($piece['is_internal']) : 'null',
                    array_key_exists('allow_rotation', $piece) ? json_encode($piece['allow_rotation']) : 'null',
                ]);
            })
            ->map(function (Collection $group): array {
                $first = $group->first();

                $normalized = [
                    'id' => $first['component_id'] ?? null,
                    'description' => (string) ($first['description'] ?? 'Pezzo'),
                    'length' => round((float) ($first['length'] ?? 0), 2),
                    'quantity' => $group->count(),
                ];

                if (($first['width'] ?? null) !== null) {
                    $normalized['width'] = round((float) $first['width'], 2);
                }

                if (array_key_exists('is_internal', $first)) {
                    $normalized['is_internal'] = $first['is_internal'];
                }

                if (array_key_exists('allow_rotation', $first)) {
                    $normalized['allow_rotation'] = $first['allow_rotation'];
                }

                return $normalized;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeScrapPoolEntry(Scarto $scarto): array
    {
        $volumeMc = $scarto->calculatedVolumeMc();
        $pesoSpecifico = (float) ($scarto->lottoMateriale?->prodotto?->peso_specifico_kg_mc ?? 0);

        return [
            'source_scrap_id' => (int) $scarto->id,
            'lotto_materiale_id' => (int) ($scarto->lotto_materiale_id ?? 0),
            'lotto_produzione_id' => $scarto->lotto_produzione_id ? (int) $scarto->lotto_produzione_id : null,
            'source_lotto_materiale_code' => $scarto->lottoMateriale?->codice_lotto,
            'source_lotto_produzione_code' => $scarto->lottoProduzione?->codice_lotto,
            'materiale_nome' => (string) ($scarto->lottoMateriale?->prodotto?->nome ?? 'Materiale'),
            'peso_specifico_kg_mc' => $pesoSpecifico,
            'current_length_mm' => round((float) ($scarto->lunghezza_mm ?? 0), 2),
            'width_mm' => round((float) ($scarto->larghezza_mm ?? 0), 2),
            'thickness_mm' => round((float) ($scarto->spessore_mm ?? 0), 2),
            'current_volume_mc' => round($volumeMc, 6),
            'current_peso_kg' => round($this->calculateWeightKg($volumeMc, $pesoSpecifico), 3),
            'dimensioni_label' => $this->formatDimensioniLabel(
                lengthMm: (float) ($scarto->lunghezza_mm ?? 0),
                widthMm: (float) ($scarto->larghezza_mm ?? 0),
                thicknessMm: (float) ($scarto->spessore_mm ?? 0)
            ),
            'is_residual' => false,
        ];
    }

    /**
     * @param  Collection<int, Scarto>  $availableScraps
     * @param  Collection<int, array<string, mixed>>  $matches
     * @param  Collection<int, array<string, mixed>>  $scrapPool
     * @return array<int, array<string, mixed>>
     */
    private function buildSourceSummaries(
        Collection $availableScraps,
        Collection $matches,
        Collection $scrapPool,
        int $minReusableLengthMm
    ): array {
        $usedIds = $matches
            ->pluck('scrap_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $usedIds
            ->map(function (int $scrapId) use ($availableScraps, $matches, $scrapPool, $minReusableLengthMm): ?array {
                /** @var Scarto|null $source */
                $source = $availableScraps->firstWhere('id', $scrapId);
                if (! $source) {
                    return null;
                }

                $remaining = $scrapPool->last(fn (array $entry) => (int) ($entry['source_scrap_id'] ?? 0) === $scrapId);
                $cutsCount = $matches->where('scrap_id', $scrapId)->count();
                $remainingLength = round((float) ($remaining['current_length_mm'] ?? 0), 2);
                $remainingWidth = round((float) ($remaining['width_mm'] ?? (float) ($source->larghezza_mm ?? 0)), 2);
                $remainingThickness = round((float) ($remaining['thickness_mm'] ?? (float) ($source->spessore_mm ?? 0)), 2);
                $remainingVolume = $remainingLength > 0
                    ? round($this->calculateVolumeMc($remainingLength, $remainingWidth, $remainingThickness), 6)
                    : 0.0;

                return [
                    'source_scrap_id' => $scrapId,
                    'lotto_materiale_id' => (int) ($source->lotto_materiale_id ?? 0),
                    'lotto_produzione_id' => $source->lotto_produzione_id ? (int) $source->lotto_produzione_id : null,
                    'source_lotto_materiale_code' => $source->lottoMateriale?->codice_lotto,
                    'source_lotto_produzione_code' => $source->lottoProduzione?->codice_lotto,
                    'original_length_mm' => round((float) ($source->lunghezza_mm ?? 0), 2),
                    'original_width_mm' => round((float) ($source->larghezza_mm ?? 0), 2),
                    'original_thickness_mm' => round((float) ($source->spessore_mm ?? 0), 2),
                    'cuts_count' => $cutsCount,
                    'remaining_length_mm' => $remainingLength,
                    'remaining_width_mm' => $remainingWidth,
                    'remaining_thickness_mm' => $remainingThickness,
                    'remaining_volume_mc' => $remainingVolume,
                    'remaining_riutilizzabile' => $remainingLength >= $minReusableLengthMm,
                    'consumed_fully' => $remainingLength <= 0,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function calculateResidualLength(float $scrapLengthMm, float $requiredLengthMm, float $kerfMm): float
    {
        if (abs($scrapLengthMm - $requiredLengthMm) < 0.0001) {
            return 0.0;
        }

        return round(max(0, $scrapLengthMm - $requiredLengthMm - max(0, $kerfMm)), 2);
    }

    private function calculateVolumeMc(float $lengthMm, float $widthMm, float $thicknessMm): float
    {
        return max(0, ($lengthMm * $widthMm * $thicknessMm) / 1000000000);
    }

    private function calculateWeightKg(float $volumeMc, float $pesoSpecificoKgMc): float
    {
        return max(0, $volumeMc * $pesoSpecificoKgMc);
    }

    private function formatDimensioniLabel(float $lengthMm, float $widthMm, float $thicknessMm): string
    {
        return sprintf(
            '%s x %s x %s mm',
            number_format($lengthMm, 0, ',', '.'),
            number_format($widthMm, 0, ',', '.'),
            number_format($thicknessMm, 0, ',', '.')
        );
    }
}
