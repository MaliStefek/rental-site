<?php

declare(strict_types=1);

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
        return $user->roles->contains('admin'); // only admins can see all rentals
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Rental $rental): bool
    {
        if ($user->roles->contains('admin')) {
            return true;
        }

        return $user->id === $rental->user_id;
        // admins can view all, regular users can view their own rentals
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // any logged-in user can rent tools
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rental $rental): bool
    {
        // only the owner can edit, and only if it's still draft
        return $user->id === $rental->user_id && $rental->status === 'draft';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rental $rental): bool
    {
        // only the owner can delete, only if draft
        return $user->id === $rental->user_id && $rental->status === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Rental $rental): bool
    {
        return $user->roles->contains('admin'); // only admins can restore
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Rental $rental): bool
    {
        return $user->roles->contains('admin'); // only admins
    }
}
