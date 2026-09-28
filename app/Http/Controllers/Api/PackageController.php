<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * What the owner can be on. Read-only: nothing is sold here; an owner asks
 * for a package and an admin assigns it.
 */
class PackageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        // The effective package, so an expired assignment reports as the
        // default rather than as something the owner no longer has.
        $current = $request->user()->restaurant?->effectivePackage();

        $packages = Package::query()->orderBy('sort_order')->orderBy('id')->get();

        return PackageResource::collection($packages)->additional([
            'meta' => [
                'current' => $current?->slug,
            ],
        ]);
    }
}
