<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionSettingHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'type',
        'old_value',
        'new_value',
        'source',
        'changed_reason',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'changed_by' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException('Le righe di storico production settings sono append-only e non possono essere modificate.');
        });

        static::deleting(function (): void {
            throw new \LogicException('Le righe di storico production settings non possono essere eliminate applicativamente.');
        });
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
