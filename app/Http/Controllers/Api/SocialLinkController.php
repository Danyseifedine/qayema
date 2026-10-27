<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSocialLinkRequest;
use App\Http\Requests\UpdateSocialLinkRequest;
use App\Http\Resources\SocialLinkResource;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Social link CRUD for the dashboard SPA. Every action is scoped to the
 * authenticated user's restaurant and authorized through
 * RestaurantSocialLinkPolicy.
 */
class SocialLinkController extends Controller
{
    use ResolvesRestaurant;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RestaurantSocialLink::class);
        $restaurant = $this->restaurant($request);

        $links = $restaurant->socialLinks()->orderBy('id')->get();

        return SocialLinkResource::collection($links)->additional([
            'meta' => [
                'used' => $links->count(),
                'limit' => $restaurant->limitOf(Feature::SocialLinkLimit),
            ],
        ]);
    }

    public function store(StoreSocialLinkRequest $request): JsonResponse
    {
        $this->authorize('create', RestaurantSocialLink::class);
        $restaurant = $this->restaurant($request);

        // Re-check the plan limit and insert under a row lock so two concurrent
        // creates can't both slip past the cap (time-of-check/time-of-use).
        $link = DB::transaction(function () use ($request, $restaurant): RestaurantSocialLink {
            $locked = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();

            $locked->ensureRoomFor(Feature::SocialLinkLimit, 'platform', 'You have reached your plan limit of :limit social links.');

            $link = new RestaurantSocialLink($request->validated());
            $link->restaurant()->associate($locked);
            $link->save();

            return $link;
        });

        return (new SocialLinkResource($link))->response()->setStatusCode(201);
    }

    public function update(UpdateSocialLinkRequest $request, RestaurantSocialLink $socialLink): SocialLinkResource
    {
        $this->authorize('update', $socialLink);

        $socialLink->update($request->validated());

        return new SocialLinkResource($socialLink);
    }

    public function destroy(RestaurantSocialLink $socialLink): JsonResponse
    {
        $this->authorize('delete', $socialLink);

        $socialLink->delete();

        return response()->json(null, 204);
    }
}
