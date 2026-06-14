<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DishRequest;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Global\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DishController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        $dishes = $restaurant
            ? $restaurant->dishes()->with('category')->orderBy($this->nameColumn($restaurant))->get()
            : collect();

        return view('dashboard.dishes.index', [
            'dishes' => $dishes,
            'restaurant' => $restaurant,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return redirect()->route('menu-owner.restaurant.index')
                ->with('error', __('menu_owner.common.messages.setup_first'));
        }

        if ($redirect = $this->redirectIfDishLimitReached($restaurant)) {
            return $redirect;
        }

        return view('dashboard.dishes.form', [
            'dish' => null,
            'restaurant' => $restaurant,
            'categories' => $restaurant->categories()->orderBy($this->nameColumn($restaurant))->get(),
            'tagValues' => [],
            'allTags' => \App\Models\Tag::orderBy('category')->get(),
        ]);
    }

    public function store(DishRequest $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return redirect()->route('menu-owner.restaurant.index')
                ->with('error', __('menu_owner.common.messages.setup_first'));
        }

        if ($redirect = $this->redirectIfDishLimitReached($restaurant)) {
            return $redirect;
        }

        $data = $this->translateContent($request->validated(), $restaurant->default_locale ?? 'ar');
        $tagIds = $data['tags'] ?? [];
        unset($data['tags']);

        $data['restaurant_id'] = $restaurant->id;
        $data['display_order'] = $restaurant->dishes()->max('display_order') + 1;

        $dish = Dish::create($data);
        $dish->tags()->sync($tagIds);

        $this->syncImage($request, $dish);

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', __('menu_owner.common.messages.dish_created'));
    }

    public function edit(Dish $dish): View
    {
        $this->authorize('update', $dish);

        $restaurant = $dish->restaurant;

        return view('dashboard.dishes.form', [
            'dish' => $dish,
            'restaurant' => $restaurant,
            'categories' => $restaurant->categories()->orderBy($this->nameColumn($restaurant))->get(),
            'tagValues' => $dish->tags()->pluck('tags.id')->all(),
            'allTags' => \App\Models\Tag::orderBy('category')->get(),
        ]);
    }

    public function update(DishRequest $request, Dish $dish): RedirectResponse
    {
        $this->authorize('update', $dish);

        $data = $this->translateContent($request->validated(), $dish->restaurant->default_locale ?? 'ar', $dish);
        $tagIds = $data['tags'] ?? [];
        unset($data['tags']);

        $dish->update($data);
        $dish->tags()->sync($tagIds);

        $this->syncImage($request, $dish);

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', __('menu_owner.common.messages.dish_updated'));
    }

    /**
     * Wrap submitted plain strings into the restaurant's base-language
     * translation, preserving any existing translation in the other locale.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function translateContent(array $data, string $locale, ?Dish $dish = null): array
    {
        foreach (['name', 'ingredients'] as $field) {
            if (array_key_exists($field, $data) && ! is_array($data[$field])) {
                $existing = $dish?->getTranslations($field) ?? [];
                $data[$field] = array_merge($existing, [$locale => $data[$field]]);
            }
        }

        return $data;
    }

    private function nameColumn(\App\Models\Restaurant $restaurant): string
    {
        return 'name->'.($restaurant->default_locale ?? 'en');
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        $this->authorize('delete', $dish);

        $dish->delete();

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', __('menu_owner.common.messages.dish_deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate(['a' => 'required|integer', 'b' => 'required|integer']);

        $restaurant = $request->user()->restaurant;

        $dishA = $restaurant->dishes()->findOrFail($request->integer('a'));
        $dishB = $restaurant->dishes()->findOrFail($request->integer('b'));

        [$dishA->display_order, $dishB->display_order] = [$dishB->display_order, $dishA->display_order];
        $dishA->save();
        $dishB->save();

        session()->flash('success', __('menu_owner.common.messages.reorder_success'));

        return response()->json(['success' => true]);
    }

    private function redirectIfDishLimitReached(Restaurant $restaurant): ?RedirectResponse
    {
        return $restaurant->hasReachedDishLimit()
            ? redirect()->route('menu-owner.dishes.index')
                ->with('error', __('menu_owner.common.messages.limit_dishes'))
            : null;
    }

    private function syncImage(DishRequest $request, Dish $dish): void
    {
        $this->media->sync(
            $dish,
            $request->input('dish_image_key'),
            $request->boolean('delete_dish_image'),
            'images',
            'dish-image',
        );
    }
}
