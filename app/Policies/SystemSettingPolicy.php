<?php

namespace App\Policies;

use App\Models\User;

final class SystemSettingPolicy
{
    /**
     * Determine if the user can manage system settings.
     */
    public function manage(User $user): bool
    {
        return $user->hasCapability('settings.manage');
    }
}
