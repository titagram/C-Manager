<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Prodotto;
use App\Models\User;

class ProdottoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Prodotto $prodotto): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, Prodotto $prodotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, Prodotto $prodotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function restore(User $user, Prodotto $prodotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function forceDelete(User $user, Prodotto $prodotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
