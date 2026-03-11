<?php

namespace App\Services\Production\DTO;

use App\Models\Costruzione;
use App\Models\Prodotto;

class OptimizationInput
{
    /**
     * @param array{
     *   larghezza_cm: float,
     *   profondita_cm: float,
     *   altezza_cm: float,
     *   numero_pezzi: int
     * } $dimensions
     * @param array<int, array{
     *   id?: int,
     *   description?: string,
     *   length?: float,
     *   quantity?: int,
     *   width?: float,
     *   is_internal?: bool|null,
     *   allow_rotation?: bool|null
     * }> $pieces
     */
    public function __construct(
        public readonly int $costruzioneId,
        public readonly string $categoria,
        public readonly ?string $costruzioneSlug,
        public readonly int $materialeId,
        public readonly ?string $materialeCodice,
        public readonly float $kerfMm,
        public readonly array $dimensions,
        public readonly array $pieces,
        public readonly array $rulesSnapshot,
        public readonly string $rulesFingerprint,
        public readonly string $rulesVersion
    ) {}

    /**
     * @param array<int, array{
     *   id?: int,
     *   description?: string,
     *   length?: float,
     *   quantity?: int,
     *   width?: float,
     *   is_internal?: bool|null,
     *   allow_rotation?: bool|null
     * }> $pieces
     */
    public static function fromRuntime(
        Costruzione $costruzione,
        Prodotto $materiale,
        float $kerfMm,
        array $dimensions,
        array $pieces
    ): self {
        $costruzione->loadMissing('componenti');
        $rulesSnapshot = self::buildRulesSnapshot($costruzione);
        $rulesFingerprint = hash('sha256', json_encode($rulesSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return new self(
            costruzioneId: (int) $costruzione->id,
            categoria: (string) $costruzione->categoria,
            costruzioneSlug: $costruzione->slug,
            materialeId: (int) $materiale->id,
            materialeCodice: $materiale->codice,
            kerfMm: max(0, $kerfMm),
            dimensions: [
                'larghezza_cm' => (float) ($dimensions['larghezza_cm'] ?? 0),
                'profondita_cm' => (float) ($dimensions['profondita_cm'] ?? 0),
                'altezza_cm' => (float) ($dimensions['altezza_cm'] ?? 0),
                'numero_pezzi' => max(1, (int) ($dimensions['numero_pezzi'] ?? 1)),
            ],
            pieces: $pieces,
            rulesSnapshot: $rulesSnapshot,
            rulesFingerprint: $rulesFingerprint,
            rulesVersion: 'rules-' . substr($rulesFingerprint, 0, 12)
        );
    }

    /**
     * Lightweight trace snapshot for audit/debug.
     *
     * @return array<string, mixed>
     */
    public function toTraceArray(): array
    {
        return [
            'costruzione' => [
                'id' => $this->costruzioneId,
                'categoria' => $this->categoria,
                'slug' => $this->costruzioneSlug,
            ],
            'materiale' => [
                'id' => $this->materialeId,
                'codice' => $this->materialeCodice,
            ],
            'dimensions_cm' => $this->dimensions,
            'kerf_mm' => $this->kerfMm,
            'requirements' => [
                'pieces_count' => count($this->pieces),
                // Future-facing metadata fields: available even if currently null/unused.
                'component_constraints' => collect($this->pieces)
                    ->map(function (array $piece): array {
                        return [
                            'component_id' => isset($piece['id']) ? (int) $piece['id'] : null,
                            'description' => (string) ($piece['description'] ?? ''),
                            'is_internal' => array_key_exists('is_internal', $piece)
                                ? ($piece['is_internal'] !== null ? (bool) $piece['is_internal'] : null)
                                : null,
                            'allow_rotation' => array_key_exists('allow_rotation', $piece)
                                ? ($piece['allow_rotation'] !== null ? (bool) $piece['allow_rotation'] : null)
                                : null,
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            'rules' => [
                'version' => $this->rulesVersion,
                'fingerprint_sha256' => $this->rulesFingerprint,
                'snapshot' => $this->rulesSnapshot,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildRulesSnapshot(Costruzione $costruzione): array
    {
        $componenti = $costruzione->componenti
            ->sortBy('id')
            ->values()
            ->map(static fn ($componente): array => [
                'id' => (int) $componente->id,
                'nome' => (string) $componente->nome,
                'tipo_dimensionamento' => strtoupper((string) ($componente->tipo_dimensionamento ?? '')),
                'calcolato' => (bool) ($componente->calcolato ?? false),
                'formula_lunghezza' => $componente->formula_lunghezza,
                'formula_larghezza' => $componente->formula_larghezza,
                'formula_quantita' => $componente->formula_quantita,
                'is_internal' => (bool) ($componente->is_internal ?? false),
                'allow_rotation' => (bool) ($componente->allow_rotation ?? false),
            ])
            ->all();

        $calculatedCount = collect($componenti)
            ->where('tipo_dimensionamento', 'CALCOLATO')
            ->count();
        $manualCount = collect($componenti)
            ->where('tipo_dimensionamento', 'MANUALE')
            ->count();

        $config = self::normalizeForFingerprint((array) ($costruzione->config ?? []));

        return [
            'schema_version' => 1,
            'costruzione' => [
                'id' => (int) $costruzione->id,
                'categoria' => (string) $costruzione->categoria,
                'slug' => (string) ($costruzione->slug ?? ''),
                'nome' => (string) ($costruzione->nome ?? ''),
                'richiede_lunghezza' => (bool) ($costruzione->richiede_lunghezza ?? false),
                'richiede_larghezza' => (bool) ($costruzione->richiede_larghezza ?? false),
                'richiede_altezza' => (bool) ($costruzione->richiede_altezza ?? false),
                'config' => $config,
            ],
            'componenti' => $componenti,
            'summary' => [
                'total' => count($componenti),
                'calcolato' => $calculatedCount,
                'manuale' => $manualCount,
            ],
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function normalizeForFingerprint(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $isAssoc = array_keys($value) !== range(0, count($value) - 1);
        if ($isAssoc) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::normalizeForFingerprint($item);
        }

        return $value;
    }
}
