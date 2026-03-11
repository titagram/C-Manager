<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ProgressivoGeneratorService
{
    private const ENTITY_TABLE_MAP = [
        'preventivi' => 'preventivi',
        'ordini' => 'ordini',
        'lotti_produzione' => 'lotti_produzione',
        'bom' => 'bom',
    ];

    public function next(string $entita, int $anno): int
    {
        if (!array_key_exists($entita, self::ENTITY_TABLE_MAP)) {
            throw new InvalidArgumentException("Entita non supportata per progressivo: {$entita}");
        }

        $sourceTable = self::ENTITY_TABLE_MAP[$entita];

        return DB::transaction(function () use ($entita, $anno, $sourceTable): int {
            $this->ensureSequenceRowExists($entita, $anno, $sourceTable);

            $sequence = DB::table('progressivi_annuali')
                ->where('entita', $entita)
                ->where('anno', $anno)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw new RuntimeException("Sequenza non trovata per {$entita}/{$anno}");
            }

            $next = ((int) $sequence->last_value) + 1;

            DB::table('progressivi_annuali')
                ->where('entita', $entita)
                ->where('anno', $anno)
                ->update([
                    'last_value' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        }, 5);
    }

    private function ensureSequenceRowExists(string $entita, int $anno, string $sourceTable): void
    {
        $exists = DB::table('progressivi_annuali')
            ->where('entita', $entita)
            ->where('anno', $anno)
            ->exists();

        if ($exists) {
            return;
        }

        $currentMax = (int) (DB::table($sourceTable)
            ->where('anno', $anno)
            ->max('progressivo') ?? 0);

        try {
            DB::table('progressivi_annuali')->insert([
                'entita' => $entita,
                'anno' => $anno,
                'last_value' => $currentMax,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            $alreadyCreated = DB::table('progressivi_annuali')
                ->where('entita', $entita)
                ->where('anno', $anno)
                ->exists();

            if (!$alreadyCreated) {
                throw $e;
            }
        }
    }
}
