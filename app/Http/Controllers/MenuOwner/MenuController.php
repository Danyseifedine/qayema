<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        return view('menu-owner.menus.index', [
            'menu' => $menu,
        ]);
    }

    public function storeOrUpdate(MenuRequest $request): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        $data = $request->validated();

        // Handle checkbox - if not present, set to false
        $data['is_active'] = $request->has('is_active');

        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure slug is unique
        $slug = $data['slug'];
        $counter = 1;
        while (Menu::where('slug', $slug)->where('id', '!=', $menu?->id)->exists()) {
            $slug = $data['slug'] . '-' . $counter;
            $counter++;
        }
        $data['slug'] = $slug;

        if ($menu) {
            // Update existing menu
            $menu->update($data);
            $message = 'Menu updated successfully!';
        } else {
            // Create new menu
            $data['user_id'] = $user->id;
            $data['dish_limit'] = 40; // Set default dish limit
            $data['category_limit'] = 10; // Set default category limit
            $menu = Menu::create($data);
            $menu->setDefaultSettings(); // Set default menu settings
            $message = 'Menu created successfully!';
        }

        return redirect()->route('menu-owner.menus.index')->with('success', $message);
    }
}
