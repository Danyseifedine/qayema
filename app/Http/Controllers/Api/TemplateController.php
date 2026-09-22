<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectTemplateRequest;
use App\Http\Requests\UpdateTemplateSettingsRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The template store: what an owner can pick from, switching between designs,
 * and customizing whatever the chosen template exposes.
 *
 * Every active template is free and open to every restaurant — what a package
 * grants is limits and features, never a design. A newly onboarded restaurant
 * has no template and must pick one before the rest of the dashboard unlocks.
 */
class TemplateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->collection($this->restaurant($request));
    }

    /**
     * Switch to a template, seeding that template's default settings. Every
     * active design is available, so this never fails on entitlement.
     */
    public function select(SelectTemplateRequest $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);
        $template = $this->activeTemplate($request->validated('template_id'));

        $restaurant->update([
            'template_id' => $template->id,
            'template_settings' => $template->defaultSettings(),
        ]);

        return $this->collection($restaurant);
    }

    /**
     * Save the owner's customizations for their active template. What may be
     * changed is declared by that template's own schema.
     */
    public function updateSettings(UpdateTemplateSettingsRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $template = $restaurant->template;

        abort_if($template === null, 403, __('Choose a template first.'));

        // resolveSettings() drops anything the schema doesn't declare and fills
        // the gaps with the template's defaults.
        $settings = $template->resolveSettings((array) $request->validated('settings'));

        $restaurant->update(['template_settings' => $settings]);

        return response()->json(['data' => ['settings' => $settings]]);
    }

    private function collection(Restaurant $restaurant): AnonymousResourceCollection
    {
        return TemplateResource::collection(Template::query()->active()->get())->additional([
            'meta' => [
                'current' => $restaurant->template_id,
                'settings' => $restaurant->template_settings ?? [],
            ],
        ]);
    }

    private function activeTemplate(int $id): Template
    {
        return Template::query()->active()->findOrFail($id);
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        return $restaurant;
    }
}
