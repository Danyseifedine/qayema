<?php

namespace Tests\Feature\Requests;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** App\Http\Requests\UpdateFeaturesRequest, through PUT /api/features. */
class UpdateFeaturesRequestTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, array{0: array<string, mixed>, 1: string, 2: string}> */
    public static function badBodies(): array
    {
        return [
            'no off' => [[], 'off', 'The off field must be present.'],
            'off a string' => [['off' => 'orders'], 'off', 'The off field must be an array.'],
            'an unknown feature' => [['off' => ['payments']], 'off.0', 'The selected off.0 is invalid.'],
            'a feature twice' => [['off' => ['qr', 'qr']], 'off.0', 'The off.0 field has a duplicate value.'],
            'a number' => [['off' => [1]], 'off.0', 'The off.0 field must be a string.'],
            'a nested list' => [['off' => [['qr']]], 'off.0', 'The off.0 field must be a string.'],
        ];
    }

    /** @param  array<string, mixed>  $body */
    #[DataProvider('badBodies')]
    public function test_a_bad_list_is_refused_and_nothing_changes(array $body, string $field, string $message): void
    {
        $restaurant = $this->owner(['switched_off' => ['analytics']]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.features.update'), $body)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field => $message]);

        $this->assertSame(['analytics'], $restaurant->refresh()->switchedOff());
    }

    public function test_every_optional_feature_can_be_switched_off_at_once(): void
    {
        $restaurant = $this->owner();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.features.update'), ['off' => array_reverse(Restaurant::OPTIONAL_FEATURES)])
            ->assertOk()
            ->assertExactJson(['data' => ['off' => Restaurant::OPTIONAL_FEATURES]]);
    }

    public function test_an_empty_list_switches_everything_back_on(): void
    {
        $restaurant = $this->owner(['switched_off' => ['qr', 'orders']]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.features.update'), ['off' => []])
            ->assertOk()
            ->assertExactJson(['data' => ['off' => []]]);

        $this->assertSame([], $restaurant->refresh()->switchedOff());
    }

    public function test_a_keyed_list_is_stored_as_a_plain_list(): void
    {
        $restaurant = $this->owner();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.features.update'), ['off' => ['a' => 'qr', 'b' => 'languages']])
            ->assertOk()
            ->assertExactJson(['data' => ['off' => ['qr', 'languages']]]);

        $this->assertSame(['qr', 'languages'], $restaurant->refresh()->switched_off);
    }

    public function test_a_user_without_a_restaurant_is_refused_before_validation(): void
    {
        $this->actingAs($this->userWithoutRestaurant())
            ->putJson(route('api.features.update'), ['off' => 'nonsense'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'This action is unauthorized.', 'code' => 'forbidden']);
    }

    public function test_a_guest_gets_a_401(): void
    {
        $this->putJson(route('api.features.update'), ['off' => []])->assertUnauthorized();
    }
}
