<?php

namespace Tests\Feature\Portal;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The landing page says only what the product does today: the packages as an
 * admin set them, numbers that are true, and none of the claims it used to
 * make (AI menu scanning, an in-app checkout, invented reviews).
 */
class LandingContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_pricing_shows_each_package_as_it_is_set(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Free', '40 dishes', 'Pro', '$12', '/ month', 'Everything in Free, plus:', '150 dishes', 'Premium', '$29', 'Custom', "Let's talk"], false)
            ->assertSeeInOrder(['Pro', 'Premium', 'Most popular', '$29'], false)
            ->assertSee('Orders on WhatsApp');
    }

    public function test_an_admins_price_change_is_on_the_page_at_once(): void
    {
        Package::findBySlug('premium')->update(['price_cents' => 3500]);

        $this->get('/')->assertOk()->assertSee('$35')->assertDontSee('$29');
    }

    public function test_the_numbers_are_true_of_the_product(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Eleven menu languages (English and ten second ones), and the Free
        // package's own dish limit.
        $this->assertStringContainsString('data-count="'.count(config('locales.menu')).'"', $html);
        $this->assertStringContainsString('data-count="40"', $html);
        $this->assertStringContainsString(__('portal.stats.free_dishes'), $html);
    }

    public function test_upgrading_is_explained_as_it_really_works(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('No card,')
            ->assertSee('Ask from your dashboard')
            ->assertSee('We switch it on');
    }

    /** @return array<string, array{0: string}> */
    public static function claimsThatAreNotTrue(): array
    {
        return [
            'AI scanning' => ['AI'],
            'photographing the menu' => ['Photograph your menu'],
            'an in-app checkout' => ['Apple Pay'],
            'payment logos' => ['payment-logos'],
            'an invented rating' => ['Riyadh to Lisbon'],
            'an invented review' => ['Layla Othman'],
            'the missing product shot' => ['qayema-dashboard.html'],
        ];
    }

    #[DataProvider('claimsThatAreNotTrue')]
    public function test_it_makes_no_claim_the_product_cannot_back(string $claim): void
    {
        // The page's random security token could spell "AI" by chance, so it
        // is taken out, and a claim only counts as a whole word.
        $html = preg_replace('/(name="csrf-token" content=|name="_token" value=)"[^"]*"/', '', $this->get('/')->assertOk()->getContent());

        $this->assertDoesNotMatchRegularExpression('/\b'.preg_quote($claim, '/').'\b/', $html);
    }

    public function test_the_arabic_page_uses_the_arabic_packages(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('مميّز')
            ->assertSee('150 طبقاً')
            ->assertSee('كل ما في مجاني، إضافةً إلى:')
            ->assertDontSee('الذكاء الاصطناعي');
    }

    public function test_the_product_shot_is_a_real_image_in_the_readers_language_and_both_themes(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(asset('images/landing/dashboard-en-dark.webp'), false)
            ->assertSee(asset('images/landing/dashboard-en-light.webp'), false);

        foreach (['en', 'ar'] as $language) {
            foreach (['dark', 'light'] as $theme) {
                $this->assertFileExists(public_path("images/landing/dashboard-{$language}-{$theme}.webp"));
            }
        }
    }

    public function test_the_site_opens_dark_unless_the_visitor_chose_light(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-theme="dark"', false)
            ->assertSee("localStorage.getItem('qayema-theme') || 'dark'", false);
    }

    public function test_each_feature_card_names_the_package_that_unlocks_it(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Eight cards; the ones a package unlocks say which.
        $this->assertSame(8, substr_count($html, 'class="feat-cell"'));
        $this->assertMatchesRegularExpression('#c-plan">Pro</span>.*?Two languages, one menu#s', $html);
        $this->assertMatchesRegularExpression('#c-plan">Premium</span>.*?Advanced analytics#s', $html);
    }
}
