<?php

namespace Tests\Feature\Portal;

use App\Http\Middleware\SetPortalLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_to_a_supported_locale_is_remembered(): void
    {
        $this->get(route('locale.switch', 'ar'))
            ->assertRedirect(route('login'))
            ->assertCookie(SetPortalLocale::COOKIE, 'ar');

        // Someone who chose Arabic and types qayema.com lands on /ar.
        $this->withCookie(SetPortalLocale::COOKIE, 'ar')->get('/')->assertRedirect(url('/ar'));
        $this->withCookie(SetPortalLocale::COOKIE, 'ar')->get('/ar')->assertOk()->assertSee('dir="rtl"', false);
    }

    public function test_the_switch_on_a_public_page_leads_to_its_twin(): void
    {
        $this->get(route('locale.switch', ['locale' => 'ar', 'to' => '/ar/contact']))
            ->assertRedirect('/ar/contact')
            ->assertCookie(SetPortalLocale::COOKIE, 'ar');

        $this->get(route('locale.switch', ['locale' => 'en', 'to' => '/']))
            ->assertRedirect('/')
            ->assertCookie(SetPortalLocale::COOKIE, 'en');
        $this->withCookie(SetPortalLocale::COOKIE, 'en')->get('/')->assertOk()->assertSee('lang="en"', false);
    }

    public function test_the_switch_never_leads_off_the_site(): void
    {
        foreach (['https://evil.example/phish', '//evil.example', '/\\evil.example', '/ar/../../x', 'javascript:alert(1)'] as $to) {
            $this->get(route('locale.switch', ['locale' => 'ar', 'to' => $to]))
                ->assertRedirect(route('login'));
        }
    }

    public function test_an_arabic_page_remembers_arabic_for_the_sign_in_pages(): void
    {
        $this->get('/ar/contact')->assertOk()->assertCookie(SetPortalLocale::COOKIE, 'ar');

        $this->withCookie(SetPortalLocale::COOKIE, 'ar')->get(route('login'))->assertOk()->assertSee('lang="ar"', false);
    }

    public function test_a_page_without_a_form_remembers_the_language_without_a_session(): void
    {
        // A first visit to /ar/pricing stores no session, yet sign-in follows.
        $this->get('/ar/pricing')
            ->assertOk()
            ->assertCookie(SetPortalLocale::COOKIE, 'ar')
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_a_remembered_language_the_site_does_not_have_changes_nothing(): void
    {
        // A cookie someone edited: the home page stays itself, sign-in stays English.
        $this->withCookie(SetPortalLocale::COOKIE, 'fr')->get('/')->assertOk()->assertSee('lang="en"', false);
        $this->withCookie(SetPortalLocale::COOKIE, 'fr')->get(route('login'))->assertOk()->assertSee('lang="en"', false);
    }

    public function test_a_language_already_remembered_is_not_set_again(): void
    {
        $this->withCookie(SetPortalLocale::COOKIE, 'ar')->get('/ar/pricing')->assertOk()->assertCookieMissing(SetPortalLocale::COOKIE);
    }

    public function test_a_visitor_with_no_choice_gets_the_page_asked_for(): void
    {
        // As a search engine visits: no cookie, no redirect.
        $this->get('/')->assertOk()->assertSee('lang="en"', false);
        $this->get('/ar')->assertOk()->assertSee('lang="ar"', false);
    }

    public function test_ar_can_never_be_a_restaurant_link(): void
    {
        $this->assertContains('ar', \App\Models\Restaurant::RESERVED_SLUGS);
    }

    public function test_an_unsupported_locale_is_ignored(): void
    {
        $this->withCookie(SetPortalLocale::COOKIE, 'en')
            ->get(route('locale.switch', 'fr'))
            ->assertRedirect()
            ->assertCookieMissing(SetPortalLocale::COOKIE);
    }

    public function test_the_switch_returns_to_an_on_site_referer(): void
    {
        $this->get(route('locale.switch', 'ar'), ['Referer' => url('/contact')])
            ->assertRedirect(url('/contact'));
    }

    public function test_an_off_site_referer_is_not_followed(): void
    {
        $this->get(route('locale.switch', 'ar'), ['Referer' => 'https://evil.example/phish'])
            ->assertRedirect(route('login'));
    }

    public function test_the_locale_survives_login(): void
    {
        $user = User::factory()->create();

        $this->withCookie(SetPortalLocale::COOKIE, 'ar')
            ->post(route('login'), ['login' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);

        // The cookie is the browser's, so signing in leaves it as it was.
        $this->withCookie(SetPortalLocale::COOKIE, 'ar')->get(route('locale.switch', 'en'))->assertCookie(SetPortalLocale::COOKIE, 'en');
    }

    public function test_the_default_locale_is_english(): void
    {
        $this->get('/')->assertOk()->assertSee('lang="en"', false);
    }
}
