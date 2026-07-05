<?php

namespace App\Policies;

use App\Models\User;

class ProcurementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('procurement.view');
    }

    public function manage(User $user): bool
    {
        return $user->hasCapability('procurement.manage');
    }
}
