<?php

namespace App\Policies;

use App\Models\FinishedGood;
use App\Models\User;

/**
 * Policy for FinishedGood model authorization.
 */
final class FinishedGoodPolicy
{
    /**
     * Determine if the user can view the list of finished goods.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('finished-good.view') || $user->hasCapability('finished-good.manage');
    }

    /**
     * Determine if the user can view a specific finished good.
     */
    public function view(User $user, FinishedGood $finishedGood): bool
    {
        return $user->hasCapability('finished-good.view') || $user->hasCapability('finished-good.manage');
    }

    /**
     * Determine if the user can create a finished good.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('finished-good.manage');
    }

    /**
     * Determine if the user can update a finished good.
     */
    public function update(User $user, FinishedGood $finishedGood): bool
    {
        return $user->hasCapability('finished-good.manage');
    }

    /**
     * Determine if the user can delete a finished good.
     */
    public function delete(User $user, FinishedGood $finishedGood): bool
    {
        return $user->hasCapability('finished-good.manage');
    }
}
