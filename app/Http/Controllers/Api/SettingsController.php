<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Resources\SettingsResource;
use App\Models\Restaurant;
use App\Models\Tag;
use App\Services\Global\MediaService;
use Illuminate\Http\Request;

/**
 * The owner's menu settings: their restaurant's editable display name, editable
 * logo + banner, phone/currency and cuisine/dietary tags. The slug stays
 * read-only. Always scoped to the authenticated user's own restaurant, so there's
 * no cross-restaurant surface.
 */
class SettingsController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function show(Request $request): SettingsResource
    {
        $restaurant = $this->restaurant($request);

        return (new SettingsResource($restaurant->load('tags')))->additional([
            'meta' => $this->meta(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): SettingsResource
    {
        $restaurant = $this->restaurant($request);

        // The slug stays read-only; the display name, phone, country code and
        // currency update. The name is written to the restaurant's own default
        // locale (the single language the owner manages), matching onboarding.
        $locale = $restaurant->default_locale ?: 'ar';
        $restaurant->setTranslation('name', $locale, $request->validated('name'));
        $restaurant->fill([
            'country_code' => $request->validated('country_code'),
            'phone' => $request->validated('phone'),
            'currency' => $request->validated('currency'),
        ])->save();

        // The logo can be replaced but never cleared (mandatory) — no delete flag.
        $this->media->sync($restaurant, $request->input('logo_key'), false, 'logo', 'logo');
        $this->media->sync($restaurant, $request->input('cover_image_key'), $request->boolean('delete_cover_image'), 'cover_image', 'cover');

        $this->syncTags($restaurant, $request->validated('tag_ids'));

        return (new SettingsResource($restaurant->load('tags')))->additional([
            'meta' => $this->meta(),
        ]);
    }

    /**
     * @return array{tags: array<int, array<string, mixed>>, currencies: array<int, array<string, string>>}
     */
    private function meta(): array
    {
        return [
            'tags' => $this->availableTags(),
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

    /**
     * Replace the restaurant's owner-selectable (cuisine/dietary) tags, leaving
     * any other-category tags (vibe/style) untouched.
     *
     * @param  array<int, int>  $tagIds
     */
    private function syncTags(Restaurant $restaurant, array $tagIds): void
    {
        $restaurant->tags()->detach(Tag::query()->whereIn('category', Tag::OWNER_CATEGORIES)->pluck('id'));

        $allowed = Tag::query()
            ->whereIn('id', $tagIds)
            ->whereIn('category', Tag::OWNER_CATEGORIES)
            ->pluck('id');

        $restaurant->tags()->attach($allowed);
    }

    /**
     * The cuisine/dietary tags the owner can choose from.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availableTags(): array
    {
        return Tag::query()
            ->whereIn('category', Tag::OWNER_CATEGORIES)
            ->orderBy('category')
            ->orderBy('id')
            ->get()
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'category' => $tag->category,
                'name' => [
                    'en' => $tag->getTranslation('name', 'en', false) ?: null,
                    'ar' => $tag->getTranslation('name', 'ar', false) ?: null,
                ],
            ])
            ->all();
    }
}
