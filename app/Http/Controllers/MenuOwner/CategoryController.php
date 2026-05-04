<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\ImageOptimizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly ImageOptimizationService $imageService) {}

    public function index(Request $request): View
    {
        $menu = $request->user()->currentMenu();

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
        $menu = $request->user()->currentMenu();

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
        $menu = $request->user()->currentMenu();

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

        if ($request->hasFile('image')) {
            $optimizedPath = $this->imageService->optimizeCategoryImage($request->file('image'));
            $category->addMedia($optimizedPath)
                ->usingName(pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('image');
            $this->cleanupTemp($optimizedPath);
        }

        return redirect()->route('menu-owner.categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('menu-owner.categories.form', [
            'category' => $category,
            'menu' => $category->menu,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        if ($request->hasFile('image')) {
            $optimizedPath = $this->imageService->optimizeCategoryImage($request->file('image'));
            $category->clearMediaCollection('image');
            $category->addMedia($optimizedPath)
                ->usingName(pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('image');
            $this->cleanupTemp($optimizedPath);
        }

        return redirect()->route('menu-owner.categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()->route('menu-owner.categories.index')
            ->with('success', 'Category deleted successfully!');
    }

    private function cleanupTemp(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
