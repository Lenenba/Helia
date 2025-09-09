<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Menu;

class MenuPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Example: admins can do everything
        return $user->is_admin ?? null;
    }
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Menu $menu): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return true;
    }
    public function update(User $user, Menu $menu): bool
    {
        return true;
    }
    public function delete(User $user, Menu $menu): bool
    {
        return true;
    }
}
