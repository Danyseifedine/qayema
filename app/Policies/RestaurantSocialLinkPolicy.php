<?php

namespace App\Policies;

use App\Models\RestaurantSocialLink;
use App\Models\User;

class RestaurantSocialLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function view(User $user, RestaurantSocialLink $link): bool
    {
        return $this->belongsToUserRestaurant($user, $link->restaurant_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function update(User $user, RestaurantSocialLink $link): bool
    {
        return $this->belongsToUserRestaurant($user, $link->restaurant_id);
    }

    public function delete(User $user, RestaurantSocialLink $link): bool
    {
        return $this->belongsToUserRestaurant($user, $link->restaurant_id);
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
