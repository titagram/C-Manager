<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scarto extends Model
{
    use HasFactory;

    protected $table = 'scarti';

    protected $fillable = [
        'lotto_produzione_id',
        'lotto_materiale_id',
        'lunghezza_mm',
        'larghezza_mm',
        'spessore_mm',
        'volume_mc',
        'riutilizzabile',
        'riutilizzato',
        'riutilizzato_in_lotto_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'lunghezza_mm' => 'decimal:2',
            'larghezza_mm' => 'decimal:2',
            'spessore_mm' => 'decimal:2',
            'volume_mc' => 'decimal:6',
            'riutilizzabile' => 'boolean',
            'riutilizzato' => 'boolean',
        ];
    }

    public function lottoProduzione(): BelongsTo
    {
        return $this->belongsTo(LottoProduzione::class);
    }

    public function lottoMateriale(): BelongsTo
    {
        return $this->belongsTo(LottoMateriale::class);
    }

    public function lottoRiutilizzo(): BelongsTo
    {
        return $this->belongsTo(LottoProduzione::class, 'riutilizzato_in_lotto_id');
    }

    public static function calculateVolumeMcFromDimensions(
        float|int|string|null $lunghezzaMm,
        float|int|string|null $larghezzaMm,
        float|int|string|null $spessoreMm
    ): float {
        return max(
            0.0,
            (((float) $lunghezzaMm) * ((float) $larghezzaMm) * ((float) $spessoreMm)) / 1000000000
        );
    }

    public static function calculateWeightKgFromVolume(
        float|int|string|null $volumeMc,
        float|int|string|null $pesoSpecificoKgMc
    ): float {
        return max(0.0, ((float) $volumeMc) * ((float) $pesoSpecificoKgMc));
    }

    public function calculatedVolumeMc(): float
    {
        return self::calculateVolumeMcFromDimensions(
            $this->lunghezza_mm,
            $this->larghezza_mm,
            $this->spessore_mm
        );
    }

    public function estimatedWeightKg(): ?float
    {
        $pesoSpecifico = (float) ($this->lottoMateriale?->prodotto?->peso_specifico_kg_mc ?? 0);

        if ($pesoSpecifico <= 0) {
            return null;
        }

        return self::calculateWeightKgFromVolume($this->calculatedVolumeMc(), $pesoSpecifico);
    }
}
