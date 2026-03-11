<?php

namespace App\Models;

use App\Enums\LottoPricingMode;
use App\Enums\StatoLottoProduzione;
use App\Services\ProgressivoGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LottoProduzione extends Model
{
    use HasFactory, SoftDeletes;

    public const FITOK_CERT_STATUS_CERTIFIABLE = 'certificabile_fitok';

    public const FITOK_CERT_STATUS_MIXED = 'misto_non_certificabile_fitok';

    public const FITOK_CERT_STATUS_NON_FITOK = 'non_fitok';

    public const FITOK_CERT_STATUS_PENDING = 'in_attesa_calcolo_fitok';

    protected $table = 'lotti_produzione';

    protected $fillable = [
        'codice_lotto',
        'anno',
        'progressivo',
        'cliente_id',
        'preventivo_id',
        'ordine_id',
        'ordine_riga_id',
        'prodotto_finale',
        'costruzione_id',
        // Dimensioni cassa (cm)
        'larghezza_cm',
        'profondita_cm',
        'altezza_cm',
        // Tipo e spessori
        'tipo_prodotto',
        'spessore_base_mm',
        'spessore_fondo_mm',
        // Produzione
        'numero_pezzi',
        'numero_univoco',
        'optimizer_result',
        // Calcoli
        'volume_totale_mc',
        'peso_kg_mc',
        'peso_totale_kg',
        'prezzo_calcolato',
        'pricing_mode',
        'tariffa_mc',
        'ricarico_percentuale',
        'prezzo_finale_override',
        'prezzo_finale',
        'prezzo_calcolato_at',
        'pricing_snapshot',
        // FITOK
        'fitok_percentuale',
        'fitok_volume_mc',
        'non_fitok_volume_mc',
        'fitok_calcolato_at',
        // Standard
        'descrizione',
        'stato',
        'data_inizio',
        'data_fine',
        'avviato_at',
        'completato_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'stato' => StatoLottoProduzione::class,
            'data_inizio' => 'date',
            'data_fine' => 'date',
            'avviato_at' => 'datetime',
            'completato_at' => 'datetime',
            'optimizer_result' => 'array',
            // Dimensioni
            'larghezza_cm' => 'decimal:2',
            'profondita_cm' => 'decimal:2',
            'altezza_cm' => 'decimal:2',
            'spessore_base_mm' => 'decimal:2',
            'spessore_fondo_mm' => 'decimal:2',
            // Calcoli
            'volume_totale_mc' => 'decimal:6',
            'peso_kg_mc' => 'decimal:2',
            'peso_totale_kg' => 'decimal:2',
            'prezzo_calcolato' => 'decimal:2',
            'pricing_mode' => LottoPricingMode::class,
            'tariffa_mc' => 'decimal:2',
            'ricarico_percentuale' => 'decimal:2',
            'prezzo_finale_override' => 'decimal:2',
            'prezzo_finale' => 'decimal:2',
            'prezzo_calcolato_at' => 'datetime',
            'pricing_snapshot' => 'array',
            // FITOK
            'fitok_percentuale' => 'decimal:2',
            'fitok_volume_mc' => 'decimal:6',
            'non_fitok_volume_mc' => 'decimal:6',
            'fitok_calcolato_at' => 'datetime',
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

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(Ordine::class);
    }

    public function ordineRiga(): BelongsTo
    {
        return $this->belongsTo(OrdineRiga::class);
    }

    public function costruzione(): BelongsTo
    {
        return $this->belongsTo(Costruzione::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function consumiMateriale(): HasMany
    {
        return $this->hasMany(ConsumoMateriale::class);
    }

    /**
     * Alias for consumiMateriale relationship
     */
    public function consumi(): HasMany
    {
        return $this->hasMany(ConsumoMateriale::class);
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(MovimentoMagazzino::class);
    }

    public function materialiUsati(): HasMany
    {
        return $this->hasMany(LottoProduzioneMateriale::class)->orderBy('ordine');
    }

    public function primaryMaterialProfiles(): HasMany
    {
        return $this->hasMany(LottoPrimaryMaterialProfile::class)->orderBy('ordine');
    }

    public function righePreventivoCollegate(): HasMany
    {
        return $this->hasMany(PreventivoRiga::class, 'lotto_produzione_id');
    }

    public function scarti(): HasMany
    {
        return $this->hasMany(Scarto::class);
    }

    public function componentiManuali(): HasMany
    {
        return $this->hasMany(LottoComponenteManuale::class);
    }

    public function canBeModified(): bool
    {
        return in_array($this->stato, [
            StatoLottoProduzione::BOZZA,
            StatoLottoProduzione::CONFERMATO,
            StatoLottoProduzione::IN_LAVORAZIONE,
        ]);
    }

    public function hasTechnicalDefinition(): bool
    {
        if ($this->costruzione_id !== null) {
            return true;
        }

        if (! empty($this->optimizer_result)) {
            return true;
        }

        if ($this->relationLoaded('materialiUsati')) {
            if ($this->materialiUsati->isNotEmpty()) {
                return true;
            }
        } elseif ($this->materialiUsati()->exists()) {
            return true;
        }

        if ($this->relationLoaded('componentiManuali')) {
            if ($this->componentiManuali->isNotEmpty()) {
                return true;
            }
        } elseif ($this->componentiManuali()->exists()) {
            return true;
        }

        return false;
    }

    public function isPlaceholderBozza(): bool
    {
        return $this->stato === StatoLottoProduzione::BOZZA
            && ! $this->hasTechnicalDefinition();
    }

    public function avviaLavorazione(): void
    {
        if (! in_array($this->stato, [StatoLottoProduzione::BOZZA, StatoLottoProduzione::CONFERMATO])) {
            return;
        }
        $this->stato = StatoLottoProduzione::IN_LAVORAZIONE;
        $this->data_inizio = now();
        $this->avviato_at = now();
        $this->save();
    }

    public function completaLavorazione(): void
    {
        $this->stato = StatoLottoProduzione::COMPLETATO;
        $this->data_fine = now();
        $this->completato_at = now();
        $this->save();
    }

    public function annulla(): void
    {
        $this->stato = StatoLottoProduzione::ANNULLATO;
        $this->save();
    }

    /**
     * Calcola e salva la quota FITOK aggregando i consumi
     */
    public function calcolaFitok(): void
    {
        $totals = $this->consumi()
            ->with('lottoMateriale.prodotto')
            ->get()
            ->reduce(function ($carry, $consumo) {
                $isFitok = $consumo->lottoMateriale?->prodotto?->soggetto_fitok ?? false;
                if ($isFitok) {
                    $carry['fitok'] += $consumo->quantita;
                } else {
                    $carry['non_fitok'] += $consumo->quantita;
                }

                return $carry;
            }, ['fitok' => 0, 'non_fitok' => 0]);

        $totalVolume = $totals['fitok'] + $totals['non_fitok'];

        $this->fitok_volume_mc = $totals['fitok'];
        $this->non_fitok_volume_mc = $totals['non_fitok'];
        $this->fitok_percentuale = $totalVolume > 0
            ? round(($totals['fitok'] / $totalVolume) * 100, 2)
            : 0;
        $this->fitok_calcolato_at = now();
        $this->save();
    }

    /**
     * Verifica se il lotto è 100% FITOK compliant
     */
    public function isFitokCompliant(): bool
    {
        return $this->getFitokCertificationStatus() === self::FITOK_CERT_STATUS_CERTIFIABLE;
    }

    public function isFitokMixed(): bool
    {
        return $this->getFitokCertificationStatus() === self::FITOK_CERT_STATUS_MIXED;
    }

    public function isFitokNonCertificabile(): bool
    {
        return in_array($this->getFitokCertificationStatus(), [
            self::FITOK_CERT_STATUS_MIXED,
            self::FITOK_CERT_STATUS_NON_FITOK,
        ], true);
    }

    public function getFitokCertificationStatus(): string
    {
        return self::resolveFitokCertificationStatusFromPercentuale(
            $this->fitok_percentuale !== null ? (float) $this->fitok_percentuale : null
        );
    }

    public function getFitokCertificationStatusLabel(): string
    {
        return self::resolveFitokCertificationStatusLabel($this->getFitokCertificationStatus());
    }

    public static function resolveFitokCertificationStatusFromPercentuale(?float $fitokPercentuale): string
    {
        if ($fitokPercentuale === null) {
            return self::FITOK_CERT_STATUS_PENDING;
        }

        if ($fitokPercentuale >= 100.0) {
            return self::FITOK_CERT_STATUS_CERTIFIABLE;
        }

        if ($fitokPercentuale > 0.0) {
            return self::FITOK_CERT_STATUS_MIXED;
        }

        return self::FITOK_CERT_STATUS_NON_FITOK;
    }

    public static function resolveFitokCertificationStatusLabel(string $status): string
    {
        return match ($status) {
            self::FITOK_CERT_STATUS_CERTIFIABLE => 'Certificabile FITOK',
            self::FITOK_CERT_STATUS_MIXED => 'Misto (non certificabile FITOK)',
            self::FITOK_CERT_STATUS_NON_FITOK => 'Non FITOK',
            self::FITOK_CERT_STATUS_PENDING => 'In attesa calcolo FITOK',
            default => 'In attesa calcolo FITOK',
        };
    }

    public static function resolveFitokCertificationLabelFromPercentuale(?float $fitokPercentuale): string
    {
        return self::resolveFitokCertificationStatusLabel(
            self::resolveFitokCertificationStatusFromPercentuale($fitokPercentuale)
        );
    }

    /**
     * Restituisce le dimensioni formattate (es. "80x80x120")
     */
    public function getDimensioniAttribute(): ?string
    {
        if (! $this->larghezza_cm && ! $this->profondita_cm && ! $this->altezza_cm) {
            return null;
        }

        return sprintf(
            '%sx%sx%s',
            (int) $this->larghezza_cm,
            (int) $this->profondita_cm,
            (int) $this->altezza_cm
        );
    }

    /**
     * Calcola e salva volume e peso totale
     */
    public function calcolaTotali(): void
    {
        if ($this->larghezza_cm && $this->profondita_cm && $this->altezza_cm) {
            // Volume in MC = L*P*A (cm) / 1.000.000
            $this->volume_totale_mc = ($this->larghezza_cm * $this->profondita_cm * $this->altezza_cm * $this->numero_pezzi) / 1000000;

            // Peso totale = volume * peso specifico
            $this->peso_totale_kg = $this->volume_totale_mc * ($this->peso_kg_mc ?? 360);

            $this->save();
        }
    }

    /**
     * Calcola il costo totale del lotto sommando i costi dei materiali utilizzati
     */
    public function calcolaCostoTotale(): float
    {
        return $this->materialiUsati()->sum('costo_materiale') ?? 0;
    }

    /**
     * Calcola il prezzo di vendita totale del lotto sommando i prezzi di vendita dei materiali
     */
    public function calcolaPrezzoVenditaTotale(): float
    {
        return $this->materialiUsati()->sum('prezzo_vendita') ?? 0;
    }

    public function calcolaPrezzoDaRicarico(float $costoTotale): array
    {
        $ricarico = (float) ($this->ricarico_percentuale ?? 0);
        $prezzoCalcolato = round($costoTotale * (1 + ($ricarico / 100)), 2);
        $prezzoFinale = $this->prezzo_finale_override !== null
            ? (float) $this->prezzo_finale_override
            : $prezzoCalcolato;

        return [
            'prezzo_calcolato' => $prezzoCalcolato,
            'prezzo_finale' => round($prezzoFinale, 2),
        ];
    }

    public function scopeByStato($query, StatoLottoProduzione $stato)
    {
        return $query->where('stato', $stato);
    }

    public function scopeInCorso($query)
    {
        return $query->whereIn('stato', [
            StatoLottoProduzione::BOZZA,
            StatoLottoProduzione::CONFERMATO,
            StatoLottoProduzione::IN_LAVORAZIONE,
        ]);
    }

    public function scopeSearch($query, string $term)
    {
        $term = strtolower($term);

        return $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(codice_lotto) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(prodotto_finale) LIKE ?', ["%{$term}%"])
                ->orWhereHas('cliente', function ($q2) use ($term) {
                    $q2->whereRaw('LOWER(ragione_sociale) LIKE ?', ["%{$term}%"]);
                });
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lotto) {
            $year = now()->year;

            // Set anno if not provided
            if (! $lotto->anno) {
                $lotto->anno = $year;
            }

            if (! $lotto->progressivo) {
                $lotto->progressivo = app(ProgressivoGeneratorService::class)
                    ->next('lotti_produzione', (int) $lotto->anno);
            }

            // Generate formatted codice_lotto for backwards compatibility
            if (! $lotto->codice_lotto) {
                $lotto->codice_lotto = sprintf('LP-%d-%04d', $lotto->anno, $lotto->progressivo);
            }

            // Auto-generate numero_univoco from progressivo
            if (! $lotto->numero_univoco) {
                $lotto->numero_univoco = sprintf('%d-%03d', $lotto->anno, $lotto->progressivo);
            }
        });

        static::deleting(function (LottoProduzione $lotto): void {
            // Keep preventivo consistency: when a lotto is removed (soft/hard),
            // linked preventivo rows must be removed as requested by business rules.
            $lotto->righePreventivoCollegate()->delete();
        });
    }
}
