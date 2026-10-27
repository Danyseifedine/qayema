<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\User;
use App\Services\Portal\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 2 also sets a second currency, suggested on the page (dollars
 * beside the local money, or the phone's country's money beside dollars),
 * with its rate written the way people say it: 1 USD = 89,000 LBP.
 */
class OnboardingCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['onboarding_step' => 1, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $this->user->id]);
    }

    /** @param  array<string, mixed>  $currencies */
    private function stepTwo(array $currencies): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)->postJson(route('onboarding.advance'), [
            '_step' => 2, 'country_code' => 'LB', 'phone' => '70123456', ...$currencies,
        ]);
    }

    public function test_a_pound_menu_keeps_dollars_beside_it_at_the_rate(): void
    {
        $this->stepTwo(['currency' => 'LBP', 'second_currency' => 'USD', 'rate' => '89000'])->assertOk();

        $restaurant = $this->user->fresh()->restaurant;
        $this->assertSame('USD', $restaurant->second_currency);
        $this->assertSame('89000', \App\Support\Price::rate((string) $restaurant->exchange_rate));
        $this->assertSame('89000', OnboardingService::writtenRate($restaurant));
    }

    public function test_a_dollar_menu_keeps_the_rate_the_dollar_way_round(): void
    {
        // Written 1 USD = 89,000 LBP; stored as dollars per pound.
        $this->stepTwo(['currency' => 'USD', 'second_currency' => 'LBP', 'rate' => '89000'])->assertOk();

        $restaurant = $this->user->fresh()->restaurant;
        $this->assertSame('LBP', $restaurant->second_currency);
        $this->assertSame('0.000011235955', \App\Support\Price::rate((string) $restaurant->exchange_rate));
        // Read back as it was written.
        $this->assertSame('89000', OnboardingService::writtenRate($restaurant));
    }

    public function test_no_second_currency_keeps_none(): void
    {
        $this->stepTwo(['currency' => 'EUR', 'second_currency' => null, 'rate' => null])->assertOk();

        $restaurant = $this->user->fresh()->restaurant;
        $this->assertNull($restaurant->second_currency);
        $this->assertNull($restaurant->exchange_rate);
        $this->assertNull(OnboardingService::writtenRate($restaurant));
    }

    public function test_a_second_currency_needs_a_rate_and_is_not_the_main_one(): void
    {
        $this->stepTwo(['currency' => 'USD', 'second_currency' => 'USD', 'rate' => '1'])
            ->assertUnprocessable()->assertJsonValidationErrors(['second_currency' => 'Pick a second currency other than the main one.']);
        $this->stepTwo(['currency' => 'USD', 'second_currency' => 'EUR'])
            ->assertUnprocessable()->assertJsonValidationErrors(['rate' => 'Enter the exchange rate.']);
        $this->stepTwo(['currency' => 'USD', 'second_currency' => 'EUR', 'rate' => '0'])
            ->assertUnprocessable()->assertJsonValidationErrors(['rate' => 'Enter an exchange rate above 0.']);
    }

    public function test_the_page_knows_each_countrys_money_and_shows_the_saved_rate(): void
    {
        $this->user->restaurant->update(['currency' => 'USD', 'second_currency' => 'LBP', 'exchange_rate' => '0.000011235955']);
        $this->user->update(['onboarding_step' => 2]);

        $html = (string) $this->actingAs($this->user)->get(route('onboarding'))->assertOk()->getContent();

        $this->assertStringContainsString('"LB":"LBP"', $html);
        $this->assertStringContainsString('second_currency:    "LBP"', $html);
        $this->assertStringContainsString('rate:               "89000"', $html);
    }

    /**
     * The rate the owner typed comes back as typed, whichever currency is
     * the main one: stored as main per one second, read back the dollar way.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function pairs(): array
    {
        return [
            'pounds, dollars beside' => ['LBP', 'USD', '89000', '89000'],
            'dollars, pounds beside' => ['USD', 'LBP', '89000', '0.000011235955'],
            'dollars, dirhams beside' => ['USD', 'AED', '3.6725', '0.272294077604'],
            'dollars, dinars beside' => ['USD', 'JOD', '0.709', '1.410437235543'],
            'euros, dollars beside' => ['EUR', 'USD', '0.92', '0.92'],
            'euros, pounds sterling beside' => ['EUR', 'GBP', '1.17', '1.17'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pairs')]
    public function test_a_rate_comes_back_as_the_owner_wrote_it(string $main, string $second, string $written, string $stored): void
    {
        $this->stepTwo(['currency' => $main, 'second_currency' => $second, 'rate' => $written])->assertOk();

        $restaurant = $this->user->fresh()->restaurant;
        $this->assertSame($stored, \App\Support\Price::rate((string) $restaurant->exchange_rate));
        $this->assertSame($written, OnboardingService::writtenRate($restaurant));
        // And the menu counts with it: one of the second in the main currency.
        $this->assertSame(
            \App\Support\Price::toMain('1', $stored),
            \App\Support\Price::toMain('1', (string) $restaurant->exchange_rate),
        );
    }

    public function test_the_rate_is_what_the_dashboard_and_the_menu_then_use(): void
    {
        $this->stepTwo(['currency' => 'USD', 'second_currency' => 'LBP', 'rate' => '89000'])->assertOk();

        // The dashboard reads the same pair and rate.
        $this->actingAs($this->user)->getJson(route('api.restaurant.show'))
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.second_currency', 'LBP')
            ->assertJsonPath('data.exchange_rate', '0.000011235955');

        // A dish of L.L 445,000 counts as $5 in a cart.
        $this->assertSame('5.00', \App\Support\Price::toMain('445000.00', (string) $this->user->fresh()->restaurant->exchange_rate));
    }

    public function test_doing_step_two_again_changes_or_clears_the_second_currency(): void
    {
        $this->stepTwo(['currency' => 'LBP', 'second_currency' => 'USD', 'rate' => '89000'])->assertOk();
        $this->stepTwo(['currency' => 'LBP', 'second_currency' => 'USD', 'rate' => '90000'])->assertOk();
        $this->assertSame('90000', OnboardingService::writtenRate($this->user->fresh()->restaurant));

        $this->stepTwo(['currency' => 'LBP', 'second_currency' => null])->assertOk();
        $restaurant = $this->user->fresh()->restaurant;
        $this->assertNull($restaurant->second_currency);
        $this->assertNull($restaurant->exchange_rate);
    }

    public function test_a_rate_without_a_second_currency_is_ignored(): void
    {
        $this->stepTwo(['currency' => 'USD', 'second_currency' => null, 'rate' => '5'])->assertOk();

        $this->assertNull($this->user->fresh()->restaurant->exchange_rate);
    }

    public function test_a_second_currency_not_on_the_list_is_refused(): void
    {
        $this->stepTwo(['currency' => 'USD', 'second_currency' => 'XYZ', 'rate' => '1'])
            ->assertUnprocessable()->assertJsonValidationErrors(['second_currency' => 'Please choose a currency from the list.']);
    }

    public function test_every_country_on_the_phone_list_names_a_currency_on_the_currency_list(): void
    {
        $currencies = array_keys(config('currencies'));

        foreach (config('countries') as $code => $country) {
            $this->assertArrayHasKey('currency', $country, "{$code} names no currency.");
            $this->assertContains($country['currency'], $currencies, "{$code}'s {$country['currency']} is not on the currency list.");
        }
    }
}
