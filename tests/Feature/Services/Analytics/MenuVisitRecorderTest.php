<?php

namespace Tests\Feature\Services\Analytics;

use App\Models\MenuSession;
use App\Services\Analytics\MenuVisitRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * MenuVisitRecorder called directly. Whether a render is recorded at all (a
 * preview, a self-referred language switch) is the controller's decision and
 * is tested with it in Tests\Feature\Menu; this is what one row holds.
 */
class MenuVisitRecorderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function request(string $query = '', string $agent = 'TestAgent/1.0', ?string $sessionId = null): Request
    {
        $request = Request::create('/menu'.($query !== '' ? '?'.$query : ''), 'GET', server: [
            'REMOTE_ADDR' => '198.51.100.9',
            'HTTP_USER_AGENT' => $agent,
        ]);

        if ($sessionId !== null) {
            $request->setLaravelSession(new Store('test', new ArraySessionHandler(10), $sessionId));
        }

        return $request;
    }

    public function test_a_visit_is_one_row_with_everything_it_knows(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 22:30:00', 'UTC'));
        $restaurant = $this->owner();
        $agent = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

        $row = app(MenuVisitRecorder::class)->record($restaurant, $this->request('qr=1', $agent, str_repeat('k', 40)), 'ar');

        $this->assertInstanceOf(MenuSession::class, $row);
        $this->assertSame(1, MenuSession::query()->count());
        $this->assertSame(
            [
                'restaurant_id' => $restaurant->id,
                'session_id' => str_repeat('k', 40),
                'device_type' => 'mobile',
                'browser' => 'Chrome',
                'os' => 'Android',
                'locale' => 'ar',
                'via_qr' => true,
                'viewed_at' => '2026-09-28 22:30:00',
            ],
            [
                ...MenuSession::query()->first()->only('restaurant_id', 'session_id', 'device_type', 'browser', 'os', 'locale', 'via_qr'),
                'viewed_at' => MenuSession::query()->first()->viewed_at->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function test_without_a_session_the_visitor_is_the_ip_and_agent(): void
    {
        $row = app(MenuVisitRecorder::class)->record($this->owner(), $this->request());

        $this->assertSame(md5('198.51.100.9'.'TestAgent/1.0'), $row->session_id);
    }

    public function test_the_same_guest_twice_is_two_views_of_one_visitor(): void
    {
        $restaurant = $this->owner();
        $recorder = app(MenuVisitRecorder::class);

        $recorder->record($restaurant, $this->request());
        $recorder->record($restaurant, $this->request());

        $this->assertSame(2, $restaurant->menuSessions()->count());
        $this->assertSame(1, $restaurant->menuSessions()->distinct('session_id')->count('session_id'));
    }

    public function test_no_locale_is_stored_as_null(): void
    {
        $row = app(MenuVisitRecorder::class)->record($this->owner(), $this->request());

        $this->assertNull($row->fresh()->locale);
    }

    public function test_only_the_exact_qr_marker_counts_as_a_scan(): void
    {
        $recorder = app(MenuVisitRecorder::class);
        $restaurant = $this->owner();

        $this->assertTrue($recorder->record($restaurant, $this->request('qr=1&lang=ar'))->via_qr);
        $this->assertFalse($recorder->record($restaurant, $this->request('qr=01'))->via_qr);
        $this->assertFalse($recorder->record($restaurant, $this->request('qr[]=1'))->via_qr);
        $this->assertFalse($recorder->record($restaurant, $this->request('QR=1'))->via_qr);
        $this->assertSame(1, $restaurant->menuSessions()->where('via_qr', true)->count());
    }

    /** @return array<string, array{0: string, 1: string, 2: ?string, 3: ?string}> */
    public static function moreAgents(): array
    {
        return [
            'Windows Phone' => ['Mozilla/5.0 (Windows Phone 10.0; Android 6.0.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Mobile Safari/537.36 Edge/15.14977', 'mobile', 'Edge', 'Android'],
            'Kindle Silk' => ['Mozilla/5.0 (Linux; U; Android 4.0.3; en-us; KFTT Build/IML74K) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36', 'tablet', 'Chrome', 'Android'],
            'Android tablet' => ['Mozilla/5.0 (Linux; Android 12; SM-X200 Tablet) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0 Safari/537.36', 'tablet', 'Chrome', 'Android'],
            'iPod' => ['Mozilla/5.0 (iPod touch; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1', 'mobile', 'Safari', 'iOS'],
            'Chrome on iOS reads as Safari' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0 Mobile/15E148 Safari/604.1', 'mobile', 'Safari', 'iOS'],
            'Firefox on Android' => ['Mozilla/5.0 (Android 13; Mobile; rv:121.0) Gecko/121.0 Firefox/121.0', 'mobile', 'Firefox', 'Android'],
            'Opera on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36 OPR/106.0', 'desktop', 'Opera', 'Windows'],
            'a bot' => ['Googlebot/2.1 (+http://www.google.com/bot.html)', 'desktop', null, null],
        ];
    }

    #[DataProvider('moreAgents')]
    public function test_user_agents_are_classified(string $agent, string $device, ?string $browser, ?string $os): void
    {
        $row = app(MenuVisitRecorder::class)->record($this->owner(), $this->request('', $agent));

        $this->assertSame(
            ['device_type' => $device, 'browser' => $browser, 'os' => $os],
            $row->only('device_type', 'browser', 'os'),
        );
    }
}
