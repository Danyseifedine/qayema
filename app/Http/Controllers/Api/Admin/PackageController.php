<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;

/**
 * The packages an admin can put a restaurant on, in their order, with their
 * price a month. One no longer offered can still be given (a deal agreed by
 * hand), so it is listed and marked.
 */
class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => (string) $package->name,
                'offered' => (bool) $package->is_active,
                'is_default' => (bool) $package->is_default,
                // What it costs a month, so the app can show the price a
                // restaurant pays when it has none of its own.
                'price_cents' => (int) $package->price_cents,
                'currency' => (string) ($package->currency ?: 'USD'),
            ]);

        return response()->json(['data' => $packages]);
    }
}
