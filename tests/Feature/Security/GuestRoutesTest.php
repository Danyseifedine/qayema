<?php

namespace Tests\Feature\Security;

use App\Enums\Feature;
use App\Http\Middleware\CacheMenuForGuests;
use App\Http\Middleware\StartSessionForReturningVisitors;
use App\Http\Middleware\VerifyMenuToken;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Every route a guest's phone calls on a menu: none stores a session or
 * sets a cookie for a guest, the ones that write take the menu token in
 * place of a CSRF token, and all of it holds once the routes are cached
 * (php artisan optimize), which production runs.
 */
class GuestRoutesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // "Use my location" asks OpenStreetMap; nothing leaves the test.
        Http::preventStrayRequests();
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response(['address' => ['road' => 'Bliss Street', 'city' => 'Beirut']])]);
    }

    private function shop(): Restaurant
    {
        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering);
        $template = Template::factory()->create(['slug' => 'classic']);

        return $this->owner(['slug' => 'olive', 'is_active' => true, 'order_mode' => 'menu', 'template_id' => $template->id]);
    }

    private function assertNothingStored(TestResponse $response): void
    {
        $this->assertSame([], $response->headers->getCookies(), 'No cookie for a guest.');
        $this->assertSame('', app('session')->driver()->getHandler()->read(app('session')->driver()->getId()));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function guestCalls(): array
    {
        return [
            'the menu' => ['GET', '/olive'],
            'its QR design' => ['GET', '/olive/qr-options'],
            'the QR card' => ['GET', '/olive/qr'],
            'an address from a location' => ['GET', '/olive/address?lat=33.89&lng=35.50'],
            'an order followed' => ['GET', '/olive/order/'.str_repeat('a', 40)],
            'an order to change' => ['GET', '/olive/order/'.str_repeat('a', 40).'/cart'],
            'an order placed' => ['POST', '/olive/order'],
            'an order changed' => ['PUT', '/olive/order/'.str_repeat('a', 40)],
            'what guests do' => ['POST', '/olive/events'],
        ];
    }

    #[DataProvider('guestCalls')]
    public function test_no_guest_call_stores_a_session_or_sets_a_cookie(string $method, string $path): void
    {
        $this->shop();

        $this->assertNothingStored($this->json($method, $path));
    }

    /** @return array<string, array{0: string}> */
    public static function readingCalls(): array
    {
        return [
            'its QR design' => ['/olive/qr-options'],
            'an address from a location' => ['/olive/address?lat=33.89&lng=35.50'],
        ];
    }

    #[DataProvider('readingCalls')]
    public function test_a_call_that_only_reads_needs_no_menu_token(string $path): void
    {
        $this->shop();

        $this->getJson($path)->assertOk();
    }

    /** The routes as `php artisan route:cache` writes them and reads them back. */
    private function cached(string $name): Route
    {
        $router = app('router');
        $routes = $router->getRoutes();

        foreach ($routes as $route) {
            $route->prepareForSerialization();
        }

        $compiled = $routes->compile();

        return (new CompiledRouteCollection($compiled['compiled'], $compiled['attributes']))
            ->setRouter($router)
            ->setContainer($this->app)
            ->getByName($name);
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function writingRoutes(): array
    {
        return [
            'an order placed' => ['public.order', true],
            'an order changed' => ['public.order.update', true],
            'what guests do' => ['public.events', true],
            'the menu' => ['public.menu', false],
        ];
    }

    #[DataProvider('writingRoutes')]
    public function test_cached_routes_keep_the_guest_rules(string $name, bool $writes): void
    {
        $route = $this->cached($name);
        $middleware = app('router')->gatherRouteMiddleware($route);

        $this->assertTrue($route->getAction(StartSessionForReturningVisitors::ONLY_RETURNING));
        $this->assertContains(ValidateCsrfToken::class, $route->excludedMiddleware());
        $this->assertNotContains(ValidateCsrfToken::class, $middleware);
        $this->assertSame($writes, in_array(VerifyMenuToken::class, $middleware, true));
    }

    public function test_cached_routes_keep_the_menu_cacheable_and_the_pages_without_a_form_session_free(): void
    {
        $this->assertContains(CacheMenuForGuests::class, app('router')->gatherRouteMiddleware($this->cached('public.menu')));
        $this->assertTrue($this->cached('home')->getAction(StartSessionForReturningVisitors::ONLY_RETURNING));
        $this->assertTrue($this->cached('ar.pricing')->getAction(StartSessionForReturningVisitors::ONLY_RETURNING));
        // The contact form and sign-in keep their session from the first visit.
        $this->assertNull($this->cached('contact')->getAction(StartSessionForReturningVisitors::ONLY_RETURNING));
        $this->assertNull($this->cached('login')->getAction(StartSessionForReturningVisitors::ONLY_RETURNING));
        $this->assertContains(ValidateCsrfToken::class, app('router')->gatherRouteMiddleware($this->cached('contact.store')));
    }
}
