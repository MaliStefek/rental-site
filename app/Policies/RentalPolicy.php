<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RentalStatus;
use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isEmployee();
    }

    public function view(User $user, Rental $rental): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) return true;
        return $user->id === $rental->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Rental $rental): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isEmployee();
    }

    public function delete(User $user, Rental $rental): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Rental $rental): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Rental $rental): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, Rental $rental): bool
    {
        if ($user->isAdmin() || $user->isEmployee()) return true;

        return $user->id === $rental->user_id && $rental->status === RentalStatus::CONFIRMED;
    }
}