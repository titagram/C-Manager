<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // All authenticated users can create clients
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return true; // All authenticated users can update clients
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function restore(User $user, Cliente $cliente): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function forceDelete(User $user, Cliente $cliente): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
