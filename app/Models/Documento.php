<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documenti';

    protected $fillable = [
        'tipo',
        'numero',
        'data',
        'cliente_id',
        'fornitore',
        'descrizione',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoDocumento::class,
            'data' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(MovimentoMagazzino::class);
    }

    public function getRiferimentoCompletoAttribute(): string
    {
        return sprintf('%s n. %s del %s',
            $this->tipo->label(),
            $this->numero,
            $this->data->format('d/m/Y')
        );
    }

    public function scopeByTipo($query, TipoDocumento $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('numero', 'ilike', "%{$term}%")
              ->orWhere('fornitore', 'ilike', "%{$term}%")
              ->orWhereHas('cliente', function ($q2) use ($term) {
                  $q2->where('ragione_sociale', 'ilike', "%{$term}%");
              });
        });
    }
}
