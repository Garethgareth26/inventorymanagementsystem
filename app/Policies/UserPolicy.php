<?php

namespace App\Policies;

use App\Models\User;

/**
 * Policy for User model and dashboard authorization.
 */
final class UserPolicy
{
    /**
     * Determine if the user can view the owner dashboard.
     */
    public function viewOwnerDashboard(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine if the user can view the employee dashboard.
     */
    public function viewEmployeeDashboard(User $user): bool
    {
        return $user->isKaryawan();
    }

    /**
     * Determine if the user can manage users (Owner only).
     */
    public function manage(User $user): bool
    {
        return $user->hasCapability('user.manage');
    }
}
