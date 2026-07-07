<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

/**
 * Policy for Supplier model authorization.
 */
final class SupplierPolicy
{
    /**
     * Determine if the user can view the list of suppliers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('supplier.view') || $user->hasCapability('supplier.manage');
    }

    /**
     * Determine if the user can view a specific supplier.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasCapability('supplier.view') || $user->hasCapability('supplier.manage');
    }

    /**
     * Determine if the user can create a supplier.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('supplier.manage');
    }

    /**
     * Determine if the user can update a supplier.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasCapability('supplier.manage');
    }

    /**
     * Determine if the user can delete a supplier.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasCapability('supplier.manage');
    }
}
