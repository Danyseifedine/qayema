<?php

namespace Tests\Integration\Policies;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use App\Policies\CategoryPolicy;
use App\Policies\DishPolicy;
use App\Policies\RestaurantSocialLinkPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The three policies over a restaurant's own content (categories, dishes,
 * social links) share one rule: an admin may do anything, an owner only to
 * their own restaurant's rows, and a user without a restaurant nothing.
 */
class RestaurantContentPolicyTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /**
     * @return array<string, array{0: class-string<Model>, 1: class-string, 2: list<string>}>
     */
    public static function policies(): array
    {
        $abilities = ['view', 'update', 'delete'];

        return [
            'category' => [Category::class, CategoryPolicy::class, $abilities],
            'dish' => [Dish::class, DishPolicy::class, $abilities],
            'social link' => [RestaurantSocialLink::class, RestaurantSocialLinkPolicy::class, $abilities],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function rowOf(string $model, Restaurant $restaurant): Model
    {
        return $model::factory()->create(['restaurant_id' => $restaurant->id]);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $policy
     */
    #[DataProvider('policies')]
    public function test_the_policy_is_registered_for_its_model(string $model, string $policy): void
    {
        $this->assertInstanceOf($policy, Gate::getPolicyFor($model));
    }

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $policy
     * @param  list<string>  $abilities
     */
    #[DataProvider('policies')]
    public function test_an_admin_may_do_everything_to_any_restaurants_row(string $model, string $policy, array $abilities): void
    {
        $admin = $this->admin();
        $row = $this->rowOf($model, $this->owner());

        $this->assertTrue($admin->can('viewAny', $model));
        $this->assertTrue($admin->can('create', $model));
        foreach ($abilities as $ability) {
            $this->assertTrue($admin->can($ability, $row), "Admin should be allowed to {$ability}.");
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $policy
     * @param  list<string>  $abilities
     */
    #[DataProvider('policies')]
    public function test_an_owner_may_do_everything_to_their_own_rows(string $model, string $policy, array $abilities): void
    {
        $restaurant = $this->owner();
        $row = $this->rowOf($model, $restaurant);
        $owner = $restaurant->user;

        $this->assertTrue($owner->can('viewAny', $model));
        $this->assertTrue($owner->can('create', $model));
        foreach ($abilities as $ability) {
            $this->assertTrue($owner->can($ability, $row), "Owner should be allowed to {$ability} their own row.");
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $policy
     * @param  list<string>  $abilities
     */
    #[DataProvider('policies')]
    public function test_an_owner_may_not_touch_another_restaurants_rows(string $model, string $policy, array $abilities): void
    {
        $row = $this->rowOf($model, $this->owner());
        $stranger = $this->owner()->user;

        foreach ($abilities as $ability) {
            $this->assertFalse($stranger->can($ability, $row), "Another owner must not {$ability}.");
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $policy
     * @param  list<string>  $abilities
     */
    #[DataProvider('policies')]
    public function test_a_user_without_a_restaurant_may_do_nothing(string $model, string $policy, array $abilities): void
    {
        $row = $this->rowOf($model, $this->owner());
        $user = $this->userWithoutRestaurant();

        $this->assertFalse($user->can('viewAny', $model));
        $this->assertFalse($user->can('create', $model));
        foreach ($abilities as $ability) {
            $this->assertFalse($user->can($ability, $row), "A user without a restaurant must not {$ability}.");
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  class-string  $policy
     */
    #[DataProvider('policies')]
    public function test_the_policy_called_directly_matches_the_gate(string $model, string $policy): void
    {
        $restaurant = $this->owner();
        $row = $this->rowOf($model, $restaurant);
        $instance = new $policy;

        $this->assertTrue($instance->update($this->admin(), $row));
        $this->assertTrue($instance->update($restaurant->user, $row));
        $this->assertFalse($instance->update($this->owner()->user, $row));
        $this->assertFalse($instance->update($this->userWithoutRestaurant(), $row));
    }

    /**
     * Nothing here is soft-deleted, so there is nothing to restore.
     *
     * @param  class-string<Model>  $model
     * @param  class-string  $policy
     */
    #[DataProvider('policies')]
    public function test_there_is_no_restore_or_force_delete_so_the_gate_denies_both(string $model, string $policy): void
    {
        $restaurant = $this->owner();
        $row = $this->rowOf($model, $restaurant);

        $this->assertFalse(method_exists($policy, 'restore'));
        $this->assertFalse(method_exists($policy, 'forceDelete'));
        $this->assertFalse($restaurant->user->can('restore', $row));
        $this->assertFalse($restaurant->user->can('forceDelete', $row));
    }

    public function test_a_dish_moved_to_another_restaurant_leaves_its_old_owner(): void
    {
        $first = $this->owner();
        $second = $this->owner();
        $dish = $this->rowOf(Dish::class, $first);

        $this->assertTrue($first->user->can('update', $dish));

        $dish->restaurant_id = $second->id;

        $this->assertFalse($first->user->can('update', $dish));
        $this->assertTrue($second->user->can('update', $dish));
    }

    public function test_an_account_without_a_restaurant_reaches_no_rows(): void
    {
        // Someone still setting up has no restaurant; a deleted restaurant
        // takes its owner's account with it, so that account is gone.
        $restaurant = $this->owner();
        $owner = $restaurant->user;
        $category = $this->rowOf(Category::class, $restaurant);
        $settingUp = $this->userWithoutRestaurant();

        $this->assertFalse($settingUp->can('update', $category));
        $this->assertFalse($settingUp->can('create', Category::class));

        $restaurant->delete();
        $this->assertModelMissing($owner);
    }

    public function test_ownership_is_judged_by_restaurant_id_not_by_user_id(): void
    {
        $this->userWithoutRestaurant();
        $mine = $this->owner();
        $theirs = $this->owner();
        $owner = $mine->user;
        $link = $this->rowOf(RestaurantSocialLink::class, $theirs);

        $this->assertSame($owner->id, $theirs->id, 'Fixture: the owner\'s user id equals the other restaurant\'s id.');
        $this->assertNotSame($mine->id, $theirs->id);
        $this->assertFalse($owner->can('update', $link));
        $this->assertFalse($owner->can('delete', $link));
    }
}
