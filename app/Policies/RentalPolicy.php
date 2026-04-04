<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEmployee();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Rental $rental): bool
    {
        return $user->isAdmin() || $user->isEmployee() || $user->id === $rental->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rental $rental): bool
    {
        return $user->isAdmin() || $user->isEmployee();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rental $rental): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Rental $rental): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Rental $rental): bool
    {
        return $user->isAdmin();
    }
}
