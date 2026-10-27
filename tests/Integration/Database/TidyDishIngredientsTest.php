<?php

namespace Tests\Integration\Database;

use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class TidyDishIngredientsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_existing_ingredients_are_tidied_once(): void
    {
        $restaurant = $this->owner();
        $messy = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $clean = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $empty = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        // Written as an old save left them, around the model's own tidying.
        DB::table('dishes')->where('id', $messy->id)->update(['ingredients' => json_encode(['en' => 'onion,..tomato...ect', 'ar' => 'بصل،طماطم'])]);
        DB::table('dishes')->where('id', $clean->id)->update(['ingredients' => json_encode(['en' => 'Lamb, rice'])]);
        DB::table('dishes')->where('id', $empty->id)->update(['ingredients' => json_encode(['en' => 'etc...'])]);

        $migration = require database_path('migrations/2026_10_19_100004_tidy_dish_ingredients.php');
        $migration->up();

        $this->assertSame(['en' => 'onion, tomato', 'ar' => 'بصل، طماطم'], $messy->fresh()->getTranslations('ingredients'));
        $this->assertSame(['en' => 'Lamb, rice'], $clean->fresh()->getTranslations('ingredients'));
        $this->assertNull(DB::table('dishes')->where('id', $empty->id)->value('ingredients'));
    }
}
