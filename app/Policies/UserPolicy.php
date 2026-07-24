<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if (in_array($ability, ['update', 'resetPassword', 'deactivate', 'activate', 'delete'], true) && ! $user->isSuperAdmin()) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return ! $model->isSuperAdmin() || $model->id === $user->id;
    }

    public function resetPassword(User $user, User $model): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return ! $model->isSuperAdmin() || $model->id === $user->id;
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->isSuperAdmin()
            && $model->id !== $user->id
            && ! $model->isSuperAdmin();
    }

    public function activate(User $user, User $model): bool
    {
        return $user->isSuperAdmin()
            && $model->id !== $user->id
            && ! $model->isSuperAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin()
            && $model->id !== $user->id
            && ! $model->isSuperAdmin();
    }
}
