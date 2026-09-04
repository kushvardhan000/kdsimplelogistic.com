<?php

namespace App\Policies;

use App\Models\AccountTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountTransactionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, AccountTransaction $model): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AccountTransaction $model): bool
    {
        return false;
    }

    public function delete(User $user, AccountTransaction $model): bool
    {
        return false;
    }
}
