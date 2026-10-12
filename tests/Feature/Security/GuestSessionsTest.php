<?php

namespace Tests\Feature\Security;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Http\Middleware\StartSessionForReturningVisitors: the website's pages
 * without a form store no session for a stranger (a guest, a crawler), and
 * carry on with one for whoever already has it. Everywhere else a session
 * starts as it always did.
 */
class GuestSessionsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function sessionCookie(): string
    {
        return (string) config('session.cookie');
    }

    /** The session id a response handed out in its cookie. */
    private function sessionIdFrom(TestResponse $response): string
    {
        return (string) $response->getCookie($this->sessionCookie())?->getValue();
    }

    private function stored(string $id): bool
    {
        return app('session')->driver()->getHandler()->read($id) !== '';
    }

    /** @return array<string, array{0: string}> */
    public static function pagesWithoutAForm(): array
    {
        return [
            'home' => ['/'],
            'home in Arabic' => ['/ar'],
            'pricing' => ['/pricing'],
            'a guide' => ['/guides'],
            'the privacy policy' => ['/ar/privacy-policy'],
            'the cookie policy' => ['/cookie-policy'],
        ];
    }

    #[DataProvider('pagesWithoutAForm')]
    public function test_a_stranger_gets_no_session_on_a_page_without_a_form(string $path): void
    {
        $this->get($path)->assertOk()->assertCookieMissing($this->sessionCookie());

        $this->assertFalse($this->stored(app('session')->driver()->getId()));
    }

    public function test_a_made_up_session_cookie_does_not_start_one(): void
    {
        $this->withCookie($this->sessionCookie(), str_repeat('a', 40))
            ->get('/pricing')
            ->assertOk()
            ->assertCookieMissing($this->sessionCookie());

        $this->assertFalse($this->stored(str_repeat('a', 40)));
    }

    /** @return array<string, array{0: string}> */
    public static function pagesThatNeedOne(): array
    {
        return [
            'the contact form' => ['/contact'],
            'the contact form in Arabic' => ['/ar/contact'],
            'sign in' => ['/get-started'],
        ];
    }

    #[DataProvider('pagesThatNeedOne')]
    public function test_a_page_with_a_form_starts_a_session_from_the_first_visit(string $path): void
    {
        $id = $this->sessionIdFrom($this->get($path)->assertOk()->assertCookie($this->sessionCookie()));

        $this->assertTrue($this->stored($id));
    }

    public function test_a_visitor_who_has_a_session_keeps_it_on_a_page_without_a_form(): void
    {
        $id = $this->sessionIdFrom($this->get('/contact'));

        $this->withCookie($this->sessionCookie(), $id)
            ->get('/pricing')
            ->assertOk()
            ->assertCookie($this->sessionCookie());

        $this->assertTrue($this->stored($id));
    }

    public function test_a_signed_in_owner_is_still_signed_in_on_the_home_page(): void
    {
        $owner = User::factory()->create();
        $id = $this->sessionIdFrom($this->post(route('login'), ['login' => $owner->email, 'password' => 'password']));
        $this->app['auth']->forgetGuards();

        $this->withCookie($this->sessionCookie(), $id)
            ->get('/')
            ->assertOk()
            ->assertSee(route('onboarding'), false)
            ->assertDontSee('nav-cta', false);
    }

    public function test_an_owner_previewing_their_menu_is_known_by_their_session(): void
    {
        $restaurant = $this->owner(['is_active' => true]);
        $template = Template::factory()->create(['slug' => 'classic']);
        $restaurant->update(['template_id' => $template->id]);
        $preview = route('public.menu', $restaurant->slug).'?preview='.$template->id;

        // A guest at that address gets the menu itself, which is tracked;
        // the owner gets the preview, which never is.
        $this->get($preview)->assertOk()->assertSee('js/menu-track.js', false);
        $id = $this->sessionIdFrom($this->post(route('login'), ['login' => $restaurant->user->email, 'password' => 'password']));
        $this->app['auth']->forgetGuards();
        $this->withCookie($this->sessionCookie(), $id)->get($preview)->assertOk()->assertDontSee('js/menu-track.js', false);
    }

    public function test_a_strangers_page_still_has_everything_a_session_gives_it(): void
    {
        // The session only lives for the request, but the page still reads
        // it: the shared $errors bag, the token in the layout.
        $this->get('/')->assertOk()->assertSee('name="csrf-token"', false);
    }
}
