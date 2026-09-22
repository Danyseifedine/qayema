<?php

namespace Tests\Concerns;

use App\Enums\CoinTransactionType;
use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;

/**
 * The fixtures almost every feature test starts from, so they are built one
 * way and read one way.
 */
trait CreatesOwners
{
    /** An onboarded owner with a restaurant and no template chosen yet. */
    protected function owner(array $restaurant = []): Restaurant
    {
        return Restaurant::factory()->create(array_merge(['template_id' => null], $restaurant));
    }

    /** An owner whose wallet already holds `$coins`. */
    protected function ownerWithCoins(int $coins, array $restaurant = []): Restaurant
    {
        $owner = $this->owner($restaurant);

        if ($coins > 0) {
            $owner->user->wallet()->credit($coins, CoinTransactionType::AdminGrant);
        }

        return $owner;
    }

    /** A live restaurant on the free classic template, ready for guests. */
    protected function published(array $restaurant = []): Restaurant
    {
        $template = Template::query()->firstWhere('slug', 'classic')
            ?? Template::factory()->withSettings([
                ['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A'],
            ])->create(['slug' => 'classic']);

        return Restaurant::factory()->create(array_merge([
            'is_active' => true,
            'template_id' => $template->id,
            'template_settings' => $template->defaultSettings(),
        ], $restaurant));
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    /** A signed-in user with no restaurant at all (mid-onboarding). */
    protected function userWithoutRestaurant(): User
    {
        return User::factory()->create();
    }
}
