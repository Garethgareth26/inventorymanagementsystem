<?php

namespace App\Policies;

use App\Models\User;

class ProductionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('production.view');
    }

    public function record(User $user): bool
    {
        return $user->hasCapability('production.record');
    }
}
