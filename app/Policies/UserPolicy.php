<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->is($target) || $user->can('user.view');
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $target): bool
    {
        return ! $user->is($target) && $user->can('user.update') && (! $target->hasRole('Super Admin') || $user->hasRole('Super Admin'));
    }

    public function disable(User $user, User $target): bool
    {
        return ! $user->is($target) && $user->can('user.disable') && (! $target->hasRole('Super Admin') || $user->hasRole('Super Admin'));
    }
}
