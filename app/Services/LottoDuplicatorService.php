<?php

namespace App\Services;

use App\Enums\StatoLottoProduzione;
use App\Models\LottoProduzione;
use Illuminate\Support\Facades\DB;

class LottoDuplicatorService
{
    /**
     * Duplica un lotto esistente creando sempre una nuova bozza scollegata
     * dallo stato operativo originario.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function duplicate(LottoProduzione $source, array $overrides = []): LottoProduzione
    {
        return DB::transaction(function () use ($source, $overrides) {
            $source->loadMissing([
                'materialiUsati',
                'componentiManuali',
            ]);

            $clone = $source->replicate([
                'codice_lotto',
                'anno',
                'progressivo',
                'stato',
                'data_inizio',
                'data_fine',
                'ordine_id',
                'ordine_riga_id',
                'fitok_percentuale',
                'fitok_volume_mc',
                'non_fitok_volume_mc',
                'fitok_calcolato_at',
            ]);

            $clone->fill(array_merge([
                'codice_lotto' => null,
                'anno' => null,
                'progressivo' => null,
                'stato' => StatoLottoProduzione::BOZZA,
                'data_inizio' => null,
                'data_fine' => null,
                'ordine_id' => null,
                'ordine_riga_id' => null,
                'fitok_percentuale' => null,
                'fitok_volume_mc' => null,
                'non_fitok_volume_mc' => null,
                'fitok_calcolato_at' => null,
            ], $overrides));

            $clone->save();

            foreach ($source->materialiUsati as $materiale) {
                $clone->materialiUsati()->create(
                    collect($materiale->getAttributes())
                        ->except(['id', 'lotto_produzione_id', 'created_at', 'updated_at'])
                        ->all()
                );
            }

            foreach ($source->componentiManuali as $componente) {
                $clone->componentiManuali()->create(
                    collect($componente->getAttributes())
                        ->except(['id', 'lotto_produzione_id', 'created_at', 'updated_at'])
                        ->all()
                );
            }

            return $clone->fresh([
                'materialiUsati',
                'componentiManuali',
            ]);
        });
    }
}
