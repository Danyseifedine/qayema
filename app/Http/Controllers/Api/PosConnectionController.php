<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\LinkPosRequest;
use App\Http\Requests\UpdatePosConnectionRequest;
use App\Http\Resources\PosConnectionResource;
use App\Jobs\SyncPosCatalog;
use App\Models\Restaurant;
use App\Services\Pos\PosLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Connect to POS on the dashboard's Features page: the link as it stands,
 * linking with the POS's address and key, ending it, and "Sync now". Only
 * where an admin switched Connect to POS on for the restaurant.
 */
class PosConnectionController extends Controller
{
    use ResolvesRestaurant;

    public function show(Request $request): JsonResponse
    {
        $restaurant = $this->connectable($request);
        $connection = $restaurant->posConnection;

        return response()->json(['data' => $connection === null ? null : new PosConnectionResource($connection)]);
    }

    public function store(LinkPosRequest $request, PosLink $link): JsonResponse
    {
        $restaurant = $this->connectable($request);

        abort_if($restaurant->posConnection !== null, 409, __('Your menu is already connected to a POS. Disconnect it first.'));

        $connection = $link->link($restaurant, $request->validated('address'), $request->validated('key'));

        return (new PosConnectionResource($connection))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, PosLink $link): JsonResponse
    {
        $link->unlink($this->connectable($request));

        return response()->json(null, 204);
    }

    /**
     * Whether orders placed in the menu go to the POS. Off, they stay on the
     * Orders page; WhatsApp orders never go.
     */
    public function update(UpdatePosConnectionRequest $request): PosConnectionResource
    {
        $restaurant = $this->connectable($request);

        abort_if($restaurant->posConnection === null, 404, __('Your menu is not connected to a POS.'));

        $restaurant->posConnection->update(['sends_orders' => $request->boolean('sends_orders')]);

        return new PosConnectionResource($restaurant->posConnection);
    }

    public function sync(Request $request): JsonResponse
    {
        $restaurant = $this->connectable($request);

        abort_if($restaurant->posConnection === null, 404, __('Your menu is not connected to a POS.'));

        SyncPosCatalog::dispatch($restaurant->id)->afterResponse();

        return response()->json(null, 202);
    }

    private function connectable(Request $request): Restaurant
    {
        $restaurant = $this->restaurant($request);

        abort_unless($restaurant->entitlements()->can(Feature::PosConnection), 403, __('Connect to POS is not switched on for your restaurant.'));

        return $restaurant;
    }
}
