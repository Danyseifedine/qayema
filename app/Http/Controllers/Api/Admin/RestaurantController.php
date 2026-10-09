<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRestaurantsRequest;
use App\Http\Requests\Admin\StoreRestaurantRequest;
use App\Http\Requests\Admin\UpdateRestaurantActiveRequest;
use App\Http\Resources\AdminRestaurantResource;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Packages\PackageAssigner;
use App\Services\Portal\OnboardingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Every restaurant, from the admin phone app: the list, one restaurant,
 * opening a new one with its owner, and switching a menu on or off.
 */
class RestaurantController extends Controller
{
    public const PER_PAGE = 20;

    public function __construct(private readonly OnboardingService $onboarding) {}

    public function index(IndexRestaurantsRequest $request): AnonymousResourceCollection
    {
        $query = Restaurant::query()->with(['user', 'package', 'media']);

        if (($search = $request->search()) !== null) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(fn (Builder $matches) => $matches
                // The whole JSON, so a name matches in whichever language.
                ->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhereHas('user', fn (Builder $owner) => $owner
                    ->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like)));
        }

        match ($request->filter()) {
            IndexRestaurantsRequest::NEW => $query->where('created_at', '>=', now()->subDays(IndexRestaurantsRequest::NEW_DAYS))->latest('id'),
            IndexRestaurantsRequest::ACTIVE => $query->where('is_active', true)->latest('id'),
            IndexRestaurantsRequest::INACTIVE => $query->where('is_active', false)->latest('id'),
            IndexRestaurantsRequest::ENDING => $query->packageActive()->whereNotNull('package_ends_at')
                ->orderBy('package_ends_at')->orderBy('id'),
            IndexRestaurantsRequest::ENDED => $query->packageExpired()
                ->orderByDesc('package_ends_at')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        return AdminRestaurantResource::collection($query->paginate(self::PER_PAGE)->withQueryString());
    }

    public function show(Restaurant $restaurant): AdminRestaurantResource
    {
        return AdminRestaurantResource::detail($restaurant);
    }

    /**
     * The owner's account and their restaurant, together or not at all. The
     * owner signs in with the username or email and the password given, and
     * finishes the rest (phone, currency, logo) on their first visit.
     */
    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $restaurant = DB::transaction(function () use ($request): Restaurant {
            $owner = User::create([
                'name' => $request->string('owner_name')->value(),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => $request->string('password')->value(),
            ]);

            $months = $request->integer('months') ?: null;

            return $this->onboarding->openForOwner(
                $owner,
                $request->string('name')->value(),
                $request->string('slug')->value(),
                $request->integer('package_id'),
                now(),
                $months === null ? null : PackageAssigner::endAfter(null, $months),
                $request->input('note'),
                $request->input('main_locale'),
            );
        });

        return $this->show($restaurant)->response()->setStatusCode(201);
    }

    public function updateActive(UpdateRestaurantActiveRequest $request, Restaurant $restaurant): AdminRestaurantResource
    {
        $restaurant->update(['is_active' => $request->boolean('is_active')]);

        return $this->show($restaurant);
    }
}
