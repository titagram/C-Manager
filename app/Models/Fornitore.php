<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fornitore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fornitori';

    protected $fillable = [
        'codice',
        'ragione_sociale',
        'partita_iva',
        'codice_fiscale',
        'indirizzo',
        'cap',
        'citta',
        'provincia',
        'nazione',
        'telefono',
        'email',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function lottiMateriale(): HasMany
    {
        return $this->hasMany(LottoMateriale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(codice) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(ragione_sociale) LIKE ?', ["%{$term}%"]);
        });
    }

    /**
     * Restituisce indirizzo completo formattato
     */
    public function getIndirizzoCompletoAttribute(): ?string
    {
        $parts = array_filter([
            $this->indirizzo,
            $this->cap,
            $this->citta,
            $this->provincia ? "({$this->provincia})" : null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
