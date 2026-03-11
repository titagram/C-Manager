<?php

namespace App\Models;

use App\Enums\TipoMovimento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LottoMateriale extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lotti_materiale';

    protected $fillable = [
        'codice_lotto',
        'prodotto_id',
        'fornitore_id',  // FK a fornitori
        'data_arrivo',
        'fornitore',     // Campo legacy per retrocompatibilità
        'numero_ddt',
        'quantita_iniziale',
        'peso_totale_kg',
        'fitok_certificato',
        'fitok_data_trattamento',
        'fitok_tipo_trattamento',
        'fitok_paese_origine',
        'lunghezza_mm',
        'larghezza_mm',
        'spessore_mm',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'data_arrivo' => 'date',
            'fitok_data_trattamento' => 'date',
            'quantita_iniziale' => 'decimal:4',
            'peso_totale_kg' => 'decimal:3',
            'lunghezza_mm' => 'decimal:2',
            'larghezza_mm' => 'decimal:2',
            'spessore_mm' => 'decimal:2',
        ];
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }

    public function fornitore(): BelongsTo
    {
        return $this->belongsTo(Fornitore::class);
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(MovimentoMagazzino::class);
    }

    public function consumiMateriale(): HasMany
    {
        return $this->hasMany(ConsumoMateriale::class);
    }

    /**
     * Calcola la giacenza attuale del lotto basandosi sui movimenti
     */
    public function getGiacenzaAttribute(): float
    {
        $saldoMovimenti = (float) ($this->movimenti()
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN tipo IN ('carico', 'rettifica_positiva') THEN quantita
                    ELSE -quantita
                END), 0) as giacenza
            ")
            ->value('giacenza') ?? 0);

        $haCaricoIniziale = $this->movimenti()
            ->where('tipo', TipoMovimento::CARICO->value)
            ->exists();

        if ($haCaricoIniziale) {
            return $saldoMovimenti;
        }

        $baseline = (float) ($this->quantita_iniziale ?? 0);

        return round($baseline + $saldoMovimenti, 4);
    }

    public function isFitok(): bool
    {
        return $this->prodotto?->soggetto_fitok ?? false;
    }

    public function hasFitokData(): bool
    {
        return $this->fitok_certificato !== null;
    }

    public function getDimensioniAttribute(): ?string
    {
        if (!$this->lunghezza_mm && !$this->larghezza_mm && !$this->spessore_mm) {
            return null;
        }

        return sprintf(
            '%s x %s x %s mm',
            $this->lunghezza_mm ?? '-',
            $this->larghezza_mm ?? '-',
            $this->spessore_mm ?? '-'
        );
    }

    public function scopeConGiacenza($query)
    {
        return $query->withCount(['movimenti as giacenza' => function ($q) {
            $q->selectRaw("
                COALESCE(SUM(CASE
                    WHEN tipo IN ('carico', 'rettifica_positiva') THEN quantita
                    ELSE -quantita
                END), 0)
            ");
        }]);
    }

    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(codice_lotto) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(fornitore) LIKE ?', ["%{$term}%"])
              ->orWhereHas('prodotto', function ($q2) use ($term) {
                  $q2->whereRaw('LOWER(nome) LIKE ?', ["%{$term}%"])
                     ->orWhereRaw('LOWER(codice) LIKE ?', ["%{$term}%"]);
              });
        });
    }
}
