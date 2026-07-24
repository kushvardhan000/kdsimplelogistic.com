<?php

namespace App\Policies;

use App\Models\TransportLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransportLogPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, TransportLog $log): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, TransportLog $log): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, TransportLog $log): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, TransportLog $log): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, TransportLog $log): bool
    {
        return $user->isSuperAdmin();
    }
}
