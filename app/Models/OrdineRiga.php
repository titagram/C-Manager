<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdineRiga extends Model
{
    use HasFactory;

    protected $table = 'ordine_righe';

    protected $fillable = [
        'ordine_id',
        'prodotto_id',
        'descrizione',
        'tipo_costruzione',
        'larghezza_mm',
        'profondita_mm',
        'altezza_mm',
        'riferimento_volume',
        'spessore_base_mm',
        'spessore_fondo_mm',
        'quantita',
        'volume_mc_calcolato',
        'volume_mc_finale',
        'prezzo_mc',
        'totale_riga',
        'ordine',
    ];

    protected function casts(): array
    {
        return [
            'larghezza_mm' => 'integer',
            'profondita_mm' => 'integer',
            'altezza_mm' => 'integer',
            'spessore_base_mm' => 'integer',
            'spessore_fondo_mm' => 'integer',
            'quantita' => 'integer',
            'volume_mc_calcolato' => 'decimal:6',
            'volume_mc_finale' => 'decimal:6',
            'prezzo_mc' => 'decimal:4',
            'totale_riga' => 'decimal:2',
        ];
    }

    public function ordineParent(): BelongsTo
    {
        return $this->belongsTo(Ordine::class, 'ordine_id');
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }

    /**
     * Calculate volume from dimensions
     */
    public function calcolaValori(): void
    {
        if ($this->larghezza_mm && $this->profondita_mm && $this->altezza_mm) {
            // Convert mm to meters and calculate volume
            $l = $this->larghezza_mm / 1000;
            $p = $this->profondita_mm / 1000;
            $h = $this->altezza_mm / 1000;

            $this->volume_mc_calcolato = round($l * $p * $h, 6);
            $this->volume_mc_finale = round($this->volume_mc_calcolato * $this->quantita, 6);
        }

        $this->calcolaTotale();
    }

    /**
     * Calculate row total from volume and price
     */
    public function calcolaTotale(): void
    {
        $this->totale_riga = round(($this->volume_mc_finale ?? 0) * ($this->prezzo_mc ?? 0), 2);
    }
}
