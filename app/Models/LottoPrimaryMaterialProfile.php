<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LottoPrimaryMaterialProfile extends Model
{
    use HasFactory;

    protected $table = 'lotto_primary_material_profiles';

    protected $fillable = [
        'lotto_produzione_id',
        'profile_key',
        'prodotto_id',
        'ordine',
    ];

    protected function casts(): array
    {
        return [
            'ordine' => 'integer',
        ];
    }

    public function lottoProduzione(): BelongsTo
    {
        return $this->belongsTo(LottoProduzione::class);
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(Prodotto::class);
    }
}
