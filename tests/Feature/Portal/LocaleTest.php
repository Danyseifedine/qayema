<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_to_a_supported_locale_persists_in_the_session(): void
    {
        $this->get(route('locale.switch', 'ar'))->assertRedirect(route('login'));

        $this->assertSame('ar', session('owner_locale'));
        // Someone who chose Arabic and types qayema.com lands on /ar.
        $this->get('/')->assertRedirect(url('/ar'));
        $this->get('/ar')->assertOk()->assertSee('dir="rtl"', false);
    }

    public function test_the_switch_on_a_public_page_leads_to_its_twin(): void
    {
        $this->get(route('locale.switch', ['locale' => 'ar', 'to' => '/ar/contact']))
            ->assertRedirect('/ar/contact');
        $this->assertSame('ar', session('owner_locale'));

        $this->get(route('locale.switch', ['locale' => 'en', 'to' => '/']))->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('lang="en"', false);
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
        $this->get('/ar/contact')->assertOk();

        $this->get(route('login'))->assertOk()->assertSee('lang="ar"', false);
    }

    public function test_a_visitor_with_no_choice_gets_the_page_asked_for(): void
    {
        // As a search engine visits: no session, no redirect.
        $this->get('/')->assertOk()->assertSee('lang="en"', false);
        $this->get('/ar')->assertOk()->assertSee('lang="ar"', false);
    }

    public function test_ar_can_never_be_a_restaurant_link(): void
    {
        $this->assertContains('ar', \App\Models\Restaurant::RESERVED_SLUGS);
    }

    public function test_an_unsupported_locale_is_ignored(): void
    {
        $this->withSession(['owner_locale' => 'en'])->get(route('locale.switch', 'fr'))->assertRedirect();

        $this->assertSame('en', session('owner_locale'));
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

        $this->get(route('locale.switch', 'ar'));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

        $this->assertSame('ar', session('owner_locale'));
    }

    public function test_the_default_locale_is_english(): void
    {
        $this->get('/')->assertOk()->assertSee('lang="en"', false);
    }
}
