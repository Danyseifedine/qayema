<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Resources\SettingsResource;
use App\Models\Restaurant;
use App\Services\Global\MediaService;
use App\Services\Global\OpeningHours;
use Illuminate\Http\Request;

/**
 * The owner's restaurant profile: display name, description, contact details,
 * location and branding. The slug stays read-only. Always scoped to the
 * authenticated user's own restaurant, so there's no cross-restaurant surface.
 */
class SettingsController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function show(Request $request): SettingsResource
    {
        return (new SettingsResource($this->restaurant($request)))->additional([
            'meta' => $this->meta(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): SettingsResource
    {
        $restaurant = $this->restaurant($request);

        // Translatable fields are written to the restaurant's own default locale
        // — the single language the owner manages — matching onboarding.
        $locale = $restaurant->default_locale ?: 'ar';

        $restaurant->setTranslation('name', $locale, $request->validated('name'));
        $restaurant->setTranslation('description', $locale, (string) $request->validated('description'));

        $restaurant->fill([
            'opening_hours' => OpeningHours::normalise((array) $request->validated('opening_hours')),
            'timezone' => $request->validated('timezone'),
            'google_maps_url' => $request->validated('google_maps_url'),
            'country_code' => $request->validated('country_code'),
            'phone' => $request->validated('phone'),
            'currency' => $request->validated('currency'),
        ])->save();

        // The logo can be replaced but never cleared (mandatory) — no delete flag.
        $this->media->sync($restaurant, $request->input('logo_key'), false, 'logo', 'logo');
        $this->media->sync($restaurant, $request->input('cover_image_key'), $request->boolean('delete_cover_image'), 'cover_image', 'cover');

        return (new SettingsResource($restaurant->fresh()))->additional([
            'meta' => $this->meta(),
        ]);
    }

    /**
     * @return array{currencies: array<int, array<string, string>>}
     */
    private function meta(): array
    {
        return [
            'currencies' => $this->currencies(),
        ];
    }

    /**
     * @return array<int, array{code: string, name: string, symbol: string}>
     */
    private function currencies(): array
    {
        return collect(config('currencies', []))
            ->map(fn (array $info, string $code): array => [
                'code' => $code,
                'name' => $info['name'],
                'symbol' => $info['symbol'],
            ])
            ->values()
            ->all();
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        return $restaurant;
    }
}
