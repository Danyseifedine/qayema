<?php

namespace Tests\Integration\Services\Analytics;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuEvent;
use App\Models\Restaurant;
use App\Services\Analytics\MenuEventRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * MenuEventRecorder called directly: what it keeps, what it drops, and what
 * it cleans, without the form request in front of it.
 */
class MenuEventRecorderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function request(?string $sessionId = null): Request
    {
        $request = Request::create('/menu/events', 'POST', server: [
            'REMOTE_ADDR' => '198.51.100.7',
            'HTTP_USER_AGENT' => 'TestAgent/1.0',
        ]);

        if ($sessionId !== null) {
            $session = new Store('test', new ArraySessionHandler(10), $sessionId);
            $request->setLaravelSession($session);
        }

        return $request;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    private function record(Restaurant $restaurant, array $events, ?Request $request = null): int
    {
        return app(MenuEventRecorder::class)->record($restaurant, $request ?? $this->request(), $events);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function stored(): array
    {
        return MenuEvent::query()->orderBy('id')->get()
            ->map(fn (MenuEvent $event): array => [
                'type' => $event->type->value,
                'dish_id' => $event->dish_id,
                'category_id' => $event->category_id,
                'value' => $event->value,
            ])
            ->all();
    }

    public function test_an_empty_batch_stores_nothing(): void
    {
        $this->assertSame(0, $this->record($this->owner(), []));
        $this->assertDatabaseCount('menu_events', 0);
    }

    public function test_every_row_carries_the_restaurant_the_session_and_the_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:34:56', 'UTC'));
        $restaurant = $this->owner();

        $count = $this->record($restaurant, [['type' => 'map'], ['type' => 'call']], $this->request(str_repeat('s', 40)));

        $this->assertSame(2, $count);
        $events = MenuEvent::query()->get();
        $this->assertCount(2, $events);

        foreach ($events as $event) {
            $this->assertSame($restaurant->id, $event->restaurant_id);
            $this->assertSame(str_repeat('s', 40), $event->session_id);
            $this->assertSame('2026-09-28 12:34:56', $event->occurred_at->format('Y-m-d H:i:s'));
        }
    }

    public function test_without_a_session_the_visitor_is_the_ip_and_agent(): void
    {
        $this->record($this->owner(), [['type' => 'whatsapp']]);

        $this->assertSame(md5('198.51.100.7'.'TestAgent/1.0'), MenuEvent::query()->value('session_id'));
    }

    public function test_another_restaurants_category_is_dropped(): void
    {
        $restaurant = $this->owner();
        $mine = Category::factory()->for($restaurant)->create();
        $theirs = Category::factory()->create();

        $count = $this->record($restaurant, [
            ['type' => 'category_open', 'category_id' => $theirs->id],
            ['type' => 'category_open', 'category_id' => $mine->id],
            ['type' => 'category_open', 'category_id' => 999999],
        ]);

        $this->assertSame(1, $count);
        $this->assertSame([['type' => 'category_open', 'dish_id' => null, 'category_id' => $mine->id, 'value' => null]], $this->stored());
    }

    public function test_a_dish_or_category_event_without_its_id_is_dropped(): void
    {
        $restaurant = $this->owner();

        $count = $this->record($restaurant, [
            ['type' => 'dish_add'],
            ['type' => 'dish_add', 'dish_id' => null],
            ['type' => 'category_open'],
            ['type' => 'category_open', 'category_id' => 0],
        ]);

        $this->assertSame(0, $count);
        $this->assertDatabaseCount('menu_events', 0);
    }

    public function test_ids_sent_as_strings_are_matched(): void
    {
        $restaurant = $this->owner();
        $dish = Dish::factory()->for($restaurant)->create();

        $this->assertSame(1, $this->record($restaurant, [['type' => 'dish_add', 'dish_id' => (string) $dish->id]]));
        $this->assertSame($dish->id, MenuEvent::query()->value('dish_id'));
    }

    public function test_fields_a_type_does_not_carry_are_cleared(): void
    {
        $restaurant = $this->owner();
        $dish = Dish::factory()->for($restaurant)->create();
        $category = Category::factory()->for($restaurant)->create();
        $all = ['dish_id' => $dish->id, 'category_id' => $category->id, 'value' => 'instagram'];

        $count = $this->record($restaurant, [
            ['type' => 'dish_add', ...$all],
            ['type' => 'category_open', ...$all],
            ['type' => 'social', ...$all],
            ['type' => 'map', ...$all],
        ]);

        $this->assertSame(4, $count);
        $this->assertSame([
            ['type' => 'dish_add', 'dish_id' => $dish->id, 'category_id' => null, 'value' => null],
            ['type' => 'category_open', 'dish_id' => null, 'category_id' => $category->id, 'value' => null],
            ['type' => 'social', 'dish_id' => null, 'category_id' => null, 'value' => 'instagram'],
            ['type' => 'map', 'dish_id' => null, 'category_id' => null, 'value' => null],
        ], $this->stored());
    }

    public function test_a_value_type_with_no_real_value_is_dropped(): void
    {
        $count = $this->record($this->owner(), [
            ['type' => 'search'],
            ['type' => 'search', 'value' => null],
            ['type' => 'search', 'value' => "   \t  "],
            ['type' => 'social', 'value' => ''],
            ['type' => 'language'],
        ]);

        $this->assertSame(0, $count);
        $this->assertDatabaseCount('menu_events', 0);
    }

    public function test_values_are_squished_lowercased_and_cut_to_sixty_four(): void
    {
        $restaurant = $this->owner();

        $this->record($restaurant, [
            ['type' => 'search', 'value' => "  ÉCLAIR \n au   Chocolat "],
            ['type' => 'social', 'value' => ' Instagram '],
            ['type' => 'search_miss', 'value' => str_repeat('Ab', 50)],
        ]);

        $this->assertSame(
            ['éclair au chocolat', 'instagram', str_repeat('ab', 32)],
            MenuEvent::query()->orderBy('id')->pluck('value')->all(),
        );
    }

    public function test_a_search_needs_two_letters_counted_as_characters(): void
    {
        $this->record($this->owner(), [
            ['type' => 'search', 'value' => 'ab'],
            ['type' => 'search', 'value' => 'a'],
            ['type' => 'search_miss', 'value' => 'فل'],
            ['type' => 'search_miss', 'value' => 'ف'],
            // Other value types have no minimum length.
            ['type' => 'social', 'value' => 'x'],
        ]);

        $this->assertSame(['ab', 'فل', 'x'], MenuEvent::query()->orderBy('id')->pluck('value')->all());
    }

    public function test_a_language_must_be_one_the_menu_is_shown_in(): void
    {
        $restaurant = $this->owner(['second_locale' => 'ar']);

        // Free has no multiple_languages, so the menu is English only.
        $this->record($restaurant, [
            ['type' => 'language', 'value' => 'ar'],
            ['type' => 'language', 'value' => ' EN '],
        ]);

        $this->assertSame(['en'], MenuEvent::query()->pluck('value')->all());

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        MenuEvent::query()->delete();

        $this->record($restaurant->fresh(), [
            ['type' => 'language', 'value' => 'AR'],
            ['type' => 'language', 'value' => 'fr'],
        ]);

        $this->assertSame(['ar'], MenuEvent::query()->pluck('value')->all());
    }

    public function test_an_unknown_type_drops_the_whole_batch_without_throwing(): void
    {
        $count = $this->record($this->owner(), [
            ['type' => 'map'],
            ['type' => 'hack'],
        ]);

        $this->assertSame(0, $count);
        $this->assertDatabaseCount('menu_events', 0);
    }

    public function test_a_failure_to_write_never_throws(): void
    {
        $restaurant = $this->owner();
        DB::statement('DROP TABLE menu_events');

        $this->assertSame(0, $this->record($restaurant, [['type' => 'map']]));
    }
}
