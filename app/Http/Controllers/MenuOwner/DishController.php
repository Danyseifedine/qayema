<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DishRequest;
use App\Models\Category;
use App\Models\Dish;
use App\Services\ImageOptimizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DishController extends Controller
{
    public function __construct(private readonly ImageOptimizationService $imageService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->currentMenu();

        $dishes = $menu
            ? Dish::where('menu_id', $menu->id)
                ->with('category')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get()
            : collect();

        return view('menu-owner.dishes.index', [
            'dishes' => $dishes,
            'menu' => $menu,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $menu = $user->currentMenu();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding dishes.');
        }

        if ($menu->hasReachedDishLimit()) {
            return redirect()->route('menu-owner.dishes.index')
                ->with('error', 'You have reached the maximum number of dishes allowed.');
        }

        $categories = Category::where('menu_id', $menu->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('menu-owner.dishes.form', [
            'dish' => null,
            'menu' => $menu,
            'categories' => $categories,
        ]);
    }

    public function store(DishRequest $request): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->currentMenu();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding dishes.');
        }

        if ($menu->hasReachedDishLimit()) {
            return redirect()->route('menu-owner.dishes.index')
                ->with('error', 'You have reached the maximum number of dishes allowed for your menu.');
        }

        $data = $request->validated();
        $data['menu_id'] = $menu->id;

        $dish = Dish::create($data);

        $this->handleImageUploads($request, $dish);

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish created successfully!');
    }

    public function edit(Dish $dish): View|RedirectResponse
    {
        $this->authorize('update', $dish);

        $menu = $dish->menu;
        $categories = Category::where('menu_id', $menu->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('menu-owner.dishes.form', [
            'dish' => $dish,
            'menu' => $menu,
            'categories' => $categories,
        ]);
    }

    public function update(DishRequest $request, Dish $dish): RedirectResponse
    {
        $this->authorize('update', $dish);

        $data = $request->validated();
        $dish->update($data);

        if ($request->has('delete_images') && is_array($request->input('delete_images'))) {
            foreach ($request->input('delete_images') as $imageId) {
                $media = $dish->getMedia('images')->where('id', (int) $imageId)->first();
                if ($media) {
                    $media->delete();
                }
            }
        }

        $this->handleImageUploads($request, $dish);

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish updated successfully!');
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        $this->authorize('delete', $dish);

        $dish->delete();

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish deleted successfully!');
    }

    private function handleImageUploads(Request $request, Dish $dish): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        foreach (Arr::wrap($request->file('images')) as $image) {
            if (! $image instanceof UploadedFile || ! $image->isValid()) {
                continue;
            }

            try {
                $optimizedPath = $this->imageService->optimizeDishImage($image);

                if (! file_exists($optimizedPath) || ! is_readable($optimizedPath)) {
                    Log::error('Optimized dish image not readable: '.$optimizedPath);

                    continue;
                }

                $dish->addMedia($optimizedPath)
                    ->usingName(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
                    ->toMediaCollection('images');

                if (file_exists($optimizedPath)) {
                    unlink($optimizedPath);
                }
            } catch (\Exception $e) {
                Log::error('Failed to save dish image: '.$e->getMessage());
            }
        }
    }
}
