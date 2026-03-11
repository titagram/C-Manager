<?php

namespace App\Models;

use App\Enums\StatoConsumoMateriale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumoMateriale extends Model
{
    use HasFactory;

    protected $table = 'consumi_materiale';

    protected $fillable = [
        'lotto_produzione_id',
        'lotto_materiale_id',
        'movimento_id',
        'stato',
        'opzionato_at',
        'consumato_at',
        'released_at',
        'quantita',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'stato' => StatoConsumoMateriale::class,
            'opzionato_at' => 'datetime',
            'consumato_at' => 'datetime',
            'released_at' => 'datetime',
            'quantita' => 'decimal:4',
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

    public function movimento(): BelongsTo
    {
        return $this->belongsTo(MovimentoMagazzino::class);
    }

    public function isOpzionato(): bool
    {
        return $this->stato === StatoConsumoMateriale::OPZIONATO;
    }

    public function isConsumabile(): bool
    {
        return in_array($this->stato, [
            StatoConsumoMateriale::PIANIFICATO,
            StatoConsumoMateriale::OPZIONATO,
        ], true);
    }
}
