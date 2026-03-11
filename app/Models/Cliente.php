<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clienti';

    protected $fillable = [
        'ragione_sociale',
        'partita_iva',
        'codice_fiscale',
        'indirizzo',
        'cap',
        'citta',
        'provincia',
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

    public function documenti(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function preventivi(): HasMany
    {
        return $this->hasMany(Preventivo::class);
    }

    public function lottiProduzione(): HasMany
    {
        return $this->hasMany(LottoProduzione::class);
    }

    public function getIndirizzoCompletoAttribute(): string
    {
        $parts = array_filter([
            $this->indirizzo,
            $this->cap,
            $this->citta,
            $this->provincia ? "({$this->provincia})" : null,
        ]);

        return implode(' ', $parts);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(ragione_sociale) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(partita_iva) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(codice_fiscale) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"]);
        });
    }
}
