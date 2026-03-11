<?php

namespace App\Models;

use App\Enums\TipoRigaPreventivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\LottoProduzione;

class PreventivoRiga extends Model
{
    use HasFactory;

    protected $table = 'preventivo_righe';

    protected $fillable = [
        'preventivo_id',
        'lotto_produzione_id',
        'tipo_riga',
        'include_in_bom',
        'prodotto_id',
        'unita_misura',
        'descrizione',
        'lunghezza_mm',
        'larghezza_mm',
        'spessore_mm',
        'quantita',
        'superficie_mq',
        'volume_mc',
        'materiale_netto',
        'coefficiente_scarto',
        'materiale_lordo',
        'prezzo_unitario',
        'totale_riga',
        'ordine',
    ];

    protected function casts(): array
    {
        return [
            'lunghezza_mm' => 'decimal:2',
            'larghezza_mm' => 'decimal:2',
            'spessore_mm' => 'decimal:2',
            'tipo_riga' => TipoRigaPreventivo::class,
            'include_in_bom' => 'boolean',
            'unita_misura' => 'string',
            'quantita' => 'integer',
            'superficie_mq' => 'decimal:6',
            'volume_mc' => 'decimal:6',
            'materiale_netto' => 'decimal:4',
            'coefficiente_scarto' => 'decimal:4',
            'materiale_lordo' => 'decimal:4',
            'prezzo_unitario' => 'decimal:4',
            'totale_riga' => 'decimal:2',
        ];
    }

    public function preventivo(): BelongsTo
    {
        return $this->belongsTo(Preventivo::class);
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }

    public function lottoProduzione(): BelongsTo
    {
        return $this->belongsTo(LottoProduzione::class);
    }

    public function calcolaValori(): void
    {
        if ($this->tipo_riga === TipoRigaPreventivo::LOTTO) {
            return;
        }

        // Conversione in metri
        $lunghezza_m = ($this->lunghezza_mm ?? 0) / 1000;
        $larghezza_m = ($this->larghezza_mm ?? 0) / 1000;
        $spessore_m = ($this->spessore_mm ?? 0) / 1000;

        // Superficie e volume
        $this->superficie_mq = $lunghezza_m * $larghezza_m * $this->quantita;
        $this->volume_mc = $lunghezza_m * $larghezza_m * $spessore_m * $this->quantita;

        // Materiale netto e lordo
        $this->materiale_netto = $this->volume_mc;
        $this->materiale_lordo = $this->materiale_netto * (1 + $this->coefficiente_scarto);

        // Arrotondamento per eccesso (3 decimali)
        $this->materiale_lordo = ceil($this->materiale_lordo * 1000) / 1000;

        // Totale riga
        $this->totale_riga = $this->materiale_lordo * ($this->prezzo_unitario ?? 0);
    }
}
