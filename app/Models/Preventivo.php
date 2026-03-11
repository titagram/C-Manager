<?php

namespace App\Models;

use App\Enums\StatoPreventivo;
use App\Services\ProgressivoGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Preventivo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'preventivi';

    protected $fillable = [
        'numero',
        'anno',
        'progressivo',
        'cliente_id',
        'data',
        'validita_fino',
        'stato',
        'descrizione',
        'engine_version',
        'totale_materiali',
        'totale_lavorazioni',
        'totale',
        'input_snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'validita_fino' => 'date',
            'stato' => StatoPreventivo::class,
            'totale_materiali' => 'decimal:2',
            'totale_lavorazioni' => 'decimal:2',
            'totale' => 'decimal:2',
            'input_snapshot' => 'array',
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

    public function righe(): HasMany
    {
        return $this->hasMany(PreventivoRiga::class)->orderBy('ordine');
    }

    public function lottoProduzione(): HasOne
    {
        return $this->hasOne(LottoProduzione::class);
    }

    public function ordine(): HasOne
    {
        return $this->hasOne(Ordine::class);
    }

    public function isScaduto(): bool
    {
        if (!$this->validita_fino) {
            return false;
        }

        return $this->validita_fino->isPast() && $this->stato !== StatoPreventivo::ACCETTATO;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->stato, [StatoPreventivo::BOZZA]);
    }

    public function ricalcolaTotali(): void
    {
        $this->totale_materiali = $this->righe()->sum('totale_riga');
        $this->totale = $this->totale_materiali + $this->totale_lavorazioni;
        $this->save();
    }

    public function scopeByStato($query, StatoPreventivo $stato)
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($preventivo) {
            $year = now()->year;

            // Set anno if not provided
            if (!$preventivo->anno) {
                $preventivo->anno = $year;
            }

            if (!$preventivo->progressivo) {
                $preventivo->progressivo = app(ProgressivoGeneratorService::class)
                    ->next('preventivi', (int) $preventivo->anno);
            }

            // Generate formatted numero for backwards compatibility
            if (!$preventivo->numero) {
                $preventivo->numero = sprintf('PRV-%d-%04d', $preventivo->anno, $preventivo->progressivo);
            }
        });
    }
}
