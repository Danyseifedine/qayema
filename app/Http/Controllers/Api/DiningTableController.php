<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiningTablesRequest;
use App\Http\Requests\UpdateDiningTableRequest;
use App\Http\Resources\DiningTableResource;
use App\Models\DiningTable;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The restaurant's tables, for the dashboard's Tables page. Each has its own
 * QR code for ordering from the seat, which is a package feature of its own
 * (`dine_in`). A table is looked up through the owner's own
 * restaurant, so another restaurant's table is simply not found.
 */
class DiningTableController extends Controller
{
    use ResolvesRestaurant;

    public function index(Request $request): AnonymousResourceCollection
    {
        $restaurant = $this->tablesRestaurant($request);
        $tables = $restaurant->diningTables()->get();

        return DiningTableResource::collection($tables->each->setRelation('restaurant', $restaurant))->additional([
            'meta' => [
                'limit' => (int) config('menu.tables.max'),
                // Whether guests can order to them now (not switched off).
                'takes_orders' => $restaurant->takesDineIn(),
            ],
        ]);
    }

    public function store(StoreDiningTablesRequest $request): JsonResponse
    {
        $restaurant = $this->tablesRestaurant($request);

        // Counted under a row lock, so two adds at once cannot both slip
        // past the cap.
        $created = DB::transaction(function () use ($request, $restaurant) {
            $locked = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();
            $count = $locked->diningTables()->count();
            $names = $request->names();
            $max = (int) config('menu.tables.max');

            if ($count + count($names) > $max) {
                throw ValidationException::withMessages([
                    'names' => __('A restaurant can have up to :max tables.', ['max' => $max]),
                ]);
            }

            $next = (int) $locked->diningTables()->max('sort_order') + 1;

            return collect($names)->map(fn (string $name, int $index): DiningTable => $locked->diningTables()->create([
                'name' => $name,
                'sort_order' => $next + $index,
            ]));
        });

        return DiningTableResource::collection($created->each->setRelation('restaurant', $restaurant))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateDiningTableRequest $request, int $table): DiningTableResource
    {
        $restaurant = $this->tablesRestaurant($request);
        $found = $this->find($restaurant, $table);

        $found->update(['name' => $request->validated('name')]);

        return new DiningTableResource($found);
    }

    /** A new code for the table: its printed QR code stops working. */
    public function newCode(Request $request, int $table): DiningTableResource
    {
        $restaurant = $this->tablesRestaurant($request);
        $found = $this->find($restaurant, $table);

        $found->newCode();

        return new DiningTableResource($found);
    }

    /** Orders made at it keep its name; they just no longer point at it. */
    public function destroy(Request $request, int $table): JsonResponse
    {
        $restaurant = $this->tablesRestaurant($request);
        $this->find($restaurant, $table)->delete();

        return response()->json(null, 204);
    }

    private function tablesRestaurant(Request $request): Restaurant
    {
        $restaurant = $this->restaurant($request, __('Create your restaurant before adding tables.'));

        abort_unless(
            $restaurant->entitlements()->can(Feature::DineIn),
            403,
            __('Ordering at the table is not on your package.'),
        );

        return $restaurant;
    }

    private function find(Restaurant $restaurant, int $id): DiningTable
    {
        return $restaurant->diningTables()->findOrFail($id)->setRelation('restaurant', $restaurant);
    }
}
