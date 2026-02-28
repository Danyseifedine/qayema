<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialLinkRequest;
use App\Models\MenuSocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialLinkController extends Controller
{
    public function index(Request $request): View
    {
        $menu = $request->user()->currentMenu();

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
        $menu = $request->user()->currentMenu();

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
        $menu = $request->user()->currentMenu();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding social links.');
        }

        if ($menu->hasReachedSocialLinkLimit()) {
            return redirect()->route('menu-owner.social-links.index')
                ->with('error', "You have reached the maximum limit of {$menu->social_link_limit} social links for this menu.");
        }

        $data = $request->validated();
        $data['menu_id'] = $menu->id;

        MenuSocialLink::create($data);

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', 'Social link created successfully!');
    }

    public function edit(Request $request, MenuSocialLink $socialLink): View
    {
        $this->authorize('update', $socialLink);

        return view('menu-owner.social-links.form', [
            'socialLink' => $socialLink,
            'menu' => $socialLink->menu,
        ]);
    }

    public function update(SocialLinkRequest $request, MenuSocialLink $socialLink): RedirectResponse
    {
        $this->authorize('update', $socialLink);

        $data = $request->validated();
        $socialLink->update($data);

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', 'Social link updated successfully!');
    }

    public function destroy(Request $request, MenuSocialLink $socialLink): RedirectResponse
    {
        $this->authorize('delete', $socialLink);

        $socialLink->delete();

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', 'Social link deleted successfully!');
    }
}
