<?php

namespace App\Policies;

use App\Models\ProductionEntry;
use App\Models\User;

/**
 * Policy for ProductionEntry authorization checks.
 */
final class ProductionPolicy
{
    /**
     * Determine if the user can view the list of production entries.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('production.view') || $user->hasCapability('production.record');
    }

    /**
     * Determine if the user can view a specific production entry.
     */
    public function view(User $user, ProductionEntry $entry): bool
    {
        return $user->hasCapability('production.view') || $user->hasCapability('production.record');
    }

    /**
     * Determine if the user can record a production entry.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('production.record');
    }

    /**
     * Determine if the user can update a production entry (typically not allowed, but policy checks).
     */
    public function update(User $user, ProductionEntry $entry): bool
    {
        return $user->hasCapability('production.record');
    }

    /**
     * Determine if the user can delete a production entry.
     */
    public function delete(User $user, ProductionEntry $entry): bool
    {
        return $user->hasCapability('production.record');
    }
}
