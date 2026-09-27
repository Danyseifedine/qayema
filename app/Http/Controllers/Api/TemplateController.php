<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelectTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The design store: what an owner can pick from, and switching between them.
 *
 * Every active template is free and open to every restaurant — what a package
 * grants is limits and features, never a design. A newly onboarded restaurant
 * has no template and must pick one before the rest of the dashboard unlocks.
 */
class TemplateController extends Controller
{
    use ResolvesRestaurant;

    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->collection($this->restaurant($request));
    }

    /**
     * Switch to a template. Every active design is available, so this never
     * fails on entitlement. The colours the owner chose for each design are
     * kept: switching back brings them back (Restaurant::designSettings()).
     */
    public function select(SelectTemplateRequest $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);
        $template = $this->activeTemplate($request->validated('template_id'));

        $restaurant->update(['template_id' => $template->id]);

        return $this->collection($restaurant);
    }

    private function collection(Restaurant $restaurant): AnonymousResourceCollection
    {
        return TemplateResource::collection(Template::query()->active()->get())->additional([
            'meta' => [
                'current' => $restaurant->template_id,
            ],
        ]);
    }

    private function activeTemplate(int $id): Template
    {
        return Template::query()->active()->findOrFail($id);
    }
}
