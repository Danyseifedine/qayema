<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePackageRequest;
use App\Http\Requests\Admin\ExtendPackageRequest;
use App\Http\Requests\Admin\UpdateBillingRequest;
use App\Http\Resources\AdminRestaurantResource;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Illuminate\Support\Facades\DB;

/**
 * A restaurant's package from the admin app, through PackageAssigner like
 * every admin change, so each one lands in its package history with the
 * admin and the note; and what it pays a month, and whether through Whish.
 */
class RestaurantPackageController extends Controller
{
    public function __construct(private readonly PackageAssigner $assigner) {}

    /**
     * Another package from today, for some months or forever. A price sent
     * with it is saved after the change, so it wins over the assigner
     * clearing the old package's price.
     */
    public function update(ChangePackageRequest $request, Restaurant $restaurant): AdminRestaurantResource
    {
        $months = $request->integer('months') ?: null;

        DB::transaction(function () use ($request, $restaurant, $months): void {
            $this->assigner->assign(
                $restaurant,
                Package::findOrFail($request->integer('package_id')),
                now(),
                $months === null ? null : PackageAssigner::endAfter(null, $months),
                $request->input('note'),
            );

            if (($billing = $request->billing()) !== []) {
                $restaurant->update($billing);
            }
        });

        return $this->fresh($restaurant);
    }

    /**
     * What it pays a month (null: its package's price) and whether through
     * Whish, saved like the restaurant page's Package section: a new price
     * is a package history row, and the guests' menu is left alone
     * (Restaurant::ADMIN_ONLY_FIELDS).
     */
    public function billing(UpdateBillingRequest $request, Restaurant $restaurant): AdminRestaurantResource
    {
        $restaurant->update([
            'price_cents' => $request->validated('price_cents'),
            'pays_with_whish' => $request->boolean('pays_with_whish'),
        ]);

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
