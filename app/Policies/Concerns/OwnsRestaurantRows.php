<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * A policy for a restaurant's own rows (categories, dishes, offers, social
 * links): an admin may do anything, an owner only on their restaurant's.
 */
trait OwnsRestaurantRows
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
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
