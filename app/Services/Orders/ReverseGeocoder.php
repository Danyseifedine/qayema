<?php

namespace App\Services\Orders;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * A guest's shared location as an address line ("Bliss Street, Ras Beirut,
 * Beirut"), to start the cart's address box.
 *
 * OpenStreetMap's Nominatim, asked from the server: the guest's own browser
 * never contacts it, and the answer is kept for a month per spot (about 11 m),
 * since Nominatim's free service asks callers to identify themselves and to
 * go easy on it. Null when it cannot say, and the guest just types.
 */
class ReverseGeocoder
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/reverse';

    public function address(float $latitude, float $longitude, string $locale): ?string
    {
        $key = sprintf('geocode:%s:%.4f,%.4f', $locale, $latitude, $longitude);

        return Cache::remember($key, now()->addDays(30), fn (): ?string => $this->lookUp($latitude, $longitude, $locale));
    }

    private function lookUp(float $latitude, float $longitude, string $locale): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name').' ('.config('app.url').')',
                'Accept-Language' => $locale.',en',
            ])->connectTimeout(3)->timeout(5)->get(self::ENDPOINT, [
                'format' => 'jsonv2',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 18,
                'addressdetails' => 1,
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return self::line((array) $response->json('address', []));
    }

    /**
     * The parts a driver needs, most precise first: the street (with its
     * number), the neighbourhood and the town. The postcode, district and
     * country add length and nothing a local driver uses.
     *
     * @param  array<string, mixed>  $address
     */
    public static function line(array $address): ?string
    {
        $street = trim(implode(' ', array_filter([
            $address['house_number'] ?? null,
            $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? null,
        ])));

        $parts = array_values(array_unique(array_filter([
            $street !== '' ? $street : null,
            $address['neighbourhood'] ?? $address['quarter'] ?? $address['suburb'] ?? null,
            $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null,
        ], fn ($part): bool => is_string($part) && trim($part) !== '')));

        return $parts === [] ? null : implode(', ', $parts);
    }
}
