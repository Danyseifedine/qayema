<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// A restaurant's orders, live on its owner's dashboard: only that owner.
Broadcast::channel('orders.{restaurantId}', fn (User $user, int $restaurantId): bool => $user->restaurant?->id === $restaurantId);
