<?php

namespace Tests\Feature\Menu;

use App\Models\Restaurant;
use App\Services\Analytics\MenuVisitRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MenuVisitRecorderTest extends TestCase
{
    use RefreshDatabase;

    private function record(string $userAgent, string $query = ''): ?array
    {
        $restaurant = Restaurant::factory()->create();
        $request = Request::create('/'.$restaurant->slug.($query ? '?'.$query : ''), 'GET', server: ['HTTP_USER_AGENT' => $userAgent]);

        $row = app(MenuVisitRecorder::class)->record($restaurant, $request);

        return $row?->only('device_type', 'browser', 'os', 'via_qr');
    }

    /** @return array<string, array{0: string, 1: string, 2: ?string, 3: ?string}> */
    public static function userAgents(): array
    {
        return [
            'iPhone Safari' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'mobile', 'Safari', 'iOS'],
            'iPad' => ['Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1', 'tablet', 'Safari', 'iOS'],
            'Android Chrome' => ['Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36', 'mobile', 'Chrome', 'Android'],
            'Windows Chrome' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', 'desktop', 'Chrome', 'Windows'],
            'Windows Edge' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0', 'desktop', 'Edge', 'Windows'],
            'macOS Opera' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0', 'desktop', 'Opera', 'macOS'],
            'Linux Firefox' => ['Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0', 'desktop', 'Firefox', 'Linux'],
            'macOS Safari' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 13_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15', 'desktop', 'Safari', 'macOS'],
            'curl' => ['curl/8.4.0', 'desktop', null, null],
            'empty' => ['', 'desktop', null, null],
        ];
    }

    #[DataProvider('userAgents')]
    public function test_user_agents_are_classified(string $agent, string $device, ?string $browser, ?string $os): void
    {
        $this->assertSame(
            ['device_type' => $device, 'browser' => $browser, 'os' => $os, 'via_qr' => false],
            $this->record($agent),
        );
    }

    public function test_only_qr_equals_one_marks_a_scan(): void
    {
        $this->assertTrue($this->record('x', 'qr=1')['via_qr']);
        $this->assertFalse($this->record('x', 'qr=true')['via_qr']);
        $this->assertFalse($this->record('x', 'qr=0')['via_qr']);
        $this->assertFalse($this->record('x')['via_qr']);
    }

    public function test_a_failure_to_write_never_throws(): void
    {
        $restaurant = Restaurant::factory()->create();
        DB::statement('DROP TABLE menu_sessions');

        $result = app(MenuVisitRecorder::class)->record($restaurant, Request::create('/x'));

        $this->assertNull($result);
    }
}
