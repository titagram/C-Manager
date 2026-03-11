<?php

namespace App\Models;

use App\Enums\StatoOrdine;
use App\Services\ProgressivoGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ordine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ordini';

    protected $fillable = [
        'numero',
        'anno',
        'progressivo',
        'preventivo_id',
        'cliente_id',
        'data_ordine',
        'data_consegna_prevista',
        'data_consegna_effettiva',
        'stato',
        'descrizione',
        'note',
        'totale',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'data_ordine' => 'date',
            'data_consegna_prevista' => 'date',
            'data_consegna_effettiva' => 'date',
            'stato' => StatoOrdine::class,
            'totale' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function preventivo(): BelongsTo
    {
        return $this->belongsTo(Preventivo::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function righe(): HasMany
    {
        return $this->hasMany(OrdineRiga::class)->orderBy('ordine');
    }

    public function lottiProduzione(): HasMany
    {
        return $this->hasMany(LottoProduzione::class);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->stato, [
            StatoOrdine::CONFERMATO,
            StatoOrdine::IN_PRODUZIONE,
        ]);
    }

    public function ricalcolaTotale(): void
    {
        $this->totale = $this->righe()->sum('totale_riga');
        $this->save();
    }

    public function scopeByStato($query, StatoOrdine $stato)
    {
        return $query->where('stato', $stato);
    }

    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);
        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(numero) LIKE ?', ["%{$term}%"])
              ->orWhereRaw('LOWER(descrizione) LIKE ?', ["%{$term}%"])
              ->orWhereHas('cliente', function ($q2) use ($term) {
                  $q2->whereRaw('LOWER(ragione_sociale) LIKE ?', ["%{$term}%"]);
              });
        });
    }

    public function scopeInCorso($query)
    {
        return $query->whereIn('stato', [
            StatoOrdine::CONFERMATO,
            StatoOrdine::IN_PRODUZIONE,
            StatoOrdine::PRONTO,
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ordine) {
            $year = now()->year;

            // Set anno if not provided
            if (!$ordine->anno) {
                $ordine->anno = $year;
            }

            if (!$ordine->progressivo) {
                $ordine->progressivo = app(ProgressivoGeneratorService::class)
                    ->next('ordini', (int) $ordine->anno);
            }

            // Generate formatted numero for backwards compatibility
            if (!$ordine->numero) {
                $ordine->numero = sprintf('ORD-%d-%04d', $ordine->anno, $ordine->progressivo);
            }

            if (!$ordine->data_ordine) {
                $ordine->data_ordine = now();
            }
        });
    }
}
