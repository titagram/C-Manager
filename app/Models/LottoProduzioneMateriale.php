<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LottoProduzioneMateriale extends Model
{
    use HasFactory;

    protected $table = 'lotto_produzione_materiali';

    protected $fillable = [
        'lotto_produzione_id',
        'lotto_materiale_id',
        'prodotto_id',
        'source_profile',
        'descrizione',
        'lunghezza_mm',
        'larghezza_mm',
        'spessore_mm',
        'quantita_pezzi',
        'volume_mc',
        'volume_netto_mc',
        'volume_scarto_mc',
        'pezzi_per_asse',
        'assi_necessarie',
        'scarto_per_asse_mm',
        'scarto_totale_mm',
        'scarto_percentuale',
        'costo_materiale',
        'prezzo_vendita',
        'is_fitok',
        'ordine',
    ];

    protected function casts(): array
    {
        return [
            'quantita_pezzi' => 'integer',
            'pezzi_per_asse' => 'integer',
            'assi_necessarie' => 'integer',
            'ordine' => 'integer',
            'lunghezza_mm' => 'decimal:2',
            'larghezza_mm' => 'decimal:2',
            'spessore_mm' => 'decimal:2',
            'volume_mc' => 'decimal:6',
            'volume_netto_mc' => 'decimal:6',
            'volume_scarto_mc' => 'decimal:6',
            'scarto_per_asse_mm' => 'decimal:2',
            'scarto_totale_mm' => 'decimal:2',
            'scarto_percentuale' => 'decimal:2',
            'costo_materiale' => 'decimal:2',
            'prezzo_vendita' => 'decimal:2',
            'is_fitok' => 'boolean',
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

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }
}
