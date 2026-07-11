<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The menu templates an owner can choose from, and the one their restaurant is
 * currently using. A newly onboarded restaurant has no template and must pick
 * one before the rest of the dashboard unlocks. Always scoped to the
 * authenticated user's own restaurant.
 */
class TemplateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);

        return $this->collection($restaurant);
    }

    public function select(SelectTemplateRequest $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);

        $template = Template::query()
            ->where('is_active', true)
            ->findOrFail($request->validated('template_id'));

        $restaurant->update([
            'template_id' => $template->id,
            'template_settings' => $template->defaultSettings(),
        ]);

        return $this->collection($restaurant);
    }

    private function collection(Restaurant $restaurant): AnonymousResourceCollection
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return TemplateResource::collection($templates)->additional([
            'meta' => ['current' => $restaurant->template_id],
        ]);
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        return $restaurant;
    }
}
