<?php

namespace App\Models;

use App\Enums\UnitaMisura;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomRiga extends Model
{
    use HasFactory;

    protected $table = 'bom_righe';

    protected $fillable = [
        'bom_id',
        'prodotto_id',
        'source_type',
        'source_id',
        'descrizione',
        'quantita',
        'unita_misura',
        'coefficiente_scarto',
        'is_fitok_required',
        'is_optional',
        'ordine',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantita' => 'decimal:4',
            'coefficiente_scarto' => 'decimal:4',
            'is_fitok_required' => 'boolean',
            'is_optional' => 'boolean',
            'unita_misura' => UnitaMisura::class,
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }

    public function quantitaConScarto(): float
    {
        return $this->quantita * (1 + $this->coefficiente_scarto);
    }
}
