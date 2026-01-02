<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        $categories = $menu
            ? Category::where('menu_id', $menu->id)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get()
            : collect();

        return view('menu-owner.categories.index', [
            'categories' => $categories,
            'menu' => $menu,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding categories.');
        }

        if ($menu->hasReachedCategoryLimit()) {
            return redirect()->route('menu-owner.categories.index')
                ->with('error', 'You have reached the maximum number of categories allowed.');
        }

        return view('menu-owner.categories.form', [
            'category' => null,
            'menu' => $menu,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding categories.');
        }

        if ($menu->hasReachedCategoryLimit()) {
            return redirect()->route('menu-owner.categories.index')
                ->with('error', 'You have reached the maximum number of categories allowed.');
        }

        $data = $request->validated();
        $data['menu_id'] = $menu->id;

        $category = Category::create($data);

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            $imageService = app(\App\Services\ImageOptimizationService::class);
            $optimizedPath = $imageService->optimizeCategoryImage($request->file('image'));
            $category->addMedia($optimizedPath)
                ->usingName(pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('image');
            // Clean up temporary file after Media Library copies it
            if (file_exists($optimizedPath)) {
                @unlink($optimizedPath);
            }
        }

        return redirect()->route('menu-owner.categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function edit(Request $request, Category $category): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure category belongs to user's menu
        if (! $menu || $category->menu_id !== $menu->id) {
            abort(404);
        }

        return view('menu-owner.categories.form', [
            'category' => $category,
            'menu' => $menu,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure category belongs to user's menu
        if (! $menu || $category->menu_id !== $menu->id) {
            abort(404);
        }

        $data = $request->validated();
        $category->update($data);

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            $imageService = app(\App\Services\ImageOptimizationService::class);
            $optimizedPath = $imageService->optimizeCategoryImage($request->file('image'));
            $category->clearMediaCollection('image');
            $category->addMedia($optimizedPath)
                ->usingName(pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('image');
            // Clean up temporary file after Media Library copies it
            if (file_exists($optimizedPath)) {
                @unlink($optimizedPath);
            }
        }

        return redirect()->route('menu-owner.categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure category belongs to user's menu
        if (! $menu || $category->menu_id !== $menu->id) {
            abort(404);
        }

        $category->delete();

        return redirect()->route('menu-owner.categories.index')
            ->with('success', 'Category deleted successfully!');
    }
}
