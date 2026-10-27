<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * The owner's orders. `manage` (move, edit or delete one from the
 * dashboard) is for its own restaurant only: the order endpoints work on the
 * signed-in user's restaurant, and an admin who also owns one must not reach
 * another's. `delete` is what /admin asks: an admin removes orders on the
 * restaurant's page there.
 */
class OrderPolicy
{
    public function manage(User $user, Order $order): Response
    {
        return $this->ownOrder($user, $order);
    }

    public function delete(User $user, Order $order): Response
    {
        return $user->isAdmin() ? Response::allow() : $this->ownOrder($user, $order);
    }

    /**
     * Denied with the same words as an owner without a restaurant gets
     * (ResolvesRestaurant), not the framework's untranslated default.
     */
    private function ownOrder(User $user, Order $order): Response
    {
        return $user->restaurant !== null && $user->restaurant->id === $order->restaurant_id
            ? Response::allow()
            : Response::deny(__('You do not have access to this.'));
    }
}
