<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LottoComponenteManuale extends Model
{
    use HasFactory;

    protected $table = 'lotto_componenti_manuali';

    protected $fillable = [
        'lotto_produzione_id',
        'componente_costruzione_id',
        'prodotto_id',
        'quantita',
        'prezzo_unitario',
        'unita_misura',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantita' => 'decimal:4',
            'prezzo_unitario' => 'decimal:4',
        ];
    }

    public function lottoProduzione(): BelongsTo
    {
        return $this->belongsTo(LottoProduzione::class);
    }

    public function componenteCostruzione(): BelongsTo
    {
        return $this->belongsTo(ComponenteCostruzione::class);
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }
}
