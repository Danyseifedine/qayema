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
        $this->get('/')->assertSee('dir="rtl"', false);
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
