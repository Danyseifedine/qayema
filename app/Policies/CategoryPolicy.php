<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function view(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function update(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }

    public function restore(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }

    private function belongsToUserRestaurant(User $user, int $restaurantId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $restaurant = $user->restaurant;

        return $restaurant !== null && $restaurant->id === $restaurantId;
    }
}
