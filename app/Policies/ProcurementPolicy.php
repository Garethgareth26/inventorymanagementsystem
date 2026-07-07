<?php

namespace App\Policies;

use App\Models\PesananPembelian;
use App\Models\User;

/**
 * Policy for PesananPembelian (Purchase Order) authorization checks.
 */
final class ProcurementPolicy
{
    /**
     * Determine if the user can view the list of POs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('procurement.view') || $user->hasCapability('procurement.manage');
    }

    /**
     * Determine if the user can view a specific PO.
     */
    public function view(User $user, PesananPembelian $po): bool
    {
        return $user->hasCapability('procurement.view') || $user->hasCapability('procurement.manage');
    }

    /**
     * Determine if the user can create a PO.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('procurement.manage');
    }

    /**
     * Determine if the user can update/transition a PO.
     */
    public function update(User $user, PesananPembelian $po): bool
    {
        return $user->hasCapability('procurement.manage');
    }

    /**
     * Determine if the user can cancel/delete a PO.
     */
    public function delete(User $user, PesananPembelian $po): bool
    {
        return $user->hasCapability('procurement.manage');
    }
}
