<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function view(User $user, Offer $offer): bool
    {
        return $this->belongsToUserRestaurant($user, $offer->restaurant_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->restaurant !== null;
    }

    public function update(User $user, Offer $offer): bool
    {
        return $this->belongsToUserRestaurant($user, $offer->restaurant_id);
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $this->belongsToUserRestaurant($user, $offer->restaurant_id);
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
