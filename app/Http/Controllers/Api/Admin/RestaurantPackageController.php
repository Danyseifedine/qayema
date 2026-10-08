<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePackageRequest;
use App\Http\Requests\Admin\ExtendPackageRequest;
use App\Http\Resources\AdminRestaurantResource;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;

/**
 * A restaurant's package from the admin app, through PackageAssigner like
 * every admin change, so each one lands in its package history with the
 * admin and the note.
 */
class RestaurantPackageController extends Controller
{
    public function __construct(private readonly PackageAssigner $assigner) {}

    /** Another package from today, for some months or forever. */
    public function update(ChangePackageRequest $request, Restaurant $restaurant): AdminRestaurantResource
    {
        $months = $request->integer('months') ?: null;

        $this->assigner->assign(
            $restaurant,
            Package::findOrFail($request->integer('package_id')),
            now(),
            $months === null ? null : PackageAssigner::endAfter(null, $months),
            $request->input('note'),
        );

        return $this->fresh($restaurant);
    }

    /** More time on the package it is on. */
    public function extend(ExtendPackageRequest $request, Restaurant $restaurant): AdminRestaurantResource
    {
        $this->assigner->extend($restaurant, $request->integer('months') ?: null, $request->input('note'));

        return $this->fresh($restaurant);
    }

    private function fresh(Restaurant $restaurant): AdminRestaurantResource
    {
        return AdminRestaurantResource::detail($restaurant);
    }
}
