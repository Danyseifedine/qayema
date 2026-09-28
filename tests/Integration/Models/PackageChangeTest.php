<?php

namespace Tests\Integration\Models;

use App\Models\Package;
use App\Models\PackageChange;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One line of package history: which restaurant, from what, to what, when
 * and by whom. Written once, so it has no updated_at.
 */
class PackageChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_the_restaurant_and_points_at_both_packages_and_the_admin(): void
    {
        $restaurant = Restaurant::factory()->create();
        $admin = User::factory()->admin()->create();
        $free = Package::default();
        $pro = Package::findBySlug('pro');

        $change = PackageChange::factory()->create([
            'restaurant_id' => $restaurant->id,
            'changed_by' => $admin->id,
            'starts_at' => '2026-10-01 00:00:00',
            'ends_at' => '2027-10-01 00:00:00',
            'note' => 'Paid by bank transfer',
        ])->fresh();

        $this->assertSame($restaurant->id, $change->restaurant_id);
        $this->assertTrue($change->fromPackage->is($free));
        $this->assertTrue($change->toPackage->is($pro));
        $this->assertTrue($change->changedBy->is($admin));
        $this->assertSame('2026-10-01', $change->starts_at->toDateString());
        $this->assertSame('2027-10-01', $change->ends_at->toDateString());
        $this->assertSame('Paid by bank transfer', $change->note);
    }

    public function test_it_is_written_once_without_an_updated_at(): void
    {
        $change = PackageChange::factory()->create();

        $this->assertNull(PackageChange::UPDATED_AT);
        $this->assertNotNull($change->created_at);
        $this->assertArrayNotHasKey('updated_at', $change->fresh()->getAttributes());
    }

    public function test_the_first_line_of_a_new_restaurant_has_no_from_package_and_no_admin(): void
    {
        $restaurant = Restaurant::factory()->create();

        $first = $restaurant->packageChanges()->sole();

        $this->assertNull($first->fromPackage);
        $this->assertNull($first->changedBy);
        $this->assertTrue($first->toPackage->is(Package::default()));
    }

    public function test_deleting_the_admin_keeps_the_line_but_forgets_who(): void
    {
        $admin = User::factory()->admin()->create();
        $change = PackageChange::factory()->create(['changed_by' => $admin->id]);

        $admin->delete();

        $this->assertModelExists($change);
        $this->assertNull($change->fresh()->changed_by);
    }
}
