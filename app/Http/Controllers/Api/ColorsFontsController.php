<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateColorsFontsRequest;
use App\Models\Restaurant;
use App\Services\Menu\MenuFonts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The dashboard's Colors & fonts page. Colours belong to the design in use —
 * each design declares its own (Template::colorSettings()) and remembers what
 * the owner picked for it. Fonts belong to the restaurant: one per writing
 * system the menu uses, the same in every design.
 */
class ColorsFontsController extends Controller
{
    use ResolvesRestaurant;

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($this->withDesign($request))]);
    }

    public function update(UpdateColorsFontsRequest $request): JsonResponse
    {
        $restaurant = $this->withDesign($request);

        if ($request->has('colors')) {
            $restaurant->saveDesignSettings($restaurant->template, (array) $request->validated('colors'));
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
        $design = $restaurant->template;
        $values = $restaurant->designSettings();
        $catalogue = MenuFonts::catalogue();

        return [
            'design' => [
                'id' => $design->id,
                'name' => [
                    'en' => $design->getTranslation('name', 'en', false) ?: null,
                    'ar' => $design->getTranslation('name', 'ar', false) ?: null,
                ],
            ],
            'colors' => array_map(fn (array $row): array => [
                'key' => $row['key'],
                'label' => [
                    'en' => $row['label']['en'] ?? null,
                    'ar' => $row['label']['ar'] ?? null,
                ],
                'default' => $row['default'] ?? null,
                'value' => $values[$row['key']] ?? null,
                'contrast_with' => $row['contrast_with'] ?? null,
            ], $design->colorSettings()),
            'fonts' => array_map(fn (string $script, array $languages): array => [
                'script' => $script,
                'languages' => $languages,
                'value' => MenuFonts::family($restaurant, $script),
                'default' => $catalogue[$script]['default'],
                'sample' => $catalogue[$script]['sample'],
                'options' => array_map(
                    fn (string $family, array $font): array => ['family' => $family, 'category' => $font['category']],
                    array_keys($catalogue[$script]['fonts']),
                    $catalogue[$script]['fonts'],
                ),
            ], array_keys(MenuFonts::scripts($restaurant)), MenuFonts::scripts($restaurant)),
        ];
    }

    /** The restaurant, once it has a design: colours are that design's. */
    private function withDesign(Request $request): Restaurant
    {
        $restaurant = $this->restaurant($request);

        abort_if($restaurant->template === null, 403, __('Choose a template first.'));

        return $restaurant;
    }
}
