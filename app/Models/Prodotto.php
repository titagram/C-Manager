<?php

namespace App\Models;

use App\Enums\Categoria;
use App\Enums\UnitaMisura;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prodotto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prodotti';

    protected $fillable = [
        'codice',
        'nome',
        'descrizione',
        'unita_misura',
        'categoria',
        'soggetto_fitok',
        'prezzo_unitario',
        'costo_unitario',
        'prezzo_mc',
        'coefficiente_scarto',
        'lunghezza_mm',
        'larghezza_mm',
        'spessore_mm',
        'peso_specifico_kg_mc',
        'is_active',
        'usa_dimensioni',
    ];

    protected function casts(): array
    {
        return [
            'unita_misura' => UnitaMisura::class,
            'categoria' => Categoria::class,
            'soggetto_fitok' => 'boolean',
            'prezzo_unitario' => 'decimal:4',
            'costo_unitario' => 'decimal:4',
            'prezzo_mc' => 'decimal:2',
            'coefficiente_scarto' => 'decimal:4',
            'lunghezza_mm' => 'decimal:2',
            'larghezza_mm' => 'decimal:2',
            'spessore_mm' => 'decimal:2',
            'peso_specifico_kg_mc' => 'decimal:3',
            'is_active' => 'boolean',
            'usa_dimensioni' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $prodotto): void {
            $unitaMisura = $prodotto->resolveUnitaMisuraValue();

            // Keep `prezzo_mc` meaningful only for volumetric products.
            if ($unitaMisura !== UnitaMisura::MC->value) {
                $prodotto->prezzo_mc = null;

                return;
            }

            $prezzoMc = $prodotto->normalizeNullableNumber($prodotto->prezzo_mc, 2);
            $prezzoUnitario = $prodotto->normalizeNullableNumber($prodotto->prezzo_unitario, 4);

            if ($prezzoMc === null) {
                $prezzoMc = $prezzoUnitario !== null ? round($prezzoUnitario, 2) : 0.0;
            }

            // Legacy mirror for old flows still reading `prezzo_unitario`.
            $prezzoUnitario = $prezzoMc !== null ? round($prezzoMc, 4) : null;

            $prodotto->prezzo_mc = $prezzoMc;
            $prodotto->prezzo_unitario = $prezzoUnitario;
        });
    }

    public function lottiMateriale(): HasMany
    {
        return $this->hasMany(LottoMateriale::class);
    }

    public function preventivoRighe(): HasMany
    {
        return $this->hasMany(PreventivoRiga::class);
    }

    public function scarti(): HasManyThrough
    {
        return $this->hasManyThrough(
            Scarto::class,
            LottoMateriale::class,
            'prodotto_id', // Foreign key on lotti_materiale table
            'lotto_materiale_id', // Foreign key on scarti table
            'id', // Local key on prodotti table
            'id' // Local key on lotti_materiale table
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFitok($query)
    {
        return $query->where('soggetto_fitok', true);
    }

    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(codice) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(nome) LIKE ?', ["%{$term}%"]);
        });
    }

    public function scopeByCategoria($query, Categoria $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function prezzoListinoPerMc(): float
    {
        return max(0.0, (float) ($this->prezzo_mc ?? $this->prezzo_unitario ?? 0));
    }

    public function prezzoListinoPerUnita(UnitaMisura|string|null $unitaMisura = null): float
    {
        $uomValue = $this->resolveUnitaMisuraValue($unitaMisura);

        if ($uomValue === UnitaMisura::MC->value) {
            return $this->prezzoListinoPerMc();
        }

        return max(0.0, (float) ($this->prezzo_unitario ?? 0));
    }

    public function costoListinoPerUnita(UnitaMisura|string|null $unitaMisura = null): float
    {
        $uomValue = $this->resolveUnitaMisuraValue($unitaMisura);

        if ($uomValue === UnitaMisura::MC->value) {
            return max(0.0, (float) ($this->costo_unitario ?? 0));
        }

        // Cost currently uses a single canonical field across U.M.
        return max(0.0, (float) ($this->costo_unitario ?? 0));
    }

    private function resolveUnitaMisuraValue(UnitaMisura|string|null $unitaMisura = null): ?string
    {
        if ($unitaMisura instanceof UnitaMisura) {
            return $unitaMisura->value;
        }

        if (is_string($unitaMisura) && $unitaMisura !== '') {
            return strtolower($unitaMisura);
        }

        return $this->unita_misura?->value;
    }

    private function normalizeNullableNumber(mixed $value, ?int $precision = null): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = max(0.0, (float) $value);

        return $precision !== null ? round($normalized, $precision) : $normalized;
    }
}
