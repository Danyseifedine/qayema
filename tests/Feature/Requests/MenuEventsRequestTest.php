<?php

namespace Tests\Feature\Requests;

use App\Enums\MenuEventType;
use App\Models\MenuEvent;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** App\Http\Requests\MenuEventsRequest, through POST /{slug}/events. */
class MenuEventsRequestTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @param  array<string, mixed>  $body */
    private function send(Restaurant $restaurant, array $body): TestResponse
    {
        return $this->postJson(route('public.events', $restaurant->slug), $body);
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string, 2: string}> */
    public static function badBatches(): array
    {
        $whatsapp = ['type' => 'whatsapp'];

        return [
            'no events' => [[], 'events', 'The events field is required.'],
            'events null' => [['events' => null], 'events', 'The events field is required.'],
            'events a string' => [['events' => 'whatsapp'], 'events', 'The events field must be an array.'],
            'an empty batch' => [['events' => []], 'events', 'The events field is required.'],
            'twenty-six events' => [['events' => array_fill(0, 26, $whatsapp)], 'events', 'The events field must not have more than 25 items.'],
            'no type' => [['events' => [['dish_id' => 1]]], 'events.0.type', 'The events.0.type field is required.'],
            'an unknown type' => [['events' => [['type' => 'teleport']]], 'events.0.type', 'The selected events.0.type is invalid.'],
            'a type that is not a string' => [['events' => [['type' => 5]]], 'events.0.type', 'The events.0.type field must be a string.'],
            'a dish id that is not a number' => [['events' => [['type' => 'dish_add', 'dish_id' => 'soup']]], 'events.0.dish_id', 'The events.0.dish_id field must be an integer.'],
            'a dish id of zero' => [['events' => [['type' => 'dish_add', 'dish_id' => 0]]], 'events.0.dish_id', 'The events.0.dish_id field must be at least 1.'],
            'a fractional dish id' => [['events' => [['type' => 'dish_add', 'dish_id' => 1.5]]], 'events.0.dish_id', 'The events.0.dish_id field must be an integer.'],
            'a category id that is not a number' => [['events' => [['type' => 'category_open', 'category_id' => 'mains']]], 'events.0.category_id', 'The events.0.category_id field must be an integer.'],
            'a negative category id' => [['events' => [['type' => 'category_open', 'category_id' => -3]]], 'events.0.category_id', 'The events.0.category_id field must be at least 1.'],
            'a value over 64 characters' => [['events' => [['type' => 'search', 'value' => str_repeat('a', 65)]]], 'events.0.value', 'The events.0.value field must not be greater than 64 characters.'],
            'a value that is not a string' => [['events' => [['type' => 'search', 'value' => ['soup']]]], 'events.0.value', 'The events.0.value field must be a string.'],
            'a bad second event' => [['events' => [$whatsapp, ['type' => 'nope']]], 'events.1.type', 'The selected events.1.type is invalid.'],
        ];
    }

    /** @param  array<string, mixed>  $body */
    #[DataProvider('badBatches')]
    public function test_a_malformed_batch_is_refused_and_nothing_is_stored(array $body, string $field, string $message): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);

        $this->assertSame(0, MenuEvent::query()->count());
    }

    /** Every type passes validation; which ones carry enough to keep is the recorder's call. */
    public function test_every_event_type_is_accepted(): void
    {
        $restaurant = $this->published();
        $events = array_map(fn (MenuEventType $type): array => ['type' => $type->value], MenuEventType::cases());

        $this->send($restaurant, ['events' => $events])->assertNoContent();

        $this->assertDatabaseHas('menu_events', ['restaurant_id' => $restaurant->id, 'type' => MenuEventType::WhatsApp->value]);
    }

    public function test_a_batch_of_exactly_twenty_five_is_accepted(): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, ['events' => array_fill(0, 25, ['type' => 'call'])])->assertNoContent();

        $this->assertSame(25, MenuEvent::query()->count());
    }

    public function test_the_optional_fields_may_be_null_and_a_value_may_be_sixty_four_characters(): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, ['events' => [
            ['type' => 'whatsapp', 'dish_id' => null, 'category_id' => null, 'value' => null],
            ['type' => 'search', 'value' => str_repeat('b', 64)],
        ]])->assertNoContent();

        $this->assertSame(2, MenuEvent::query()->count());
    }

    /** Shape only: a dish that is not this restaurant's passes here and is dropped later. */
    public function test_an_unknown_dish_id_passes_validation(): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, ['events' => [['type' => 'dish_add', 'dish_id' => 424242]]])->assertNoContent();
    }

    /** Guests need no session or account: the request authorizes everyone. */
    public function test_a_guest_needs_no_sign_in(): void
    {
        $restaurant = $this->published();

        $this->assertGuest();
        $this->send($restaurant, ['events' => [['type' => 'map']]])->assertNoContent();
        $this->assertDatabaseHas('menu_events', ['restaurant_id' => $restaurant->id, 'type' => 'map']);
    }

    /** The menu must be live; validation passing is not enough. */
    public function test_an_inactive_restaurant_is_a_404(): void
    {
        $restaurant = $this->published(['is_active' => false]);

        $this->send($restaurant, ['events' => [['type' => 'map']]])->assertNotFound();

        $this->assertSame(0, MenuEvent::query()->count());
    }
}
