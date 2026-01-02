<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialLinkRequest;
use App\Models\Menu;
use App\Models\MenuSocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialLinkController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        $socialLinks = $menu
            ? MenuSocialLink::where('menu_id', $menu->id)
                ->orderBy('created_at')
                ->get()
            : collect();

        return view('menu-owner.social-links.index', [
            'socialLinks' => $socialLinks,
            'menu' => $menu,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding social links.');
        }

        return view('menu-owner.social-links.form', [
            'socialLink' => null,
            'menu' => $menu,
        ]);
    }

    public function store(SocialLinkRequest $request): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding social links.');
        }

        $data = $request->validated();
        $data['menu_id'] = $menu->id;

        MenuSocialLink::create($data);

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', 'Social link created successfully!');
    }

    public function edit(Request $request, MenuSocialLink $socialLink): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure social link belongs to user's menu
        if (! $menu || $socialLink->menu_id !== $menu->id) {
            abort(404);
        }

        return view('menu-owner.social-links.form', [
            'socialLink' => $socialLink,
            'menu' => $menu,
        ]);
    }

    public function update(SocialLinkRequest $request, MenuSocialLink $socialLink): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure social link belongs to user's menu
        if (! $menu || $socialLink->menu_id !== $menu->id) {
            abort(404);
        }

        $data = $request->validated();
        $socialLink->update($data);

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', 'Social link updated successfully!');
    }

    public function destroy(Request $request, MenuSocialLink $socialLink): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure social link belongs to user's menu
        if (! $menu || $socialLink->menu_id !== $menu->id) {
            abort(404);
        }

        $socialLink->delete();

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', 'Social link deleted successfully!');
    }
}
