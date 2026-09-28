<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Analytics\MenuVisitRecorder;
use App\Services\Menu\MapPoint;
use App\Services\Menu\MenuLanguages;
use App\Services\Menu\OpeningHours;
use App\Services\Orders\WhatsAppLink;
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

        $template = $preview ?? $restaurant->menuTemplate();

        // No template chosen yet (and not previewing one): nothing to render.
        abort_unless($template !== null && $template->is_active, 404);

        $view = 'menu.templates.'.$template->slug;

        // A template row whose Blade view was never added would otherwise be a
        // 500 for every guest; fall back to the default layout instead.
        if (! view()->exists($view)) {
            $view = 'menu.templates.classic';
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

        // Each design keeps the colours its owner chose for it, so a preview
        // of another design shows those, or its defaults if there are none.
        $settings = $restaurant->designSettings($template);

        $locale = $this->locale($request, $restaurant);

        // A guest switching language navigates from this menu back to it.
        // That is one visit, not two, so a self-referred load is not recorded.
        if ($preview === null && ! $this->cameFromThisMenu($request, $restaurant)) {
            $this->visits->record($restaurant, $request, $locale);
        }

        // The menu route carries no locale middleware, so without this every
        // __() on the page resolves against APP_LOCALE and an Arabic menu
        // prints English.
        app()->setLocale($locale);

        return view($view, [
            'restaurant' => $restaurant,
            'template' => $template,
            'settings' => $settings,
            'locale' => $locale,
            'is_preview' => $preview !== null,
            'hours' => OpeningHours::for($restaurant),
            'locales' => $this->localeLinks($restaurant, $preview),
            'menu_url' => route('public.menu', $restaurant->slug),
            'map_embed_url' => MapPoint::embedFor($restaurant),
            // Chatting to the restaurant needs the same E.164 number an order
            // hand-off uses, so it comes from the same place.
            'whatsapp_url' => ($number = WhatsAppLink::internationalNumber($restaurant))
                ? 'https://wa.me/'.$number
                : null,
            // Ordering is a package feature, and a preview is a dress
            // rehearsal — neither should take a real order.
            'can_order' => ! $preview && $restaurant->takesOrders(),
        ]);
    }

    /**
     * The language the page renders in: whichever of the menu's own languages
     * the guest asked for with ?lang=, and the one the owner chose to open in
     * otherwise. It is a query parameter rather than a session so each version
     * has its own shareable, indexable URL.
     */
    private function locale(Request $request, Restaurant $restaurant): string
    {
        $asked = (string) $request->query('lang');

        return in_array($asked, $restaurant->menuLanguages(), true)
            ? $asked
            : MenuLanguages::default($restaurant);
    }

    /**
     * Every language this menu can be read in, as `code => [name, flag, url]`,
     * for the switcher and the hreflang tags.
     *
     * @return array<string, array{name: string, flag: string, url: string}>
     */
    private function localeLinks(Restaurant $restaurant, ?Template $preview): array
    {
        $base = route('public.menu', $restaurant->slug);
        $links = [];

        foreach ($restaurant->menuLanguages() as $code) {
            $meta = MenuLanguages::catalogue()[$code];

            $query = ['lang' => $code];

            // A preview is a dress rehearsal; switching language inside one
            // must not drop back to the live template.
            if ($preview !== null) {
                $query['preview'] = $preview->id;
            }

            $links[$code] = [
                'name' => $meta['name'],
                'flag' => $meta['flag'],
                'url' => $base.'?'.http_build_query($query),
            ];
        }

        return $links;
    }

    /** Whether this request is a navigation from this same menu page. */
    private function cameFromThisMenu(Request $request, Restaurant $restaurant): bool
    {
        $referer = (string) $request->headers->get('referer');

        if ($referer === '') {
            return false;
        }

        return str_starts_with(
            rtrim(strtok($referer, '?'), '/'),
            rtrim(route('public.menu', $restaurant->slug), '/')
        );
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
