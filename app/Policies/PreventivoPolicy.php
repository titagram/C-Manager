<?php

namespace App\Policies;

use App\Enums\StatoPreventivo;
use App\Enums\UserRole;
use App\Models\Preventivo;
use App\Models\User;

class PreventivoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Preventivo $preventivo): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // All authenticated users can create quotes
    }

    public function update(User $user, Preventivo $preventivo): bool
    {
        // Only draft quotes can be edited
        if (!$preventivo->canBeEdited()) {
            return false;
        }

        // Admin can edit any, operators only their own
        return $user->role === UserRole::ADMIN || $preventivo->created_by === $user->id;
    }

    public function delete(User $user, Preventivo $preventivo): bool
    {
        // Only draft quotes can be deleted
        if ($preventivo->stato !== StatoPreventivo::BOZZA) {
            return false;
        }

        return $user->role === UserRole::ADMIN || $preventivo->created_by === $user->id;
    }

    public function changeStatus(User $user, Preventivo $preventivo): bool
    {
        return $user->role === UserRole::ADMIN || $preventivo->created_by === $user->id;
    }

    public function duplicate(User $user, Preventivo $preventivo): bool
    {
        return true; // All authenticated users can duplicate
    }

    public function exportPdf(User $user, Preventivo $preventivo): bool
    {
        return true;
    }

    public function restore(User $user, Preventivo $preventivo): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function forceDelete(User $user, Preventivo $preventivo): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
