<?php

namespace Tests\Unit\Services;

use App\Models\Restaurant;
use App\Services\Global\MapPoint;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MapPointTest extends TestCase
{
    /** @return array<string, array{0: string, 1: array{0: float, 1: float}|null}> */
    public static function links(): array
    {
        return [
            'the ?q= the dashboard writes' => ['https://www.google.com/maps?q=33.888600,35.495500', [33.8886, 35.4955]],
            'a ?ll= link' => ['https://maps.google.com/?ll=33.8886,35.4955&z=17', [33.8886, 35.4955]],
            'a ?query= link' => ['https://www.google.com/maps/search/?api=1&query=33.8886,35.4955', [33.8886, 35.4955]],
            'a place URL with /@' => ['https://www.google.com/maps/place/Beirut/@33.8886,35.4955,17z/data=!3m1', [33.8886, 35.4955]],
            'a space between them' => ['https://maps.google.com/?q=33.8886 35.4955', [33.8886, 35.4955]],
            'the southern and western hemispheres' => ['https://maps.google.com/?q=-33.8688,-151.2093', [-33.8688, -151.2093]],
            'whole degrees' => ['https://maps.google.com/?q=33,35', [33.0, 35.0]],

            // Null is "no map to draw", never an error.
            'a shortened link hiding the point' => ['https://maps.app.goo.gl/abc123', null],
            'a search by name' => ['https://www.google.com/maps/search/pizza', null],
            'a latitude off the globe' => ['https://maps.google.com/?q=91.5,35.4', null],
            'a longitude off the globe' => ['https://maps.google.com/?q=33.8,181.2', null],
            'not a URL at all' => ['somewhere near the corner', null],
            'a zoom level mistaken for a point' => ['https://www.google.com/maps/place/Beirut', null],
        ];
    }

    #[DataProvider('links')]
    public function test_it_reads_the_point_out_of_a_map_link(string $url, ?array $expected): void
    {
        $this->assertSame($expected, MapPoint::fromUrl($url));
    }

    public function test_an_empty_link_has_no_point(): void
    {
        $this->assertNull(MapPoint::fromUrl(null));
        $this->assertNull(MapPoint::fromUrl(''));
    }

    public function test_the_embed_boxes_the_point_and_pins_it(): void
    {
        $restaurant = new Restaurant(['google_maps_url' => 'https://maps.google.com/?q=33.8886,35.4955']);

        $embed = MapPoint::embedFor($restaurant);

        $this->assertNotNull($embed);
        $this->assertStringStartsWith('https://www.openstreetmap.org/export/embed.html?', $embed);
        // bbox is minLng,minLat,maxLng,maxLat — longitude first, as OSM wants.
        $this->assertStringContainsString('bbox=35.491500,33.886600,35.499500,33.890600', $embed);
        $this->assertStringContainsString('marker=33.888600,35.495500', $embed);
        // No key, and no Google.
        $this->assertStringNotContainsString('key=', $embed);
    }

    public function test_a_restaurant_without_a_readable_link_gets_no_map(): void
    {
        $this->assertNull(MapPoint::embedFor(new Restaurant(['google_maps_url' => null])));
        $this->assertNull(MapPoint::embedFor(new Restaurant(['google_maps_url' => 'https://maps.app.goo.gl/abc123'])));
    }
}
