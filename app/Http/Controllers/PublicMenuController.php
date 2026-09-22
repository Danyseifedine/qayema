<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Global\MenuVisitRecorder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public menu at /{slug} — the page a guest lands on after scanning the QR
 * code. Each template renders from its own Blade view
 * (resources/views/menu/templates/{slug}.blade.php) with the owner's saved
 * settings resolved over the template's defaults.
 *
 * The owner can also open their own menu with `?preview={template_id}` to see
 * any active design on their real content before buying it. Previews are only
 * honoured for the signed-in owner, and are never counted as visits.
 */
class PublicMenuController extends Controller
{
    public function __construct(private readonly MenuVisitRecorder $visits) {}

    public function show(Request $request, Restaurant $restaurant): View
    {
        $preview = $this->previewTemplate($request, $restaurant);

        // A restaurant that is switched off has no menu to show — but its
        // owner may still preview it while setting up.
        abort_unless($restaurant->is_active || $preview !== null, 404);

        $template = $preview ?? $restaurant->template;

        // No template chosen yet (and not previewing one): nothing to render.
        abort_unless($template !== null && $template->is_active, 404);

        $view = 'menu.templates.'.$template->slug;

        // A template row whose Blade view was never added would otherwise be a
        // 500 for every guest; fall back to the default layout instead.
        if (! view()->exists($view)) {
            $view = 'menu.templates.classic';
        }

        if ($preview === null) {
            $this->visits->record($restaurant, $request);
        }

        $restaurant->load([
            'categories' => fn ($query) => $query->orderBy('display_order')->orderBy('id'),
            'categories.dishes' => fn ($query) => $query->where('is_available', true)
                ->orderBy('display_order')
                ->orderBy('id'),
            'categories.dishes.media',
            'socialLinks',
            'media',
        ]);

        // A preview shows the candidate template with its own defaults — the
        // owner has no saved settings for a design they haven't chosen yet.
        $settings = $preview === null
            ? $template->resolveSettings((array) $restaurant->template_settings)
            : $template->defaultSettings();

        return view($view, [
            'restaurant' => $restaurant,
            'template' => $template,
            'settings' => $settings,
            'locale' => $restaurant->default_locale ?: 'ar',
            'is_preview' => $preview !== null,
        ]);
    }

    /**
     * The template to preview, when the request is the owner asking for one.
     * Anyone else's `?preview=` is silently ignored so it can't be used to
     * probe templates or bypass the "no template yet" 404.
     */
    private function previewTemplate(Request $request, Restaurant $restaurant): ?Template
    {
        $id = $request->query('preview');

        if ($id === null || ! ctype_digit((string) $id)) {
            return null;
        }

        $user = $request->user();

        if ($user === null || (int) $user->restaurant?->id !== $restaurant->id) {
            return null;
        }

        return Template::query()->active()->find((int) $id);
    }
}
