<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ordine;
use App\Models\User;

class OrdinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function view(User $user, Ordine $ordine): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, Ordine $ordine): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, Ordine $ordine): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function changeStatus(User $user, Ordine $ordine): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function restore(User $user, Ordine $ordine): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function forceDelete(User $user, Ordine $ordine): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
