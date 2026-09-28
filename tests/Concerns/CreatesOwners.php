<?php

namespace Tests\Concerns;

use App\Enums\Feature;
use App\Enums\UserRole;
use App\Models\Package;
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

    /** An onboarded owner on a named package rather than the default one. */
    protected function ownerOn(string $slug, array $restaurant = []): Restaurant
    {
        $package = Package::findBySlug($slug);

        $this->assertNotNull($package, "The [{$slug}] package is not seeded.");

        return $this->owner(array_merge(['package_id' => $package->id], $restaurant));
    }

    /** A live restaurant on the free classic template, ready for guests. */
    protected function published(array $restaurant = []): Restaurant
    {
        $template = Template::query()->firstWhere('slug', 'classic')
            ?? Template::factory()->withSettings([
                ['key' => 'primary_color', 'type' => 'color', 'default' => Template::DEFAULT_PRIMARY_COLOR],
            ])->create(['slug' => 'classic']);

        return Restaurant::factory()->create(array_merge([
            'is_active' => true,
            'template_id' => $template->id,
        ], $restaurant));
    }

    /**
     * Put features on the default package, which `owner()` and `published()`
     * land on. For tests about a feature itself rather than about which
     * package has it (those are in Tests\Feature\Packages).
     */
    protected function defaultPackageIncludes(Feature ...$features): void
    {
        foreach ($features as $feature) {
            Package::default()->setFeature($feature, 1);
        }
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
