<?php

namespace App\Policies;

use App\Models\Dish;
use App\Models\User;
use App\Policies\Concerns\OwnsRestaurantRows;

class DishPolicy
{
    use OwnsRestaurantRows;

    public function view(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
    }

    public function update(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
    }

    public function delete(User $user, Dish $dish): bool
    {
        return $this->belongsToUserRestaurant($user, $dish->restaurant_id);
    }
}
