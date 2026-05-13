<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DishRequest;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DishController extends Controller
{
    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        $dishes = $restaurant
            ? $restaurant->dishes()->with('category')->orderBy('name')->get()
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

        if ($restaurant->hasReachedDishLimit()) {
            return redirect()->route('menu-owner.dishes.index')
                ->with('error', __('menu_owner.common.messages.limit_dishes'));
        }

        return view('dashboard.dishes.form', [
            'dish' => null,
            'restaurant' => $restaurant,
            'categories' => $restaurant->categories()->orderBy('name')->get(),
            'tagValues' => [],
        ]);
    }

    public function store(DishRequest $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return redirect()->route('menu-owner.restaurant.index')
                ->with('error', __('menu_owner.common.messages.setup_first'));
        }

        if ($restaurant->hasReachedDishLimit()) {
            return redirect()->route('menu-owner.dishes.index')
                ->with('error', __('menu_owner.common.messages.limit_dishes'));
        }

        $data = $request->validated();
        $data['restaurant_id'] = $restaurant->id;
        $data['display_order'] = $restaurant->dishes()->max('display_order') + 1;

        $dish = Dish::create($data);

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
            'categories' => $restaurant->categories()->orderBy('name')->get(),
            'tagValues' => $dish->tags ?? [],
        ]);
    }

    public function update(DishRequest $request, Dish $dish): RedirectResponse
    {
        $this->authorize('update', $dish);

        $dish->update($request->validated());

        $this->syncImage($request, $dish);

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', __('menu_owner.common.messages.dish_updated'));
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

    private function syncImage(DishRequest $request, Dish $dish): void
    {
        if ($request->filled('dish_image_key')) {
            $path = storage_path('app/temp/'.$request->input('dish_image_key').'.jpg');

            if (file_exists($path)) {
                $dish->clearMediaCollection('images');
                $dish->addMedia($path)->usingName('dish-image')->toMediaCollection('images');
            }

            return;
        }

        if ($request->boolean('delete_dish_image')) {
            $dish->clearMediaCollection('images');
        }
    }
}
