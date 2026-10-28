<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;
use App\Policies\Concerns\OwnsRestaurantRows;

class OfferPolicy
{
    use OwnsRestaurantRows;

    public function view(User $user, Offer $offer): bool
    {
        return $this->belongsToUserRestaurant($user, $offer->restaurant_id);
    }

    public function update(User $user, Offer $offer): bool
    {
        return $this->belongsToUserRestaurant($user, $offer->restaurant_id);
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $this->belongsToUserRestaurant($user, $offer->restaurant_id);
    }
}
