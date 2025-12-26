<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        $menuUrl = null;
        if ($menu && $menu->is_active && $menu->slug) {
            $menuUrl = route('public.menu', $menu->slug);
        }

        return view('menu-owner.qr-code.index', [
            'menu' => $menu,
            'menuUrl' => $menuUrl,
        ]);
    }

    public function generate(Request $request)
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        if (! $menu || ! $menu->is_active || ! $menu->slug) {
            abort(404, 'Menu not found or not active');
        }

        $menuUrl = route('public.menu', $menu->slug);

        return response(QrCode::size(300)
            ->format('svg')
            ->generate($menuUrl))
            ->header('Content-Type', 'image/svg+xml');
    }
}
