<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LottoMateriale;
use App\Models\User;

class LottoMaterialePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LottoMateriale $lotto): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, LottoMateriale $lotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, LottoMateriale $lotto): bool
    {
        // Can only delete if no movements exist
        if ($lotto->movimenti()->count() > 0) {
            return false;
        }

        return $user->role === UserRole::ADMIN;
    }

    public function carico(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function scarico(User $user, LottoMateriale $lotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function rettifica(User $user, LottoMateriale $lotto): bool
    {
        return $user->role === UserRole::ADMIN; // Only admins can adjust
    }

    public function restore(User $user, LottoMateriale $lotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function forceDelete(User $user, LottoMateriale $lotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
