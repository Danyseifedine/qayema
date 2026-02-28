<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentMenu() !== null;
    }

    public function view(User $user, Category $category): bool
    {
        return $this->belongsToUserMenu($user, $category->menu_id);
    }

    public function create(User $user): bool
    {
        return $user->currentMenu() !== null;
    }

    public function update(User $user, Category $category): bool
    {
        return $this->belongsToUserMenu($user, $category->menu_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->belongsToUserMenu($user, $category->menu_id);
    }

    public function restore(User $user, Category $category): bool
    {
        return $this->belongsToUserMenu($user, $category->menu_id);
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $this->belongsToUserMenu($user, $category->menu_id);
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
