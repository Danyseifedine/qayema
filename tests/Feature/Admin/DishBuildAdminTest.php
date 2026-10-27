<?php

namespace Tests\Feature\Admin;

use App\Enums\BuildShape;
use App\Enums\Feature;
use App\Filament\Admin\Resources\Dishes\Pages\EditDish;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * "Build your own" on the admin's dish form: shown only for a restaurant
 * that has the feature, its builds and their choices saved with the dish.
 */
class DishBuildAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_section_shows_only_where_the_feature_is_on(): void
    {
        $dish = Dish::factory()->for($this->owner())->create();

        Livewire::test(EditDish::class, ['record' => $dish->id])->assertDontSee('Build your own');

        $this->defaultPackageIncludes(Feature::BuildYourOwn);
        Livewire::test(EditDish::class, ['record' => $dish->id])->assertSee('Build your own');
    }

    public function test_a_dishs_builds_are_edited_and_saved_with_it(): void
    {
        $this->defaultPackageIncludes(Feature::BuildYourOwn);
        $restaurant = $this->owner();
        $dish = Dish::factory()->for($restaurant)->create(['category_id' => Category::factory()->for($restaurant)->create()->id, 'price' => '4.00']);
        $build = DishBuild::factory()->for($dish)->pieces()->withChoices(['Vanilla' => 0, 'Chocolate' => 1])->create();

        $form = Livewire::test(EditDish::class, ['record' => $dish->id]);
        $key = array_key_first($form->get('data.builds'));

        $form->assertFormSet(["builds.{$key}.shape" => BuildShape::Pieces->value, "builds.{$key}.parts" => [3]])
            ->fillForm(["builds.{$key}.parts" => ['2', '3'], "builds.{$key}.name.en" => 'Pick your scoops'])
            ->call('save')
            ->assertHasNoFormErrors();

        $build->refresh();
        $this->assertSame([2, 3], $build->partCounts());
        $this->assertSame('Pick your scoops', $build->getTranslation('name', 'en'));
        $this->assertSame(2, $build->choices()->count());
    }

    public function test_a_category_build_may_be_kept_to_some_of_its_dishes(): void
    {
        $this->defaultPackageIncludes(Feature::BuildYourOwn);
        $restaurant = $this->owner();
        $pizzas = Category::factory()->for($restaurant)->create();
        $margherita = Dish::factory()->for($restaurant)->create(['category_id' => $pizzas->id]);
        Dish::factory()->for($restaurant)->create(['category_id' => $pizzas->id]);
        $dish = Dish::factory()->for($restaurant)->create(['category_id' => Category::factory()->for($restaurant)->create()->id, 'price' => null]);
        $build = DishBuild::factory()->for($dish)->create(['category_id' => $pizzas->id, 'parts' => [1, 2]]);

        $form = Livewire::test(EditDish::class, ['record' => $dish->id]);
        $key = array_key_first($form->get('data.builds'));

        $form->fillForm(["builds.{$key}.dish_ids" => [(string) $margherita->id]])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame([$margherita->id], $build->refresh()->dish_ids);

        // Emptied: every dish again.
        $form->fillForm(["builds.{$key}.dish_ids" => []])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNull($build->refresh()->dish_ids);
    }
}
