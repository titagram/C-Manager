<?php

namespace App\Services\Production;

use App\Enums\Categoria;
use App\Models\Costruzione;
use App\Models\Prodotto;

class ConstructionOptimizerResolver
{
    public function __construct(
        private readonly CassaConstructionOptimizer $cassaOptimizer,
        private readonly GabbiaConstructionOptimizer $gabbiaOptimizer,
        private readonly BancaleConstructionOptimizer $bancaleOptimizer,
        private readonly LegaccioConstructionOptimizer $legaccioOptimizer,
        private readonly ?ProductionSettingsService $productionSettings = null
    ) {}

    /**
     * @param  array<int, array{id:int, description:string, length:float, quantity:int, width?:float}>  $pieces
     * @return array<string, mixed>|null
     */
    public function optimizeOrNull(
        Costruzione $costruzione,
        array $pieces,
        Prodotto $materiale,
        float $kerfMm,
        array $context = []
    ): ?array {
        foreach ($this->optimizerRouteCandidates($costruzione) as $routeKey) {
            $result = match ($routeKey) {
                // Specialized keys by slug/config can be added here incrementally
                // without touching callers. They currently route to the category
                // optimizer until a dedicated implementation is introduced.
                'cassa' => $this->isCassaCategoryOptimizerEnabled()
                    ? $this->cassaOptimizer->optimize($costruzione, $pieces, $materiale, $kerfMm, $context)
                    : null,
                'gabbia' => $this->gabbiaOptimizer->optimize($costruzione, $pieces, $materiale, $kerfMm, $context),
                'bancale' => $this->bancaleOptimizer->optimize($costruzione, $pieces, $materiale, $kerfMm, $context),
                'legaccio' => $this->legaccioOptimizer->optimize($costruzione, $pieces, $materiale, $kerfMm, $context),
                default => null,
            };

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    public function hasCategoryOptimizer(Costruzione $costruzione): bool
    {
        return in_array((string) $costruzione->categoria, ['cassa', 'gabbia', 'bancale', 'legaccio'], true);
    }

    /**
     * @return array<string>
     */
    public function allowedPrimaryMaterialCategoryValues(?Costruzione $costruzione = null): array
    {
        // Current policy for "materiale asse" (optimizer primary stock):
        // allow only wood/semilavorati categories with board-like dimensions.
        // This intentionally excludes ferramenta/altro (e.g. chiodi).
        return [
            Categoria::MATERIA_PRIMA->value,
            Categoria::ASSE->value,
            Categoria::LISTELLO->value,
        ];
    }

    public function assertPrimaryMaterialCompatible(Costruzione $costruzione, Prodotto $materiale): void
    {
        $categoria = $materiale->categoria;
        if (! $categoria || ! $categoria->isMateriaPrima()) {
            throw new \InvalidArgumentException(
                'Il materiale selezionato non e compatibile come materiale asse per questo calcolo (es. chiodi/ferramenta).'
            );
        }

        if ((float) ($materiale->lunghezza_mm ?? 0) <= 0
            || (float) ($materiale->larghezza_mm ?? 0) <= 0
            || (float) ($materiale->spessore_mm ?? 0) <= 0) {
            throw new \InvalidArgumentException(
                'Il materiale selezionato deve avere lunghezza, larghezza e spessore definiti.'
            );
        }

        // Reserved hook for category-specific compatibility checks.
        if ($this->hasCategoryOptimizer($costruzione)) {
            return;
        }
    }

    /**
     * Build ordered route candidates for optimizer dispatch.
     *
     * Order:
     * 1) category + config key (future dedicated implementations)
     * 2) category + slug (future dedicated implementations)
     * 3) generic category fallback
     *
     * @return array<int, string>
     */
    private function optimizerRouteCandidates(Costruzione $costruzione): array
    {
        $category = strtolower((string) $costruzione->categoria);
        if ($category === '') {
            return [];
        }

        $config = is_array($costruzione->config) ? $costruzione->config : [];
        $optimizerKey = strtolower(trim((string) ($config['optimizer_key'] ?? '')));
        $slug = strtolower(trim((string) ($costruzione->slug ?? '')));

        $candidates = [];
        if ($optimizerKey !== '') {
            $candidates[] = "{$category}:config:{$optimizerKey}";
        }

        if ($slug !== '') {
            $candidates[] = "{$category}:slug:{$slug}";
        }

        $candidates[] = $category;

        return array_values(array_unique($candidates));
    }

    private function isCassaCategoryOptimizerEnabled(): bool
    {
        if ($this->productionSettings !== null) {
            return $this->productionSettings->cassaCategoryOptimizerEnabled();
        }

        $mode = strtolower((string) config('production.cassa_optimizer_mode', 'physical'));

        return $mode !== 'legacy';
    }
}
