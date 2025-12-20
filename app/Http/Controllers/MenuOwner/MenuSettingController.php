<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuSettingController extends Controller
{
    public function index(): View
    {
        return view('menu-owner.settings.index');
    }
}
