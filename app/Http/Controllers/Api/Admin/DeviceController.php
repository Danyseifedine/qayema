<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyDeviceRequest;
use App\Http\Requests\Admin\StoreDeviceRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;

/**
 * The admin app's phones that get notifications. The app sends its token
 * after signing in and whenever Firebase renews it, and takes it back when
 * the admin signs out.
 */
class DeviceController extends Controller
{
    /**
     * One row per phone: a phone already known moves to whoever signed in
     * on it now.
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        DeviceToken::query()->updateOrCreate(
            ['token' => $request->string('token')->value()],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->string('platform')->value(),
                'last_seen_at' => now(),
            ],
        );

        return response()->json(status: 204);
    }

    /** Only the admin's own phone; someone else's token is left alone. */
    public function destroy(DestroyDeviceRequest $request): JsonResponse
    {
        $request->user()->deviceTokens()->where('token', $request->string('token')->value())->delete();

        return response()->json(status: 204);
    }
}
