<?php

namespace App\Services\Analytics;

use App\Models\MenuSession;
use App\Models\Restaurant;
use Illuminate\Http\Request;

/**
 * Records one `menu_sessions` row per public menu view: the raw data behind
 * the owner's QR scan counts and the admin visitor widgets. Rows are pruned
 * after the retention window by `stats:rollup`.
 *
 * Deliberately best-effort: analytics must never be the reason a guest can't
 * see a menu, so a failure here is swallowed.
 */
class MenuVisitRecorder
{
    public function record(Restaurant $restaurant, Request $request, ?string $locale = null): ?MenuSession
    {
        try {
            $agent = (string) $request->userAgent();

            return $restaurant->menuSessions()->create([
                'session_id' => $request->hasSession() ? $request->session()->getId() : md5($request->ip().$agent),
                'device_type' => $this->deviceType($agent),
                'browser' => $this->browser($agent),
                'os' => $this->os($agent),
                'locale' => $locale,
                // The QR codes encode ?qr=1, which is the only way we can tell a
                // scan from someone following a shared link.
                'via_qr' => $request->query('qr') === '1',
                'viewed_at' => now(),
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function deviceType(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk/i', $agent) => 'tablet',
            (bool) preg_match('/Mobile|Android|iPhone|iPod|Windows Phone/i', $agent) => 'mobile',
            default => 'desktop',
        };
    }

    private function browser(string $agent): ?string
    {
        // Order matters: Edge and Opera both carry "Chrome", and Chrome carries
        // "Safari", so the more specific names have to be tested first.
        foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'] as $needle => $name) {
            if (str_contains($agent, $needle)) {
                return $name;
            }
        }

        return null;
    }

    private function os(string $agent): ?string
    {
        return match (true) {
            str_contains($agent, 'Android') => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $agent) => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };
    }
}
