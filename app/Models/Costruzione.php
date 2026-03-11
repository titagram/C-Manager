<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Costruzione extends Model
{
    use HasFactory;

    protected $table = 'costruzioni';

    protected $fillable = [
        'categoria',
        'nome',
        'slug',
        'descrizione',
        'config',
        'richiede_lunghezza',
        'richiede_larghezza',
        'richiede_altezza',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'richiede_lunghezza' => 'boolean',
            'richiede_larghezza' => 'boolean',
            'richiede_altezza' => 'boolean',
        ];
    }

    public function componenti()
    {
        return $this->hasMany(ComponenteCostruzione::class);
    }

    public function lotti(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LottoProduzione::class);
    }

    public function showWeightInQuote(): bool
    {
        return (bool) data_get($this->config, 'show_weight_in_quote', false);
    }

    /**
     * Scope per costruzioni attive
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope per tipo
     */
    public function scopeOfType($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
