<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->role->permissions());
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(MovimentoMagazzino::class, 'created_by');
    }

    public function documenti(): HasMany
    {
        return $this->hasMany(Documento::class, 'created_by');
    }

    public function preventivi(): HasMany
    {
        return $this->hasMany(Preventivo::class, 'created_by');
    }

    public function lottiProduzione(): HasMany
    {
        return $this->hasMany(LottoProduzione::class, 'created_by');
    }
}
