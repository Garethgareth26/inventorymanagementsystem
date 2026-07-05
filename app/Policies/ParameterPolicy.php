<?php

namespace App\Policies;

use App\Models\User;

class ParameterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('parameter.view');
    }

    public function simulate(User $user): bool
    {
        return $user->hasCapability('parameter.simulate');
    }

    public function apply(User $user): bool
    {
        return $user->hasCapability('parameter.apply');
    }
}
