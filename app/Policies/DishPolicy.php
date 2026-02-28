<?php

namespace App\Policies;

use App\Models\Dish;
use App\Models\User;

class DishPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentMenu() !== null;
    }

    public function view(User $user, Dish $dish): bool
    {
        return $this->belongsToUserMenu($user, $dish->menu_id);
    }

    public function create(User $user): bool
    {
        return $user->currentMenu() !== null;
    }

    public function update(User $user, Dish $dish): bool
    {
        return $this->belongsToUserMenu($user, $dish->menu_id);
    }

    public function delete(User $user, Dish $dish): bool
    {
        return $this->belongsToUserMenu($user, $dish->menu_id);
    }

    public function restore(User $user, Dish $dish): bool
    {
        return $this->belongsToUserMenu($user, $dish->menu_id);
    }

    public function forceDelete(User $user, Dish $dish): bool
    {
        return $this->belongsToUserMenu($user, $dish->menu_id);
    }

    private function belongsToUserMenu(User $user, int $menuId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $menu = $user->currentMenu();

        return $menu !== null && $menu->id === $menuId;
    }
}
