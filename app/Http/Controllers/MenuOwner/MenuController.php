<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        return view('menu-owner.menus.index');
    }
}
