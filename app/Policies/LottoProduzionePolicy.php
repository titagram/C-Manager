<?php

namespace App\Policies;

use App\Enums\StatoLottoProduzione;
use App\Enums\UserRole;
use App\Models\LottoProduzione;
use App\Models\User;

class LottoProduzionePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LottoProduzione $lotto): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, LottoProduzione $lotto): bool
    {
        if (!$lotto->canBeModified()) {
            return false;
        }

        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, LottoProduzione $lotto): bool
    {
        // Only BOZZA, CONFERMATO and ANNULLATO can be deleted
        if (!in_array($lotto->stato, [
            StatoLottoProduzione::BOZZA,
            StatoLottoProduzione::CONFERMATO,
            StatoLottoProduzione::ANNULLATO,
        ])) {
            return false;
        }

        return $user->role === UserRole::ADMIN;
    }

    public function start(User $user, LottoProduzione $lotto): bool
    {
        return $user->role === UserRole::ADMIN
            && in_array($lotto->stato, [
                StatoLottoProduzione::BOZZA,
                StatoLottoProduzione::CONFERMATO,
            ], true);
    }

    public function complete(User $user, LottoProduzione $lotto): bool
    {
        return $lotto->stato === StatoLottoProduzione::IN_LAVORAZIONE;
    }

    public function cancel(User $user, LottoProduzione $lotto): bool
    {
        if ($lotto->stato === StatoLottoProduzione::COMPLETATO) {
            return false;
        }

        return $user->role === UserRole::ADMIN;
    }

    public function restore(User $user, LottoProduzione $lotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function forceDelete(User $user, LottoProduzione $lotto): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
