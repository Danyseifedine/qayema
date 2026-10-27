<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingValidationTest extends TestCase
{
    use RefreshDatabase;

    private function ownerOnContactStep(): User
    {
        $user = User::factory()->create(['onboarding_step' => 1]);
        Restaurant::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);

        return $user;
    }

    public function test_phone_with_letters_is_rejected(): void
    {
        $this->actingAs($this->ownerOnContactStep())
            ->postJson(route('onboarding.advance'), [
                '_step' => 2,
                'country_code' => 'LB',
                'phone' => 'callme123',
                'currency' => 'USD',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    /** The page's own language, as every other message on it: lang/ar.json had the words all along. */
    public function test_an_arabic_page_gets_its_errors_in_arabic(): void
    {
        $this->withCookie('owner_locale', 'ar')
            ->withCredentials()
            ->actingAs($this->ownerOnContactStep())
            ->postJson(route('onboarding.advance'), [
                '_step' => 2,
                'country_code' => 'ABCDE',
                'phone' => 'callme123',
                'currency' => 'XXX',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'يرجى إدخال رقم هاتف صالح بالأرقام فقط.')
            ->assertJsonPath('errors.country_code.0', 'يرجى اختيار دولة من القائمة.')
            ->assertJsonPath('errors.currency.0', 'يرجى اختيار عملة من القائمة.');
    }

    public function test_an_english_page_keeps_its_english_errors(): void
    {
        $this->actingAs($this->ownerOnContactStep())
            ->postJson(route('onboarding.advance'), [
                '_step' => 2,
                'phone' => 'callme123',
                'currency' => 'USD',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'Please enter a valid phone number using digits only.');
    }

    public function test_too_short_phone_is_rejected(): void
    {
        $this->actingAs($this->ownerOnContactStep())
            ->postJson(route('onboarding.advance'), [
                '_step' => 2,
                'country_code' => 'LB',
                'phone' => '123',
                'currency' => 'USD',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_over_long_country_code_is_rejected(): void
    {
        // The column is char(2); a longer value must 422, not 500 on save.
        $this->actingAs($this->ownerOnContactStep())
            ->postJson(route('onboarding.advance'), [
                '_step' => 2,
                'country_code' => 'ABCDE',
                'phone' => '70123456',
                'currency' => 'USD',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('country_code');
    }

    public function test_currency_outside_the_list_is_rejected(): void
    {
        $this->actingAs($this->ownerOnContactStep())
            ->postJson(route('onboarding.advance'), [
                '_step' => 2,
                'country_code' => 'LB',
                'phone' => '70123456',
                'currency' => 'Placeat omnis quo',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('currency');
    }

    public function test_valid_contact_step_passes_and_persists(): void
    {
        $user = $this->ownerOnContactStep();

        $this->actingAs($user)
            ->postJson(route('onboarding.advance'), [
                '_step' => 2,
                'country_code' => 'LB',
                'phone' => '70 123 456',
                'currency' => 'EUR',
            ])
            ->assertOk()
            ->assertJson(['step' => 3]);

        $restaurant = $user->restaurant->fresh();
        $this->assertSame('EUR', $restaurant->currency);
        $this->assertSame('70 123 456', $restaurant->phone);
    }
}
