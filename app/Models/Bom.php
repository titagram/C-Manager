<?php

namespace App\Models;

use App\Services\ProgressivoGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bom extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bom';

    protected $fillable = [
        'codice',
        'anno',
        'progressivo',
        'nome',
        'prodotto_id',
        'lotto_produzione_id',
        'ordine_id',
        'categoria_output',
        'versione',
        'is_active',
        'generated_at',
        'source',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function righe(): HasMany
    {
        return $this->hasMany(BomRiga::class)->orderBy('ordine');
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }

    public function lottoProduzione(): BelongsTo
    {
        return $this->belongsTo(LottoProduzione::class);
    }

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(Ordine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGenerated($query)
    {
        return $query->where('source', '!=', 'template');
    }

    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(codice) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(nome) LIKE ?', ["%{$term}%"])
              ->orWhereHas('ordine', function ($q2) use ($term) {
                  $q2->whereRaw('LOWER(numero) LIKE ?', ["%{$term}%"]);
              });
        });
    }

    /**
     * Calcola la quantita totale template della BOM (con scarto).
     * Questo valore e' una baseline di pianificazione, non una misurazione consuntiva runtime.
     */
    public function calcolaQuantitaTotaleTemplate(): float
    {
        return $this->righe->sum(function ($riga) {
            return $riga->quantita * (1 + $riga->coefficiente_scarto);
        });
    }

    /**
     * Backward-compatible alias.
     */
    public function calcolaQuantitaTotale(): float
    {
        return $this->calcolaQuantitaTotaleTemplate();
    }

    /**
     * Get material types (product IDs) from this BOM template.
     * Returns array of unique product IDs for materials.
     */
    public function getMaterialTypes(): array
    {
        return $this->righe()
            ->whereNotNull('prodotto_id')
            ->pluck('prodotto_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get all righe data as template array for use in production lots.
     * Includes product info, descriptions, and reference quantities.
     */
    public function getTemplateRighe(): array
    {
        return $this->righe->map(function ($riga) {
            return [
                'prodotto_id' => $riga->prodotto_id,
                'descrizione' => $riga->descrizione,
                'quantita_riferimento' => $riga->quantita,
                'unita_misura' => $riga->unita_misura?->value ?? 'mc',
                'coefficiente_scarto' => $riga->coefficiente_scarto,
                'is_fitok_required' => $riga->is_fitok_required,
                'note' => $riga->note,
            ];
        })->toArray();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bom) {
            $year = now()->year;

            // Set anno if not provided
            if (!$bom->anno) {
                $bom->anno = $year;
            }

            if (!$bom->progressivo) {
                $bom->progressivo = app(ProgressivoGeneratorService::class)
                    ->next('bom', (int) $bom->anno);
            }

            // Generate formatted codice for backwards compatibility
            if (!$bom->codice) {
                $bom->codice = sprintf('BOM-%d-%04d', $bom->anno, $bom->progressivo);
            }
        });
    }
}
