# Offers

An owner's promotions on the guest menu, the `offers` flag (Premium and
Custom). Model `App\Models\Offer` (table `offers`, dishes through
`dish_offer`); the owner edits them on the dashboard's Offers page, the
admin sees them under Menu content → Offers and on each restaurant's page.

## What an offer is

- **Words and picture:** `title` (required in the menu's main language,
  80) and `description` (300) in the menu's languages, one picture
  (collection `image`, uploaded with the `offer` preset: up to 2000px and
  500 KB, so words drawn into it stay sharp; conversions `thumb` 240,
  `wide` 960 and `large` 1800; its `width` and `height` kept as custom
  properties, as every upload's, `MediaService::store()`). `show_text` off makes it a **picture
  alone** (its words drawn into it; `Offer::isPictureOnly()`): the title
  is optional (kept as the picture's alt text), a picture is required
  (`StoreOfferRequest`; `UpdateOfferRequest` counts the one it keeps), and
  the picture fills the card in each place's shape, the card itself the
  tap (`.offer-picture-only`, `.offer-cover`).
- **Where it shows** (`placements`, `App\Enums\OfferPlacement`, one or
  more of the three; there is no Offers section any more, migration
  `drop_the_offers_section`):
  - `banner`: a card in the carousel under the cover, one at a time with
    a dot per live card (a "3 / 12" count past 8); it slides on every 5
    seconds while on screen, waits 8 seconds after a touch, and never
    moves for a phone asking for less motion (`menu-offers.js`).
  - `popup`: a `<dialog>` over the menu when it opens.
  - `ad`: a card above the dock that cannot be skipped: its `ad_seconds`
    (3 to 30) count down in a small ring, then a small X takes their place
    and the ad stays until the guest closes it (or taps it). The countdown
    does not wait while touched.
  - A guest gets one offer over the menu a visit: the first live one in
    the owner's order. Its pop-up shows after `delay_seconds` (0 to 10);
    an offer that is also an ad shows the ad `ad_delay_seconds` (0 to 30,
    default 2) after the pop-up is closed, waiting for any other dialog to
    close too. An ad alone waits `ad_delay_seconds` from the menu
    opening.
- **Shape** (`Offer::shapeIn()`, `.offer-shape-*`): a banner is
  `banner_shape` rectangle (2:1) or square, a pop-up `popup_shape`
  square or tall (3:4, never taller than the screen), an ad always a
  rectangle; the picture fills the shape (cropped, from the `wide`
  version), the words sit over its darkened foot. The carousel takes
  the height of the card shown.
- **Showing the picture at its best** (`offer-card.blade.php`): the
  phone picks `wide` or `large` for its screen (`srcset`, `sizes` per
  place), the first banner and the overlays load at once, and the blurred
  `thumb` under it shows while it loads. A picture further from its shape's
  proportions than `Offer::CROPS_UP_TO` (1.35x; a picture alone
  `PICTURE_ALONE_CROPS_UP_TO`, 1.05x, so its words are never cut) shows
  whole on that blurred copy (`.offer-fit-whole`,
  `Offer::showsPictureWhole()`) instead of cropped. An upload from before
  sizes were kept carries `data-fit="auto"` and `menu-offers.js` judges it
  once loaded. **Twins:** `fitPicture()` in `menu-offers.js` and the
  dashboard's `picture-fit.ts` (its previews and step 2's notes, plus a
  word for a picture under 1000px on its longer side).
- **Pop-up and ad only:** `show_again` (`every_visit`: each
  time the menu is opened or refreshed, but not when its own language
  switch reloads it; `daily` or `once`, remembered in `localStorage`, key
  `qayema-offer-seen-{slug}`), one setting for the pop-up and its ad
  together.
- **A tap** (`action`): nothing, `dishes` (the menu shows only the
  offer's dishes under an "Offers" tab, added beside "All" in the
  category bar, `menu-nav.js` `QAYEMA_NAV.showOffer()`; "All" or any
  category takes the tab away) or `link`
  (`action_url`, https only, a new tab).
- **Discount** (`discount_type` none, percent 1 to 90, or amount; and
  `discount_value`): off each linked dish's own price while live.
  Variants and add-ons keep their prices. Two offers on one dish: the
  guest gets the lower price. `App\Enums\OfferDiscount::apply()` works in
  whole cents, half up, never below nothing. **No dish is ever free:** an
  amount must be below the cheapest chosen dish's own price
  (`StoreOfferRequest`, dishes priced only by choices left out), and one
  that a price cut later makes take the whole price does not apply to
  that dish (`LiveOffers::bestAmong()`, `deals()` in `menu-offers.js`,
  `discountedCents()` in the dashboard).
- `is_active` pauses it; `display_order` is the owner's order (drag,
  `DisplayOrder::apply`). At most `Offer::MAX_PER_RESTAURANT` (30).
- **Parts on the package** (`App\Services\Offers\OfferRules`), so an
  admin builds a package from parts (banners without a pop-up, no
  discounts...): a place is in it while its count is above 0 or
  unlimited; schedules (`offer_schedules`) and discounts
  (`offer_discounts`) are switches of their own. What the package lacks
  is refused on save (`StoreOfferRequest`: "Pop-ups are not on your
  package." on `placements`, even paused; "Schedules are not on your
  package." on `repeat` for any dates, repeat or hours; "Discounts are not
  on your package." on `discount_type`) and set aside on the menu and in
  orders by `OfferRules::applyTo()` (from `LiveOffers::forMenu()` and
  `bestFor()`), never written back: an offer with a schedule
  (`Offer::hasSchedule()`) stays off the menu, one whose every place is
  off the package too (`keepsOff()`), the rest show only in their places
  on the package (`showOnlyIn()`) and without a discount the package lacks
  (`withoutDiscount()`: no badge, no struck price, nothing off the order).
  `OfferResource` sends `status: off` and `kept_off` (`schedules` or
  `places`); a place off the package takes no slot, so switching on an
  offer that also names one is not refused. The dashboard mirrors it from
  `plan.offer_schedules`, `plan.offer_discounts` and `meta.slots` (a limit
  of 0): a locked tile, "Always" only, "None" only, and a warning on the
  card.
- **Slots per place** (`App\Services\Offers\OfferSlots`): the package's
  `offer_banner_limit`, `offer_popup_limit`, `offer_ad_limit` (Premium
  and Custom 3, 1, 1; `OfferPlacement::limit()`), which an admin's grant
  raises or lowers for one restaurant. Only offers switched on and not
  past their end take a slot. Creating, editing or switching one on into
  a full place is a 422 (on `placements`, or `is_active` for the switch),
  checked under the restaurant's row lock. `GET /api/offers` sends
  `meta.slots` (`{banner: {used, limit}, ...}`, null limit unlimited).

## Deal dish

An offer may make a dish of its own ("Family meal: 2 pizzas + drinks,
$25") from the editor, without the owner making the dish first: its
**deal**, `dishes.offer_id` (nullable, `nullOnDelete`, indexed;
`Offer::deal()` hasOne, `Dish::offer()`, `Dish::isDeal()`), one per offer,
written by `App\Services\Offers\DealDish` in the offer's transaction.

- **Request** (`StoreOfferRequest`, the same on update): `deal` left out
  keeps the deal as it is (and its place as the offer's one dish); `null`
  deletes it through the model (its photo goes); an object makes or edits
  it: `{name: {lang: text} (required in the main language, 60),
  ingredients?: {lang: text}|null (300, tidied as a dish's), price (above
  0, the dish price rules), currency?: the main one or the second
  (`dishCurrencyRules()`, null for the main one), category_id (one of the
  owner's), image_key?: a temp upload, use_offer_image?: bool}`. Only the
  menu's active languages are written; hidden ones stay. A new one is
  available and last in its category (and so is one moved to another
  category), and counts against the package's dishes
  (`ensureRoomFor(DishLimit)` under the restaurant's row lock, the plan or
  fair-use message on `deal.name`).
- **Alone:** an offer with a deal (sent, or kept by leaving `deal` out)
  has `discount_type` none and no other dishes, else a 422 on `deal`
  ("An offer with a deal dish has no discount and no other dishes.");
  its own deal sent back in `dish_ids` (as the list shows it) is no other
  dish. Its dishes become exactly the deal, so the tap `dishes` and "See
  the dishes" open it. Another offer's deal is never one of an offer's
  chosen dishes (`dish_ids.*` refused), so no discount reaches a deal.
- **Photo:** its own `image_key` (`MediaService::sync()`); otherwise, with
  `use_offer_image` (on by default for a deal made now, off otherwise), a
  copy of the offer's picture with the dish's own conversions
  (`MediaService::copy()`), taken again on an edit only when the offer's
  picture changed in that save or the dish has none; an offer without a
  picture gives none, and removing the offer's picture removes the copy.
  `use_offer_image` false without an upload leaves the dish's photo as it
  is.
- **API out:** `OfferResource.deal` is null or `{id, name, ingredients
  (null when none is written), price "25.00", currency (the dish's own,
  null for the main one), category_id, image_url, thumb_url,
  is_available}`. `DishResource.offer` is null or `{id, title, status
  (live, scheduled, ended, paused), next_start}`, worked out by
  `OfferResource::standing()` as the offer's own are (without `off`: the
  dish list does not ask the package's rules). `offer_id` is never written
  through the dishes API; a deal is edited (name, price, photo,
  availability...) and deleted there like any dish, and deleting it
  leaves the offer without a deal or a dish (the `dish_offer` row goes
  with it).
- **On the menu** (`PublicMenuController`): a deal whose offer is not
  among `LiveOffers::forMenu()` (paused, ended, the flag off, kept off by
  `OfferRules`) is not drawn at all: not in `#dish-options`, the search or
  a category. One whose offer is there is drawn in its category with
  `data-deal-offer="{offerId}"`, and `hidden data-deal-off` while the offer
  is not live as the page is drawn (`hidden_deals`); a category with
  nothing else gets the same on its section and its tab. `menu-offers.js`
  shows or hides each deal on load and every minute with its banners'
  check (`isLive()`), the categories and tabs with them, then sends
  `qayema:deals` (`menu-nav.js` filters again and leaves a tab that hid)
  and `qayema:prices` (the cart draws again, and `findDish()` no longer
  finds a hidden deal, so its line goes, with a toast: "Taken off your
  cart: Family meal is not available right now."; the cart keeps each
  line's dish name for it, and says the same of any dish that left the
  menu, sold out or removed, on the next visit). Nothing about one guest is on
  the page, so it stays cached at Cloudflare. `MenuSeo` leaves deals out
  entirely, and a build's category choices never offer one
  (`BuildChoices`).
- **Orders** (`OrderPlacer`): a deal is sold only while its offer is live
  on the server's clock, as a discount is judged
  (`LiveOffers::liveAmong()`: `forMenu()`'s rules, then the schedule, one
  query and none without a deal in the cart); otherwise it is set aside
  and named like a sold-out dish (`unavailable`, or "Nothing in your order
  is available any more."), on a new order and on a guest's change alike.
  The owner adding to an order (`evenUnavailable`) may still add it. It is
  charged its own price.
- **Deleting:** the offer's `deleting` hook deletes its deal through the
  model, so the photo leaves storage (the admin's delete says so).
- **Admin:** the dish form says "Made by the offer: ... It shows on the
  menu only while the offer is live.", and the Dishes list has a "Made by
  offer" badge column.
- **E2E:** a scenario offer takes `deal: {name, price, category (a
  scenario category's English name), ingredients?}` and comes back as
  `{id, deal_id}`.
- Tests: `tests/Feature/Api/DealDishTest.php`,
  `tests/Feature/Menu/MenuDealDishTest.php`,
  `tests/Feature/Orders/DealDishOrderTest.php`,
  `tests/Integration/E2e/E2eDealDishTest.php`.

## The schedule

`App\Services\Offers\OfferSchedule`, in `restaurants.timezone`. Live
means switched on AND inside `[starts_at, ends_at)` (UTC; either may be
open) AND today matches `repeat` (`always`, `weekly` with `weekdays`
mon to sun, `monthly` with `month_days` 1 to 31, where a 31st also runs on
the last day of a shorter month) AND now is inside one of `time_windows`
(up to 3 `{from, to}` "HH:MM", null all day). Only the last window may
run past midnight, and it belongs to the day it started on (the rule
of opening hours' shifts). `nextStart()` is "Starts Fri 6:00 PM", and
`problems()` validates a schedule (`StoreOfferRequest::after()`).

The dashboard sends and receives dates as the restaurant's local
"2026-11-01T18:00" (`StoreOfferRequest::LOCAL_FORMAT`); an end date with
no time is the next midnight, shown back as "until" that day.

**Twins, change all three together:** `OfferSchedule` (server, prices),
`public/js/menu-offers.js` (the guest's phone) and the dashboard's
`offer-schedule.ts` (the sentence and status); `OfferDiscount::apply()`
and `discountedCents()` in the dashboard and `discounted()` in
`menu-offers.js`.

## On the menu

The page is cached at Cloudflare for an hour, so it never decides alone
what is live: `PublicMenuController` hands `LiveOffers::forMenu()` (on,
not past their end) to `menu.partials.offers-banner` (in the design)
and `offers-overlay` (through
`menu.partials.scripts`: the dialogs, the ad, the bar, `QAYEMA_OFFERS`
and `menu-offers.js`). Cards live when the page was drawn are shown,
others carry `hidden`; the phone judges again on load and every minute.
A dish on a live discount is drawn at its offer price (`data-price`,
which the cart charges, `data-full-price`, `data-extra` for its
cheapest choice, the old price struck through and a badge), computed
from the offers in hand (`LiveOffers::bestAmong()`, no query). Each
offer keeps only its dishes on the page (a hidden one is not): with none
left, no "See the dishes". The offer's own card (banner, pop-up, ad)
never shows the discount; the dishes it takes off show it. When a price changes on the phone,
`menu-offers.js` sends `qayema:prices` and the cart draws again.

`menus:forget-ended` clears menus whose offer started or ended in the
last hour, so the HTML a crawler or a phone without scripts reads keeps
up; repeats inside the dates are left to the phone. A save, delete,
reorder or picture clears the menu at once (ClearsMenuCache,
`DisplayOrder`, media).

`?offer={id}` (the owner, signed in) shows one offer even paused or
not started, and opens its pop-up or ad at once; it is in
`DropUnknownMenuQuery::KEYS` and never cached.

## Orders

`OrderPlacer` asks `LiveOffers::bestFor()` per cart, on the server's
clock: a dish on a live discount is charged its offer price, and the
line keeps `offer_id`, `offer_title` (in the line's language) and
`full_price` (its unit price before the discount, choices included).
The order card on the dashboard says "Grill week, was $25.00". The
server's price always wins, and the guest is never charged more than
they saw: the cart sends `seen_total`, and a cart that costs more (an
offer ended while it sat there, a price went up, the phone's clock is
wrong) is not placed. `OrderPlacer` throws `PricesChanged`, the guest gets
a 409 with each dish's own price now (`prices`), `menu-offers.js` keeps
those over its own sums (`qayema:server-prices`) and the guest sends
again. A cheaper cart goes through.

## Numbers

`MenuEventType::OfferShown` (once per offer per visit: a card half on
screen, or a pop-up or ad opening) and `OfferTap`, carrying the offer's id
in `value`, checked against the restaurant's offers by
`MenuEventRecorder`. They stay out of the analytics page's guest
actions (`MenuEventType::guestActions()`); the Offers page shows each
offer's last 30 days (`meta.stats` of `GET /api/offers`).

## API

`GET/POST /api/offers`, `PATCH/DELETE /api/offers/{offer}`,
`PATCH /api/offers/{offer}/active`, `POST /api/offers/reorder`
(`OfferController`, `OfferPolicy`, writes under `throttle:mutations`, a
403 "Offers are not on your package." without the flag). The editor
sends an offer whole (`UpdateOfferRequest` is `StoreOfferRequest`).
