<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponenteCostruzione extends Model
{
    use HasFactory;

    protected $table = 'componenti_costruzione';

    protected $fillable = [
        'costruzione_id',
        'nome',
        'calcolato',
        'tipo_dimensionamento',
        'formula_lunghezza',
        'formula_larghezza',
        'formula_quantita',
        'is_internal',
        'allow_rotation',
    ];

    protected $casts = [
        'tipo_dimensionamento' => 'string',
        'calcolato' => 'boolean',
        'is_internal' => 'boolean',
        'allow_rotation' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $tipo = strtoupper(trim((string) $model->tipo_dimensionamento));
            if ($tipo === '') {
                $tipo = (bool) $model->calcolato ? 'CALCOLATO' : 'MANUALE';
            }

            if (!in_array($tipo, ['CALCOLATO', 'MANUALE'], true)) {
                throw new \InvalidArgumentException(
                    'tipo_dimensionamento non valido. Valori ammessi: CALCOLATO, MANUALE.'
                );
            }

            $model->tipo_dimensionamento = $tipo;
            $model->calcolato = $tipo === 'CALCOLATO';
            $model->nome = trim((string) $model->nome);
            $model->formula_quantita = self::normalizeFormulaQuantita($model->formula_quantita);
            $model->formula_lunghezza = self::normalizeNullableFormula($model->formula_lunghezza);
            $model->formula_larghezza = self::normalizeNullableFormula($model->formula_larghezza);
        });
    }

    public function costruzione()
    {
        return $this->belongsTo(Costruzione::class);
    }

    public function lottiManuali()
    {
        return $this->hasMany(LottoComponenteManuale::class);
    }

    private static function normalizeFormulaQuantita(mixed $value): string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : '1';
    }

    private static function normalizeNullableFormula(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
