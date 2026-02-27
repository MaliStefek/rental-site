<?php

namespace App\Policies;

use App\Models\ToolInventory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
    public function view(User $user, ToolInventory $toolInventory): bool
    {
        return $user->roles->contains('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ToolInventory $toolInventory): bool
    {
        return $user->roles->contains('admin'); // only admins can adjust stock
    }
}
