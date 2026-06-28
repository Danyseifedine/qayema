<?php

namespace App\Http\Controllers\Api;

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
use Illuminate\Validation\ValidationException;

/**
 * Social link CRUD for the dashboard SPA. Every action is scoped to the
 * authenticated user's restaurant and authorized through
 * RestaurantSocialLinkPolicy.
 */
class SocialLinkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RestaurantSocialLink::class);
        $restaurant = $this->restaurant($request);

        $links = $restaurant->socialLinks()->orderBy('id')->get();

        return SocialLinkResource::collection($links)->additional([
            'meta' => [
                'used' => $links->count(),
                'limit' => $restaurant->social_link_limit,
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

            if ($locked->hasReachedSocialLinkLimit()) {
                throw ValidationException::withMessages([
                    'platform' => __('You have reached your plan limit of :limit social links.', ['limit' => $locked->social_link_limit]),
                ]);
            }

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

    public function destroy(Request $request, RestaurantSocialLink $socialLink): JsonResponse
    {
        $this->authorize('delete', $socialLink);

        $socialLink->delete();

        return response()->json(null, 204);
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        return $restaurant;
    }
}
