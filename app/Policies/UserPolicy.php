<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

use App\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    public function before($user)
    {
        if ($user->hasRole([User::ROLE_ADMINISTRATOR, User::ROLE_IT])) {
            return true;
        }
    }

    public function show(User $user, User $userModel)
    {
        return $user->id === $userModel->id || $user->hasRole(User::ROLE_ADVISER);
    }

    public function update(User $user, User $userModel)
    {
        return $user->id === $userModel->id;
    }
}
