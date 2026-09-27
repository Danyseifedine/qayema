<?php

namespace App\Services\Menu;

use App\Models\Restaurant;

/**
 * The point behind a restaurant's map link, and a map that can be shown for it.
 *
 * Owners paste a Google Maps URL or let the dashboard fill one in from the
 * browser's position, so the coordinates already live inside that URL — which
 * is why this reads them rather than the restaurant carrying its own lat/lng
 * columns that would need backfilling from the same URLs anyway.
 *
 * Mirrors `parseMapCoordinates` / `mapEmbedUrlFor` in the dashboard
 * (qayema-dashboard/src/features/settings/hooks/use-current-location.ts).
 */
class MapPoint
{
    /** Google writes a point as `lat,lng`, sometimes separated by a space. */
    private const LAT_LNG = '/(-?\d{1,3}(?:\.\d+)?)[,\s]+(-?\d{1,3}(?:\.\d+)?)/';

    /** How much ground the embedded map shows, in degrees of longitude. */
    private const SPAN = 0.004;

    /**
     * A keyless OpenStreetMap embed for this restaurant, or null when its link
     * carries no readable point. Google's embed needs an API key and a billing
     * account; this one needs neither, which suits a page guests scan all day.
     */
    public static function embedFor(Restaurant $restaurant): ?string
    {
        $point = self::fromUrl($restaurant->google_maps_url);

        if ($point === null) {
            return null;
        }

        [$lat, $lng] = $point;

        $bbox = implode(',', [
            number_format($lng - self::SPAN, 6, '.', ''),
            number_format($lat - self::SPAN / 2, 6, '.', ''),
            number_format($lng + self::SPAN, 6, '.', ''),
            number_format($lat + self::SPAN / 2, 6, '.', ''),
        ]);

        $marker = number_format($lat, 6, '.', '').','.number_format($lng, 6, '.', '');

        return 'https://www.openstreetmap.org/export/embed.html?bbox='.$bbox.'&layer=mapnik&marker='.$marker;
    }

    /**
     * The point written into a map link, as `[lat, lng]`.
     *
     * Google writes coordinates several ways — `?q=`, `?ll=`, `?query=`, and
     * `/@lat,lng,17z` in a place URL — and a shortened `maps.app.goo.gl` link
     * hides them behind a redirect. Null means "no map to draw", not "bad link".
     *
     * @return array{0: float, 1: float}|null
     */
    public static function fromUrl(?string $url): ?array
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        parse_str($parts['query'] ?? '', $query);

        $candidates = [
            $query['q'] ?? null,
            $query['ll'] ?? null,
            $query['query'] ?? null,
            // `/maps/place/Name/@33.8886,35.4955,17z/...`
            explode('/@', $parts['path'] ?? '')[1] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || ! preg_match(self::LAT_LNG, $candidate, $found)) {
                continue;
            }

            $lat = (float) $found[1];
            $lng = (float) $found[2];

            // Anything off the globe came from a zoom level or an id, not a point.
            if (abs($lat) > 90 || abs($lng) > 180) {
                continue;
            }

            return [$lat, $lng];
        }

        return null;
    }
}
