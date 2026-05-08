<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        $categories = $restaurant
            ? $restaurant->categories()->orderBy('name')->get()
            : collect();

        return view('dashboard.categories.index', [
            'categories' => $categories,
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

        if ($restaurant->hasReachedCategoryLimit()) {
            return redirect()->route('menu-owner.categories.index')
                ->with('error', __('menu_owner.common.messages.limit_categories'));
        }

        return view('dashboard.categories.form', [
            'category' => null,
            'restaurant' => $restaurant,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return redirect()->route('menu-owner.restaurant.index')
                ->with('error', __('menu_owner.common.messages.setup_first'));
        }

        if ($restaurant->hasReachedCategoryLimit()) {
            return redirect()->route('menu-owner.categories.index')
                ->with('error', __('menu_owner.common.messages.limit_categories'));
        }

        $data = $request->validated();
        $data['restaurant_id'] = $restaurant->id;
        $data['display_order'] = $restaurant->categories()->max('display_order') + 1;

        $category = Category::create($data);

        $this->syncImage($request, $category);

        return redirect()->route('menu-owner.categories.index')
            ->with('success', __('menu_owner.common.messages.category_created'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('dashboard.categories.form', [
            'category' => $category,
            'restaurant' => $category->restaurant,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        $this->syncImage($request, $category);

        return redirect()->route('menu-owner.categories.index')
            ->with('success', __('menu_owner.common.messages.category_updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()->route('menu-owner.categories.index')
            ->with('success', __('menu_owner.common.messages.category_deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate(['a' => 'required|integer', 'b' => 'required|integer']);

        $restaurant = $request->user()->restaurant;

        $catA = Category::where('restaurant_id', $restaurant->id)->findOrFail($request->integer('a'));
        $catB = Category::where('restaurant_id', $restaurant->id)->findOrFail($request->integer('b'));

        [$catA->display_order, $catB->display_order] = [$catB->display_order, $catA->display_order];
        $catA->save();
        $catB->save();

        session()->flash('success', __('menu_owner.common.messages.reorder_success'));

        return response()->json(['success' => true]);
    }

    private function syncImage(CategoryRequest $request, Category $category): void
    {
        if ($request->filled('image_key')) {
            $path = storage_path('app/temp/'.$request->input('image_key').'.jpg');

            if (file_exists($path)) {
                $category->clearMediaCollection('image');
                $category->addMedia($path)->usingName('category-image')->toMediaCollection('image');
            }

            return;
        }

        if ($request->boolean('delete_image')) {
            $category->clearMediaCollection('image');
        }
    }
}
