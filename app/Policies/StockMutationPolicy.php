<?php

namespace App\Policies;

use App\Models\MutasiStok;
use App\Models\User;

/**
 * Policy for MutasiStok authorization checks (Movements ledger & Adjustments).
 */
final class StockMutationPolicy
{
    /**
     * Determine if the user can view the list of stock mutations (ledger).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('stock.view') || $user->hasCapability('stock.mutate');
    }

    /**
     * Determine if the user can view a specific mutation.
     */
    public function view(User $user, MutasiStok $mutation): bool
    {
        return $user->hasCapability('stock.view') || $user->hasCapability('stock.mutate');
    }

    /**
     * Determine if the user can create a stock adjustment.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('stock.adjust');
    }

    /**
     * Determine if the user can update/modify adjustments (typically read-only/immutable).
     */
    public function update(User $user, MutasiStok $mutation): bool
    {
        return $user->hasCapability('stock.adjust');
    }

    /**
     * Determine if the user can delete an adjustment (typically read-only/immutable).
     */
    public function delete(User $user, MutasiStok $mutation): bool
    {
        return $user->hasCapability('stock.adjust');
    }
}
