<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ToolInventory;
use App\Models\User;
use App\Models\Asset;

class ToolInventoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->roles->contains('admin'); // only admins see stock info
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->roles->contains('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->roles->contains('admin'); // only admins can adjust stock
    }
}
