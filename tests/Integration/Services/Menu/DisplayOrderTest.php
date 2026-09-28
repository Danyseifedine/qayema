<?php

namespace Tests\Integration\Services\Menu;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Menu\DisplayOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class DisplayOrderTest extends TestCase
{
    use CreatesOwners;
    use RefreshDatabase;

    /**
     * @return array<int, Category>
     */
    private function categories(Restaurant $restaurant, int $count): array
    {
        return Category::factory()->count($count)->for($restaurant)->create()->all();
    }

    /**
     * @return array<int, int> id => display_order
     */
    private function orderOf(Restaurant $restaurant): array
    {
        return Category::query()->where('restaurant_id', $restaurant->id)->orderBy('id')->pluck('display_order', 'id')->all();
    }

    public function test_it_numbers_the_list_from_one_in_the_order_given(): void
    {
        $restaurant = $this->owner();
        [$a, $b, $c] = $this->categories($restaurant, 3);

        DisplayOrder::apply($restaurant->categories(), [$c->id, $a->id, $b->id]);

        $this->assertSame([$a->id => 2, $b->id => 3, $c->id => 1], $this->orderOf($restaurant));
        $this->assertSame([$c->id, $a->id, $b->id], $restaurant->categories()->pluck('id')->all());
    }

    public function test_the_keys_of_the_list_do_not_matter_only_its_order(): void
    {
        $restaurant = $this->owner();
        [$a, $b] = $this->categories($restaurant, 2);

        DisplayOrder::apply($restaurant->categories(), [7 => $b->id, 3 => $a->id]);

        $this->assertSame([$a->id => 2, $b->id => 1], $this->orderOf($restaurant));
    }

    public function test_it_is_one_statement_however_long_the_list(): void
    {
        $restaurant = $this->owner();
        $ids = array_map(fn (Category $category): int => $category->id, $this->categories($restaurant, 12));

        DB::enableQueryLog();
        DisplayOrder::apply($restaurant->categories(), array_reverse($ids));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries);
        $this->assertStringStartsWith('update', strtolower($queries[0]['query']));
        $this->assertSame(range(12, 1), array_values($this->orderOf($restaurant)));
    }

    public function test_an_empty_list_touches_nothing(): void
    {
        $restaurant = $this->owner();
        $this->categories($restaurant, 2);

        DB::enableQueryLog();
        DisplayOrder::apply($restaurant->categories(), []);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame([], $queries);
        $this->assertSame([0, 0], array_values($this->orderOf($restaurant)));
    }

    public function test_rows_left_out_of_the_list_keep_their_order(): void
    {
        $restaurant = $this->owner();
        [$a, $b, $c] = $this->categories($restaurant, 3);
        $c->update(['display_order' => 9]);

        DisplayOrder::apply($restaurant->categories(), [$b->id, $a->id]);

        $this->assertSame([$a->id => 2, $b->id => 1, $c->id => 9], $this->orderOf($restaurant));
    }

    public function test_another_restaurants_row_in_the_list_is_left_alone(): void
    {
        $restaurant = $this->owner();
        [$mine] = $this->categories($restaurant, 1);
        $other = $this->owner();
        [$theirs] = $this->categories($other, 1);
        $theirs->update(['display_order' => 5]);

        DisplayOrder::apply($restaurant->categories(), [$theirs->id, $mine->id]);

        $this->assertSame(5, $theirs->fresh()->display_order, 'A tampered id stays out of reach.');
        $this->assertSame(2, $mine->fresh()->display_order, 'Numbered by its place in the list as sent.');
    }

    public function test_extra_columns_are_set_on_the_same_rows(): void
    {
        $restaurant = $this->owner();
        [$from, $to] = $this->categories($restaurant, 2);
        $moved = Dish::factory()->for($restaurant)->create(['category_id' => $from->id]);
        $sibling = Dish::factory()->for($restaurant)->create(['category_id' => $to->id]);
        $untouched = Dish::factory()->for($restaurant)->create(['category_id' => $from->id, 'display_order' => 4]);

        DisplayOrder::apply($restaurant->dishes(), [$sibling->id, $moved->id], ['category_id' => $to->id]);

        $this->assertSame([$to->id, 2], [$moved->fresh()->category_id, $moved->fresh()->display_order]);
        $this->assertSame([$to->id, 1], [$sibling->fresh()->category_id, $sibling->fresh()->display_order]);
        $this->assertSame([$from->id, 4], [$untouched->fresh()->category_id, $untouched->fresh()->display_order]);
    }

    public function test_ids_sent_as_numeric_strings_are_read_as_numbers(): void
    {
        $restaurant = $this->owner();
        [$a, $b] = $this->categories($restaurant, 2);

        DisplayOrder::apply($restaurant->categories(), [(string) $b->id, (string) $a->id]);

        $this->assertSame([$a->id => 2, $b->id => 1], $this->orderOf($restaurant));
    }

    /** The CASE is built from integers only, so text in an id cannot reach the SQL. */
    public function test_an_id_carrying_sql_changes_nothing(): void
    {
        $restaurant = $this->owner();
        [$a, $b] = $this->categories($restaurant, 2);

        DisplayOrder::apply($restaurant->categories(), [$a->id.' then 99 end, name = null --']);

        $this->assertSame([0, 0], array_values($this->orderOf($restaurant)));
        $this->assertNotNull($a->fresh()->getTranslation('name', 'en', false));
        $this->assertNotNull($b->fresh()->getTranslation('name', 'en', false));
    }
}
