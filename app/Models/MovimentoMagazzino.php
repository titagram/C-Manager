<?php

namespace App\Models;

use App\Enums\TipoMovimento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentoMagazzino extends Model
{
    use HasFactory;

    public const REASON_CODE_COUNT_MISMATCH = 'errore_conteggio';
    public const REASON_CODE_DAMAGE = 'danneggiamento_materiale';
    public const REASON_CODE_UNREGISTERED_SCRAP = 'scarto_non_registrato';
    public const REASON_CODE_SUSPECTED_SHORTAGE = 'sospetto_ammanco';

    protected $table = 'movimenti_magazzino';

    protected $fillable = [
        'lotto_materiale_id',
        'tipo',
        'quantita',
        'documento_id',
        'lotto_produzione_id',
        'causale',
        'causale_codice',
        'created_by',
        'data_movimento',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimento::class,
            'quantita' => 'decimal:4',
            'data_movimento' => 'datetime',
            'causale_codice' => 'string',
        ];
    }

    public function lottoMateriale(): BelongsTo
    {
        return $this->belongsTo(LottoMateriale::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function lottoProduzione(): BelongsTo
    {
        return $this->belongsTo(LottoProduzione::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getQuantitaConSegnoAttribute(): float
    {
        return $this->tipo->isPositive() ? $this->quantita : -$this->quantita;
    }

    /**
     * @return array<string, string>
     */
    public static function negativeAdjustmentReasonCodeOptions(): array
    {
        return [
            self::REASON_CODE_COUNT_MISMATCH => 'Errore conteggio inventario',
            self::REASON_CODE_DAMAGE => 'Danneggiamento materiale',
            self::REASON_CODE_UNREGISTERED_SCRAP => 'Scarto non registrato',
            self::REASON_CODE_SUSPECTED_SHORTAGE => 'Sospetto ammanco',
        ];
    }

    public static function requiresStructuredReasonCodeFor(TipoMovimento|string $tipo): bool
    {
        $tipoValue = $tipo instanceof TipoMovimento ? $tipo->value : (string) $tipo;

        return $tipoValue === TipoMovimento::RETTIFICA_NEGATIVA->value;
    }

    public static function isValidNegativeAdjustmentReasonCode(?string $code): bool
    {
        if (!$code) {
            return false;
        }

        return array_key_exists($code, self::negativeAdjustmentReasonCodeOptions());
    }

    public function scopeByTipo($query, TipoMovimento $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeCarichi($query)
    {
        return $query->whereIn('tipo', [TipoMovimento::CARICO, TipoMovimento::RETTIFICA_POSITIVA]);
    }

    public function scopeScarichi($query)
    {
        return $query->whereIn('tipo', [TipoMovimento::SCARICO, TipoMovimento::RETTIFICA_NEGATIVA]);
    }

    public function scopeFitok($query)
    {
        return $query->whereHas('lottoMateriale.prodotto', function ($q) {
            $q->where('soggetto_fitok', true);
        });
    }

    public function scopeInPeriodo($query, $dataInizio, $dataFine)
    {
        return $query->whereBetween('data_movimento', [$dataInizio, $dataFine]);
    }

    public function scopeRecenti($query, int $limit = 10)
    {
        return $query->orderByDesc('data_movimento')->limit($limit);
    }

    protected static function booted(): void
    {
        static::saving(function (self $movimento): void {
            if (!self::requiresStructuredReasonCodeFor($movimento->tipo)) {
                return;
            }

            if (!self::isValidNegativeAdjustmentReasonCode($movimento->causale_codice)) {
                throw new \InvalidArgumentException(
                    'Per rettifiche negative e obbligatorio un codice causale strutturato valido.'
                );
            }
        });
    }
}
