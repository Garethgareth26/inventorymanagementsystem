<?php

namespace App\Policies;

use App\Models\Bom;
use App\Models\User;

/**
 * Policy for Bom model authorization.
 */
final class BomPolicy
{
    /**
     * Determine if the user can view the BOM.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('bom.view') || $user->hasCapability('bom.manage');
    }

    /**
     * Determine if the user can view a specific BOM.
     */
    public function view(User $user, Bom $bom): bool
    {
        return $user->hasCapability('bom.view') || $user->hasCapability('bom.manage');
    }

    /**
     * Determine if the user can create a BOM.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('bom.manage');
    }

    /**
     * Determine if the user can update a BOM.
     */
    public function update(User $user, Bom|string|null $bom = null): bool
    {
        return $user->hasCapability('bom.manage');
    }

    /**
     * Determine if the user can delete a BOM.
     */
    public function delete(User $user, Bom|string|null $bom = null): bool
    {
        return $user->hasCapability('bom.manage');
    }
}
