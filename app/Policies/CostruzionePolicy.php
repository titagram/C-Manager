<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Costruzione;
use App\Models\User;

class CostruzionePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Costruzione $costruzione): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Costruzione $costruzione): bool
    {
        return true;
    }

    public function delete(User $user, Costruzione $costruzione): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function restore(User $user, Costruzione $costruzione): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function forceDelete(User $user, Costruzione $costruzione): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
