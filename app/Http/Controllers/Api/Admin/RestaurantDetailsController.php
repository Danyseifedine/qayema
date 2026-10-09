<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetOwnerPasswordRequest;
use App\Http\Requests\Admin\UpdateRestaurantRequest;
use App\Http\Resources\AdminRestaurantResource;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Illuminate\Http\JsonResponse;

/**
 * A restaurant's basics, and its owner's password, from the admin app.
 */
class RestaurantDetailsController extends Controller
{
    /**
     * Name, menu link and phone. A changed link is kept as a former one by
     * the restaurant's own hook, so printed QR codes keep working.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): AdminRestaurantResource
    {
        $restaurant->setTranslation('name', MenuLanguages::main($restaurant), $request->string('name')->trim()->value());
        $restaurant->slug = $request->string('slug')->value();
        $restaurant->phone = $request->input('phone');
        $restaurant->save();

        return AdminRestaurantResource::detail($restaurant);
    }

    /**
     * The owner signs in with the new password from now on. Their open
     * dashboard sessions end on their next request (the session keeps the
     * old password's hash), and the remember-me cookie stops working.
     */
    public function resetOwnerPassword(ResetOwnerPasswordRequest $request, Restaurant $restaurant): JsonResponse
    {
        $restaurant->user->forceFill([
            'password' => $request->string('password')->value(),
            'remember_token' => null,
        ])->save();

        return response()->json(status: 204);
    }
}
