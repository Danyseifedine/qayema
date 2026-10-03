<?php

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Errors show a page of the site (navbar, hero, footer) in the address's
 * language, never Laravel's.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // As live: no stack trace page.
        config(['app.debug' => false]);

        Route::get('/__error/{code}', fn (string $code) => abort((int) $code))->middleware('web');
        Route::get('/ar/__error/{code}', fn (string $code) => abort((int) $code))->middleware('web');
        Route::get('/__broken', fn () => throw new RuntimeException('secret detail'))->middleware('web');
    }

    /** @return array<string, array{0: int, 1: string, 2: string}> */
    public static function pages(): array
    {
        return [
            '401' => [401, 'continue', 'Back to home'],
            '403' => [403, 'reserved', 'Back to home'],
            '404' => [404, 'off the menu', 'Back to home'],
            '419' => [419, 'expired', 'Try again'],
            '429' => [429, 'please', 'Try again'],
            '500' => [500, 'slipped up', 'Try again'],
            '503' => [503, 'soon', 'Try again'],
            'any other 4xx' => [418, "didn't work", 'Back to home'],
            'any other 5xx' => [502, 'slipped up', 'Try again'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_error_has_its_own_page(int $code, string $gold, string $action): void
    {
        $this->get("/__error/{$code}")
            ->assertStatus($code)
            ->assertSee('<span class="gold-text">'.e($gold).'</span>', false)
            ->assertSee('Error '.$code)
            ->assertSee($action)
            // A page of the site, kept out of search results.
            ->assertSee('<nav class="nav">', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('href="'.url('/').'"', false);
    }

    public function test_a_server_error_never_asks_who_is_signed_in(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        // Signed in, yet the navbar shows the guest's button: the database
        // may be what failed.
        $this->get('/__error/500')->assertStatus(500)->assertSee('nav-cta', false);
        $this->get('/__error/404')->assertNotFound()->assertDontSee('nav-cta', false);
    }

    public function test_an_unknown_address_and_an_unknown_menu_get_it(): void
    {
        $this->get('/no/such/page')->assertNotFound()->assertSee('off the menu')->assertDontSee('Not Found');
        $this->get('/no-such-menu')->assertNotFound()->assertSee('off the menu');
    }

    public function test_an_arabic_address_gets_it_in_arabic(): void
    {
        $this->get('/ar/__error/404')
            ->assertNotFound()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('ليست في القائمة')
            ->assertSee('العودة إلى الرئيسية')
            ->assertSee('href="'.url('/ar').'"', false);

        $this->get('/no-such-menu?lang=ar')->assertNotFound()->assertSee('ليست في القائمة');
    }

    public function test_a_crash_says_nothing_about_what_broke(): void
    {
        $this->get('/__broken')
            ->assertStatus(500)
            ->assertSee('slipped up')
            ->assertDontSee('secret detail')
            ->assertDontSee('RuntimeException');
    }

    public function test_the_api_still_answers_in_json(): void
    {
        $this->getJson('/api/no-such-endpoint')->assertNotFound()->assertJsonStructure(['message', 'code']);
    }
}
