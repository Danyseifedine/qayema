<?php

namespace App\Policies;

use App\Models\MenuSocialLink;
use App\Models\User;

class MenuSocialLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentMenu() !== null;
    }

    public function view(User $user, MenuSocialLink $menuSocialLink): bool
    {
        return $this->belongsToUserMenu($user, $menuSocialLink->menu_id);
    }

    public function create(User $user): bool
    {
        return $user->currentMenu() !== null;
    }

    public function update(User $user, MenuSocialLink $menuSocialLink): bool
    {
        return $this->belongsToUserMenu($user, $menuSocialLink->menu_id);
    }

    public function delete(User $user, MenuSocialLink $menuSocialLink): bool
    {
        return $this->belongsToUserMenu($user, $menuSocialLink->menu_id);
    }

    public function restore(User $user, MenuSocialLink $menuSocialLink): bool
    {
        return $this->belongsToUserMenu($user, $menuSocialLink->menu_id);
    }

    public function forceDelete(User $user, MenuSocialLink $menuSocialLink): bool
    {
        return $this->belongsToUserMenu($user, $menuSocialLink->menu_id);
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
