<?php

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Each legal page: its path, route name, the English headline and first
     * section, and the Arabic headline and first section.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    public static function pages(): array
    {
        return [
            'terms' => ['/terms-of-service', 'terms', 'Terms of Service', 'acceptance', 'شروط الخدمة', 'acceptance-ar'],
            'cookies' => ['/cookie-policy', 'cookies', 'Cookie Policy', 'what', 'سياسة ملفات الارتباط', 'what-ar'],
            'refund' => ['/refund-policy', 'refund', 'Refund Policy', 'overview', 'سياسة الاسترداد', 'overview-ar'],
        ];
    }

    #[DataProvider('pages')]
    public function test_the_page_is_public_and_named(string $path, string $route): void
    {
        $this->assertSame(url($path), route($route));

        $this->get($path)->assertOk()->assertHeader('Content-Type', 'text/html; charset=utf-8');
    }

    #[DataProvider('pages')]
    public function test_the_english_page_shows_the_english_text_only(string $path, string $route, string $title, string $section, string $titleAr, string $sectionAr): void
    {
        $this->withSession(['owner_locale' => 'en'])
            ->get($path)
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertSee("<meta name=\"title\" content=\"{$title} — Qayema\">", false)
            ->assertSee("<h2 id=\"{$section}\">", false)
            ->assertDontSee("<h2 id=\"{$sectionAr}\">", false)
            ->assertDontSee($titleAr);
    }

    #[DataProvider('pages')]
    public function test_the_arabic_page_shows_the_arabic_text_only(string $path, string $route, string $title, string $section, string $titleAr, string $sectionAr): void
    {
        $this->withSession(['owner_locale' => 'ar'])
            ->get($path)
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee($titleAr)
            ->assertSee("<h2 id=\"{$sectionAr}\">", false)
            ->assertDontSee("<h2 id=\"{$section}\">", false);
    }

    #[DataProvider('pages')]
    public function test_an_unsupported_session_locale_falls_back_to_english(string $path, string $route, string $title, string $section): void
    {
        $this->withSession(['owner_locale' => 'xx'])
            ->get($path)
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertSee("<h2 id=\"{$section}\">", false);
    }
}
