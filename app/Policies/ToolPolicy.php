<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tool;
use App\Models\User;

class ToolPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // all users can view tool listings
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tool $tool): bool
    {
        return $tool->is_active || $user->roles->contains('admin');
        // anyone can see active tools, admins can see all
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->roles->contains('admin'); // only admins can add tools
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tool $tool): bool
    {
        return $user->roles->contains('admin'); // only admins can edit
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tool $tool): bool
    {
        return $user->roles->contains('admin'); // only admins can delete
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tool $tool): bool
    {
        return $user->roles->contains('admin'); // only admins
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tool $tool): bool
    {
        return $user->roles->contains('admin'); // only admins
    }
}
