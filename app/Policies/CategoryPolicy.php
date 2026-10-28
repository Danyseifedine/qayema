<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\OwnsRestaurantRows;

class CategoryPolicy
{
    use OwnsRestaurantRows;

    public function view(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->belongsToUserRestaurant($user, $category->restaurant_id);
    }
}
