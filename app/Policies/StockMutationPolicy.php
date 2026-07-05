<?php

namespace App\Policies;

use App\Models\User;

class StockMutationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('stock.view');
    }

    public function mutate(User $user): bool
    {
        return $user->hasCapability('stock.mutate');
    }

    public function adjust(User $user): bool
    {
        return $user->hasCapability('stock.adjust');
    }
}
