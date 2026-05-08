<?php

namespace App\Policies;

use App\Models\Dish;
use App\Models\User;

class DishPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function view(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function update(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
    }

    public function delete(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
    }

    public function restore(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
    }

    public function forceDelete(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
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
