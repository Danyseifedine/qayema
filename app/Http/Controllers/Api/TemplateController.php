<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientCoins;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelectTemplateRequest;
use App\Http\Requests\UpdateTemplateSettingsRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Global\TemplateStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The template store: what an owner can pick from, unlocking a paid one with
 * coins, switching to one they own, and customizing whatever that template
 * exposes.
 *
 * A newly onboarded restaurant has no template and must pick one before the
 * rest of the dashboard unlocks.
 */
class TemplateController extends Controller
{
    public function __construct(private readonly TemplateStore $store) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->collection($this->restaurant($request));
    }

    /**
     * Buy a paid template with coins. Kept separate from `select` so that
     * choosing a template can never spend money by accident — the SPA asks
     * first, then calls this.
     */
    public function unlock(SelectTemplateRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);
        $template = $this->activeTemplate($request->validated('template_id'));

        abort_if($template->isFree(), 422, __('This template is already free to use.'));

        if ($restaurant->owns($template)) {
            return response()->json([
                'message' => __('You already own this template.'),
            ], 422);
        }

        try {
            $this->store->unlock($restaurant, $template);
        } catch (InsufficientCoins $exception) {
            // 402 so the SPA can tell "you can't afford it" apart from a
            // validation problem and send the owner straight to the coin packs.
            return response()->json([
                'message' => $exception->getMessage(),
                'balance' => $exception->balance,
                'needed' => $exception->needed,
                'shortfall' => $exception->shortfall(),
            ], 402);
        }

        return $this->collection($restaurant->refresh());
    }

    /**
     * Switch to a template. Free templates are open to everyone; paid ones have
     * to be unlocked first.
     */
    public function select(SelectTemplateRequest $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);
        $template = $this->activeTemplate($request->validated('template_id'));

        abort_unless($restaurant->owns($template), 403, __('This template has not been unlocked yet.'));

        $this->store->select($restaurant, $template);

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
        $restaurant->load('templatePurchases');

        return TemplateResource::collection(Template::query()->active()->get())->additional([
            'meta' => [
                'current' => $restaurant->template_id,
                'settings' => $restaurant->template_settings ?? [],
                'balance' => $restaurant->user->wallet()->balance(),
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
