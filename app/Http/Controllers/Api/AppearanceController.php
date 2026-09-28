<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppearanceRequest;
use App\Models\Restaurant;
use App\Services\Menu\MenuFonts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The dashboard's Appearance page. The design's settings belong to the design
 * in use — each design declares its own (Template::editableSettings(): colours,
 * on/off switches, choices, short text) and remembers what the owner picked
 * for it. Fonts belong to the restaurant: one per writing system the menu
 * uses, the same in every design.
 *
 * Reading is open so the page can show what an upgrade would unlock; saving
 * needs the package's `appearance` flag. The design is the one the menu is
 * drawn in (Restaurant::menuTemplate()), which is the owner's choice unless
 * their package no longer allows it.
 */
class AppearanceController extends Controller
{
    use ResolvesRestaurant;

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($this->withDesign($request))]);
    }

    public function update(UpdateAppearanceRequest $request): JsonResponse
    {
        $restaurant = $this->withDesign($request);
        abort_unless($restaurant->hasAppearance(), 403, __('Appearance is not on your package.'));

        if ($request->has('settings')) {
            $restaurant->saveDesignSettings($restaurant->menuTemplate(), $request->settings());
        }

        if ($request->has('fonts')) {
            $fonts = array_replace((array) $restaurant->menu_fonts, (array) $request->validated('fonts'));
            $restaurant->update(['menu_fonts' => array_filter($fonts, fn ($family): bool => $family !== null)]);
        }

        return response()->json(['data' => $this->payload($restaurant->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Restaurant $restaurant): array
    {
        $design = $restaurant->menuTemplate();
        // What the owner chose, even while the package keeps it off the menu.
        $values = $design->resolveSettings($restaurant->chosenDesignSettings($design));
        $catalogue = MenuFonts::catalogue();
        $scripts = MenuFonts::scripts($restaurant);

        return [
            'design' => [
                'id' => $design->id,
                'name' => [
                    'en' => $design->getTranslation('name', 'en', false) ?: null,
                    'ar' => $design->getTranslation('name', 'ar', false) ?: null,
                ],
            ],
            'settings' => array_map(fn (array $row): array => [
                'key' => $row['key'],
                'type' => $row['type'],
                'label' => [
                    'en' => $row['label']['en'] ?? null,
                    'ar' => $row['label']['ar'] ?? null,
                ],
                'default' => $design->defaultSettings()[$row['key']] ?? null,
                'value' => $values[$row['key']] ?? null,
                'contrast_with' => $row['contrast_with'] ?? null,
                'options' => array_values($row['options'] ?? []),
            ], $design->editableSettings()),
            'fonts' => array_map(fn (string $script, array $languages): array => [
                'script' => $script,
                'languages' => $languages,
                'value' => MenuFonts::chosen($restaurant, $script),
                'default' => $catalogue[$script]['default'],
                'sample' => $catalogue[$script]['sample'],
                'options' => array_map(
                    fn (string $family, array $font): array => ['family' => $family, 'category' => $font['category']],
                    array_keys($catalogue[$script]['fonts']),
                    $catalogue[$script]['fonts'],
                ),
            ], array_keys($scripts), $scripts),
        ];
    }

    /** The restaurant, once it has a design: the settings are that design's. */
    private function withDesign(Request $request): Restaurant
    {
        $restaurant = $this->restaurant($request);

        abort_if($restaurant->menuTemplate() === null, 403, __('Choose a template first.'));

        return $restaurant;
    }
}
