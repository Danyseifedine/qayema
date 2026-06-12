<?php

namespace Tests\Feature\MenuOwner;

use App\Http\Requests\RestaurantRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RestaurantRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function validate(array $overrides): \Illuminate\Contracts\Validation\Validator
    {
        $user = User::factory()->create();
        Restaurant::factory()->create(['user_id' => $user->id]);

        $data = array_merge([
            'name' => 'My Restaurant',
            'phone' => '70123456',
            'currency' => 'USD',
            'preferred_language' => 'en',
            'logo_key' => str()->uuid()->toString(),
        ], $overrides);

        $request = RestaurantRequest::create('/restaurant', 'POST', $data);
        $request->setUserResolver(fn () => $user->fresh());

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_phone_with_letters_fails(): void
    {
        $validator = $this->validate(['phone' => 'call-me-now']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_unknown_currency_fails(): void
    {
        $validator = $this->validate(['currency' => 'FAKE']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    public function test_valid_payload_passes(): void
    {
        $validator = $this->validate(['phone' => '+961 70 123 456', 'currency' => 'EUR']);

        $this->assertFalse($validator->fails(), (string) $validator->errors());
    }
}
