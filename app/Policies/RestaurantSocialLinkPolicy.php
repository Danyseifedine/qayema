<?php

namespace App\Policies;

use App\Models\RestaurantSocialLink;
use App\Models\User;
use App\Policies\Concerns\OwnsRestaurantRows;

class RestaurantSocialLinkPolicy
{
    use OwnsRestaurantRows;

    public function view(User $user, RestaurantSocialLink $link): bool
    {
        return $this->belongsToUserRestaurant($user, $link->restaurant_id);
    }

    public function update(User $user, RestaurantSocialLink $link): bool
    {
        return $this->belongsToUserRestaurant($user, $link->restaurant_id);
    }

    public function delete(User $user, RestaurantSocialLink $link): bool
    {
        return $this->belongsToUserRestaurant($user, $link->restaurant_id);
    }
}
