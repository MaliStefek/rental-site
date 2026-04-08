<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class ToolInventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->isAdmin();
    }
}
