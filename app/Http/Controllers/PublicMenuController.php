<?php

namespace App\Http\Controllers;

use App\Enums\Ask;
use App\Enums\OrderChannel;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Menu\MapPoint;
use App\Services\Menu\MenuDishOptions;
use App\Services\Menu\MenuLanguages;
use App\Services\Menu\MenuSeo;
use App\Services\Menu\MenuToken;
use App\Services\Menu\OpeningHours;
use App\Services\Offers\LiveOffers;
use App\Services\Orders\WhatsAppLink;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public menu at /{slug}: the page a guest lands on after scanning the QR
 * code. Each template renders from its own Blade view
 * (resources/views/menu/templates/{slug}.blade.php) with the owner's saved
 * settings resolved over the template's defaults.
 *
 * The owner can also open their own menu with `?preview={template_id}` to see
 * any active design on their real content before buying it. Previews are only
 * honoured for the signed-in owner, and are never counted as visits.
 *
 * The visit itself is counted by the page (public/js/menu-track.js), so a
 * page served from a cache counts too.
 */
class PublicMenuController extends Controller
{
    public function __construct(
        private readonly MenuSeo $seo,
        private readonly LiveOffers $offers,
    ) {}

    public function show(Request $request, Restaurant $restaurant): View
    {
        $preview = $this->previewTemplate($request, $restaurant);

        // A restaurant that is switched off, or whose package ended past its
        // grace days, has no menu to show, but its owner may still preview it.
        abort_unless($restaurant->menuOnline() || $preview !== null, 404);

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
            'categories' => fn ($query) => $query->orderBy('id'),
            'socialLinks',
            'media',
        ]);
        $dishes = $this->dishes($restaurant);

        // Each design keeps the colours its owner chose for it, so a preview
        // of another design shows those, or its defaults if there are none.
        $settings = $restaurant->designSettings($template);

        $locale = $this->locale($request, $restaurant);

        // The offers the page carries; the phone shows the ones live now
        // (menu-offers.js). The dishes on a live discount are drawn at their
        // offer price already, from the offers in hand.
        $previewOffer = $this->previewOffer($request, $restaurant);
        $offers = $this->offers->forMenu($restaurant, $previewOffer);
        // An offer's deal dish is on the page only with its offer (not
        // paused, not ended, kept on by the package): not drawn at all
        // otherwise, so it is in no search, count or choice either.
        $carried = $offers->modelKeys();
        // A build's dish sits above the search, in no category's list.
        $built = $this->file($restaurant, $dishes->reject(fn (Dish $dish): bool => $dish->isDeal() && ! in_array($dish->offer_id, $carried, true)));
        // Each dish's variants and add-ons, for its sheet and the cart.
        $dishOptions = MenuDishOptions::for($restaurant, $restaurant->categories->flatMap->dishes->concat($built), $locale);
        // Each offer keeps only its dishes guests can see (a hidden one is
        // not on the page, a build's dish above the search is), with their
        // prices: its badge and "See the dishes" show only while one is left.
        $onMenu = $restaurant->categories->flatMap->dishes->concat($built)->keyBy('id');
        $offers->each(fn (Offer $offer) => $offer->setRelation(
            'dishes',
            $offer->dishes->map(fn (Dish $dish): ?Dish => $onMenu->get($dish->id))->filter()->values(),
        ));
        $timezone = $restaurant->localTimezone();
        $now = CarbonImmutable::now();
        // What each dish costs at least before add-ons (its price, cheapest
        // variants and cheapest builds, the "from" price of a dish with
        // choices): the part a discount comes off on its card.
        $variantsOn = $restaurant->showsVariants();
        $references = $onMenu->map(fn (Dish $dish): ?string => $dishOptions[$dish->id]['lowest'] ?? LiveOffers::referencePrice($dish, $variantsOn))->filter()->all();
        $deals = $offers->isEmpty() ? [] : $this->offers->bestAmong(
            $offers,
            $references,
            $now,
            $timezone,
            // An amount off is in the main currency: a dish in the second one takes it at the rate.
            $onMenu->map(fn (Dish $dish): ?string => $restaurant->rateOf($dish))->filter()->all(),
        );
        // "The second at 50% off": a badge on each of its dishes (a straight
        // discount on a dish wins).
        $pairs = $offers->isEmpty() ? [] : array_diff_key($this->offers->pairsAmong($offers, $now, $timezone), $deals);

        // The menu route carries no locale middleware, so without this every
        // __() on the page resolves against APP_LOCALE and an Arabic menu
        // prints English.
        app()->setLocale($locale);

        // Live as the page is drawn (its deal works), and shown (on the menu,
        // which one shown outside its hours is all day); the phone checks again.
        $live = $offers->filter(fn (Offer $offer): bool => $offer->id === $previewOffer || $offer->isLiveAt($now, $timezone))->pluck('id')->all();
        $shown = $offers->filter(fn (Offer $offer): bool => $offer->id === $previewOffer || $offer->isShownAt($now, $timezone))->pluck('id')->all();

        // A preview is a dress rehearsal and never takes a real order.
        $channel = $preview === null ? $restaurant->orderChannel() : null;
        $dineIn = $preview === null && $restaurant->takesDineIn();

        // Opened from a table's QR code: the cart can order to it, and on
        // WhatsApp the message says which table.
        $table = $channel === null && ! $dineIn ? null : $this->table($request, $restaurant);

        // At a table that takes orders the cart orders the table's way (the
        // menu or WhatsApp), whichever way delivery and pickup go.
        $atTable = $table !== null && $dineIn;
        $cartChannel = $atTable ? $restaurant->dineInChannel() : $channel;
        // What a WhatsApp cart asks the guest for, the table's set at a table.
        $asks = $cartChannel === OrderChannel::WhatsApp ? $restaurant->whatsappAsks()[$atTable ? 'table' : 'away'] : null;

        return view($view, [
            'restaurant' => $restaurant,
            'template' => $template,
            'settings' => $settings,
            'locale' => $locale,
            'is_preview' => $preview !== null,
            'hours' => OpeningHours::for($restaurant),
            'locales' => $this->localeLinks($restaurant, $preview, $table),
            'menu_url' => route('public.menu', $restaurant->slug),
            'map_embed_url' => MapPoint::embedFor($restaurant),
            // Chatting to the restaurant needs the same E.164 number an order
            // hand-off uses, so it comes from the same place.
            'whatsapp_url' => ($number = WhatsAppLink::internationalNumber($restaurant))
                ? 'https://wa.me/'.$number
                : null,
            // Ordering is a package feature; on WhatsApp it also needs a
            // number to send to.
            'can_order' => match ($cartChannel) {
                OrderChannel::Menu => true,
                OrderChannel::WhatsApp => $number !== null,
                null => false,
            },
            'order_channel' => $cartChannel,
            // Delivery and pickup, while those are taken in the menu, or asked
            // on WhatsApp along with the address.
            'order_types' => $channel === OrderChannel::Menu || ($asks['address'] ?? Ask::Off->value) !== Ask::Off->value ? $restaurant->orderTypes() : [],
            'whatsapp_asks' => $asks,
            // Ordering to a table in the menu: a table remembered from an
            // earlier scan is offered only then.
            'dine_in' => $dineIn && $restaurant->dineInChannel() === OrderChannel::Menu,
            'table' => $table,
            'dish_options' => $dishOptions,
            // The build dishes, drawn with their icons above the search.
            'built_dishes' => $built,
            'seo' => $this->seo->for($restaurant, $locale, $preview !== null),
            // What the cart and the tracking send in place of a CSRF token.
            'menu_token' => MenuToken::issue(),
            'offers' => $offers,
            'offers_live' => $live,
            'offers_shown' => $shown,
            // The deal dishes drawn hidden: their offer is not live as the
            // page is drawn (menu-offers.js shows them in time).
            'hidden_deals' => $onMenu->filter(fn (Dish $dish): bool => $dish->isDeal() && ! in_array($dish->offer_id, $live, true))->keys()->all(),
            'offer_deals' => $deals,
            'offer_pairs' => $pairs,
            'offer_references' => $references,
            'offer_preview' => $previewOffer,
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
    private function localeLinks(Restaurant $restaurant, ?Template $preview, ?DiningTable $table): array
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

            // Nor may it lose the table the guest is sitting at.
            if ($table !== null) {
                $query['table'] = $table->code;
            }

            $links[$code] = [
                'name' => $meta['name'],
                'flag' => $meta['flag'],
                'url' => $base.'?'.http_build_query($query),
            ];
        }

        return $links;
    }

    /** The table whose QR code opened the menu, when its code is still one of this restaurant's. */
    private function table(Request $request, Restaurant $restaurant): ?DiningTable
    {
        $code = $request->query('table');

        return is_string($code) && $code !== '' && strlen($code) <= 16
            ? $restaurant->diningTables()->where('code', $code)->first()
            : null;
    }

    /**
     * `?offer=12`: the owner looks at one of their offers on the menu as
     * guests will, even paused or not started yet, and its pop-up or ad
     * shows at once. Ignored for anyone else.
     */
    private function previewOffer(Request $request, Restaurant $restaurant): ?int
    {
        $id = $request->query('offer');

        if ($id === null || ! ctype_digit((string) $id)) {
            return null;
        }

        $user = $request->user();

        return $user !== null && (int) $user->restaurant?->id === $restaurant->id ? (int) $id : null;
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

        return Template::query()->active()->availableTo($restaurant)->find((int) $id);
    }

    /**
     * The dishes guests can order, read in one go with what they show (in
     * the dishes' order): those filed under a category, and while the
     * restaurant has Build your own, the dishes guests build in no category.
     *
     * @return Collection<int, Dish>
     */
    private function dishes(Restaurant $restaurant): Collection
    {
        $builds = $restaurant->showsBuilds();

        return $restaurant->dishes()
            ->where('is_available', true)
            ->where(fn ($query) => $query->whereNotNull('category_id')->when($builds, fn ($query) => $query->orWhereHas('builds')))
            ->orderBy('id')
            ->with(['media', ...MenuDishOptions::relations($restaurant)])
            ->get();
    }

    /**
     * Puts each category's dishes in its list, and returns the dishes guests
     * build (Build your own), while the restaurant has it: those in no
     * category, and any still filed under one from before, kept out of its
     * list. Without the feature a build's dish stays where it is (one in no
     * category is not drawn).
     *
     * @param  Collection<int, Dish>  $dishes  from dishes()
     * @return Collection<int, Dish>
     */
    private function file(Restaurant $restaurant, Collection $dishes): Collection
    {
        $built = $restaurant->showsBuilds()
            ? $dishes->filter(fn (Dish $dish): bool => $dish->builds->isNotEmpty())->values()
            : new Collection;
        $filed = $dishes->diff($built)->groupBy('category_id');

        $restaurant->categories->each(fn (Category $category) => $category->setRelation(
            'dishes',
            new Collection($filed->get($category->id)?->values()->all() ?? []),
        ));

        return $built;
    }
}
