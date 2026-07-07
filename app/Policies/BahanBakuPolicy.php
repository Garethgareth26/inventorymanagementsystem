<?php

namespace App\Policies;

use App\Models\BahanBaku;
use App\Models\User;

/**
 * Policy for BahanBaku model authorization.
 */
final class BahanBakuPolicy
{
    /**
     * Determine if the user can view the list of raw materials.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('material.view') || $user->hasCapability('material.manage');
    }

    /**
     * Determine if the user can view a specific raw material.
     */
    public function view(User $user, BahanBaku $bahanBaku): bool
    {
        return $user->hasCapability('material.view') || $user->hasCapability('material.manage');
    }

    /**
     * Determine if the user can create a raw material.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('material.manage');
    }

    /**
     * Determine if the user can update a raw material.
     */
    public function update(User $user, BahanBaku $bahanBaku): bool
    {
        return $user->hasCapability('material.manage');
    }

    /**
     * Determine if the user can delete a raw material.
     */
    public function delete(User $user, BahanBaku $bahanBaku): bool
    {
        return $user->hasCapability('material.manage');
    }
}
