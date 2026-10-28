# Ordering

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

A guest builds a cart on the public menu and places an order. The restaurant
takes orders **one way at a time** (`restaurants.order_mode`, chosen on the
Features page, `PUT /api/features/ordering`), and every order records the way
it came in (`orders.channel`, `App\Enums\OrderChannel`):

- **WhatsApp** (`ordering` flag, Premium and Custom): the order is stored and
  the guest is sent to WhatsApp with it written out (`WhatsAppLink`), kept
  plain for the staff who read it: WhatsApp's bold, no emoji, no order
  number, a blank line between the parts (the table or delivery/pickup
  first, what the guest gave, each dish, the total with the rate when a
  line was in the second currency, the note). Each dish is `*2 Burger*
  $16` (no "×" anywhere) with a `• ` line per choice, only what was picked ("Large", "Extra
  cheese"); a built dish is named by what was built, its size first (`*1
  Large: Half Margherita, Half Pepperoni*`; the same choice added up, so two
  quarters are "Half Margherita" and one choice all over is just its name;
  scoops each named, "Vanilla, Vanilla, Chocolate"). It is written in the **guest's
  language**, the one they had the menu in (labels, the restaurant's name,
  line names and offer titles: `OrderPlacer` names every order's lines in
  it). Before WhatsApp opens the cart can ask the
  guest's name, phone and (delivery and pickup only) a delivery address, each
  off, optional or required as the owner set it (`restaurants.whatsapp_fields`,
  `Restaurant::whatsappAsks()`, `App\Enums\Ask`, `PUT /api/features/whatsapp-fields`;
  table orders have their own name and phone pair). Asking the address also
  asks delivery or pickup (`order_types`), the address only for a delivery;
  what was given heads the message and is kept on the order, like the rest.
  All off by default. We never learn whether it was sent or served, so
  these orders are **not** listed on the Orders page and analytics call them
  "Sent to WhatsApp". It needs a usable number: without one the menu has no
  cart.
- **In the menu** (the `menu_ordering` flag, Premium and Custom):
  the guest gives their name, a phone number (country code from `config/countries.php`,
  stored international through `App\Support\PhoneNumber`), Delivery or
  Pickup (`restaurants.order_types`, null = both; `App\Enums\Fulfilment`),
  a typed address for a delivery, optionally their location ("Use my
  location": `latitude`/`longitude`, shown to the owner as a Google Maps
  link), and a note. The menu shows a toast with the order number; the order
  waits on the Orders page. Refused while the restaurant is closed by its
  opening hours (no hours = always open). A page may ask for the location
  only on the menu itself (`SecurityHeaders`: `geolocation=(self)` there,
  `()` everywhere else).

Each way is a package switch of its own, so an admin may sell either alone
(`Restaurant::orderWays()`). `Restaurant::orderChannel()` is the rule: null
without either way or with orders switched off; with one way, that way
whatever `order_mode` says (a downgrade never leaves a cart going nowhere);
with both, the owner's `order_mode` (`orderMode()`, what `/api/user` sends as
`ordering.mode`). Saving a way the package does not have is a 422 on `mode`
(`UpdateOrderingRequest`).

**Orders at the table** (dine-in, a table's QR code, the `dine_in` switch on
the Features page) have their own way in, apart from delivery and pickup,
again one package switch per way (`Restaurant::dineInWays()`): `dine_in`
(in the menu, the Table orders page) and `dine_in_whatsapp`.
`restaurants.dine_in_mode` (`menu` | `whatsapp`, `PUT /api/features/dine-in`,
`UpdateDineInRequest`: a way the package has, and WhatsApp needs a number,
422 otherwise) picks between them when the package has both
(`dineInMode()`, sent as `ordering.dine_in`). `Restaurant::dineInChannel()`
is the rule: null when switched off or without a way; WhatsApp when it is
the only way, or chosen, and the restaurant has a number WhatsApp can reach;
the menu otherwise when the package has it (a number removed later sends
them back to the Table orders page); null when WhatsApp is the only way and
there is no number (`takesDineIn()` is then false too). Tables (the owner's
Tables page, `DiningTableController`) serve either way. At a table the menu
builds its cart for that channel (`PublicMenuController`); on WhatsApp the
cart says "This order is for Table 5." and the message names the table, and
the order is not listed on Table orders. Without dine-in, a table's code on a
WhatsApp order only labels it. `/api/user` sends `ordering.dine_in` (the
choice) and `ordering.whatsapp_number`.

- `App\Services\Orders\OrderPlacer` is the only way an order is created. Every
  dish is re-read scoped to the restaurant and every price comes from the
  database; a price in the request body is ignored. It takes an
  `OrderDetails` (channel, note, fulfilment, name, phone, address, location,
  `client_token`): a token this restaurant already has returns that order, so
  a double tap or a retry never orders twice.
- Order lines carry **their own** `name` and `unit_price`. A dish renamed,
  repriced or deleted later must not rewrite what was ordered, which is why
  `order_items.dish_id` is `nullOnDelete`.
- `POST /{slug}/order` is public, on the `web` group, limited
  to 10/min per IP with **`autoBan: false`**: a dining room is one IP. Like
  every guest route of the menu (the group in routes/web.php) it stores no
  session for a guest and checks no CSRF token; the menu's own POST/PUT
  requests carry the page's `X-Menu-Token` instead (`MenuToken`, signed with
  the app key and the time, good for a day; `VerifyMenuToken` answers a
  missing or stale one with 419 `menu_expired`, which the cart shows in
  the menu's language, as it does a 429 and a bare 404, since those answer
  before the guest's language is known; no connection, or an answer that
  is not ours (a proxy's error page), says "That did not send"). A
  test sends it with `Tests\Support\SendsMenuToken` (`freshMenuToken()`
  after moving the clock). The page
  sends `mode`, the way it was built for; a mismatch (the owner switched while
  the guest had the menu open) is a 409 asking to refresh. A hidden `website`
  box is a spam trap. `PlaceOrderRequest` sets the guest's menu language
  before validating, so field errors come back in it.
- "Use my current location" also fills the address box with the street, area
  and town (`GET /{slug}/address`, `throttle:geocode` 10/min,
  `App\Services\Orders\ReverseGeocoder`): our server asks OpenStreetMap's
  Nominatim, with the app's name as User-Agent, and keeps each spot (~11 m)
  for 30 days. It never overwrites what the guest typed. The e2e environment
  fakes Nominatim (`E2eServiceProvider`).
- The country code is a searchable list of our own (names in the menu's
  language from PHP's `intl`, English too; digits; letters), not a select.
  The cart's boxes use 14px text; an iPhone would zoom on focus below 16px,
  so the menu's head adds `maximum-scale=1` on iPhones and iPads only (iOS
  still allows pinch zoom; Android would not, so it never gets it).
- The cart's details form (`public/js/menu-cart.js`) is built once per panel
  and never redrawn, so typing survives cart changes. What the guest types is
  kept in their browser (`qayema-guest-{slug}`) until the order goes (to
  WhatsApp too), then the form starts over, empty. A table order in the menu
  with no choice to make (dine-in the only kind) says which table it goes to.
- **Tracking** (the `order_tracking` flag, Premium and Custom,
  `Restaurant::tracksOrders()`). Without it an order placed in the menu is
  still sent and reaches the dashboard live, but the guest is only told its
  number: the order answers `tracking_url: null`, the menu has no tracking
  sheet (so nothing is kept to follow, change or add to; `menu-cart.js`
  hears no `qayema:order-state`), the tracking, cart and change endpoints
  answer 404 (`PublicOrderTrackingController`, `PlaceOrderRequest` before
  validating a change), and the guest's `order.{token}` channel is never sent
  to (`OrderNews`). The token is still made, for the owner's live events.
  With it: an order placed in the menu gets a 40-character
  `tracking_token`. The guest follows it in a sheet over the menu
  (`#track-sheet`, `public/js/menu-order.js`), never on a page of its own:
  `GET /{slug}/order/{token}` answers JSON (status, `closed`, `closed_at` and
  `html`, drawn by `menu/partials/order-tracking.blade.php`); opened as a page
  it redirects to the menu with `?track={token}`, which opens the sheet.
  Steps: sent → accepted → on its way (delivery) or ready for pickup →
  delivered or picked up, or cancelled, with the times (`accepted_at`,
  `ready_at`, `closed_at`, set by `Order::moveTo()`); a skipped step shows as
  passed with no time. Choices show as the guest picked them ("Large, + Extra
  cheese", `OrderItem::picks()`); the owner keeps "Size: Large", WhatsApp
  only "Large".
  The short reference never opens it. The "Order sent" toast and a bar at the
  top of the menu (`qayema-order-{slug}` in the guest's browser, live status)
  open the sheet; the bar goes once the order is done, an hour after a
  cancelled one, and after 12 hours anyway. `OrderStatus` is placed,
  accepted, ready, done, cancelled: the dashboard's one button moves an
  in-menu order on (Accept, then On its way or Ready, then Done), and Accept
  is what tells the guest a person saw it. No SMS and no WhatsApp messages to
  the guest.
- **Live, through Pusher** (`config/broadcasting.php`, `pusher/pusher-php-server`;
  keys `PUSHER_APP_*` in `.env`, never committed). `App\Services\Orders\OrderNews`
  sends after the response (`defer`) and never throws (`rescue`): a guest's
  new or changed order → `OrdersChanged` on `private-orders.{restaurant id}`
  (the pulse: open, latest, changed; only that owner may join,
  `routes/channels.php`, signed at `POST /api/broadcasting/auth` with the
  Sanctum session); the owner moving it → also `OrderMoved` on
  `order.{tracking token}` (public, the token is the secret). Both are
  `ShouldBroadcastNow` (sent at once, never left waiting in the queue). The menu loads
  `public/js/pusher.min.js` (pusher-js 8.6, vendored) only when there is an
  order to follow. Without keys (local, tests, e2e: `BROADCAST_CONNECTION=null`)
  nothing is sent, and the sheet, the bar and the dashboard ask once a minute;
  they do the same whenever Pusher cannot be heard.
- **One order at a time** (per browser; guests have no accounts). While the
  guest's order is going (menu-order.js tells the cart, `qayema:order-state`),
  the cart **adds to it** ("Adding to your order #…", "Add to order": the
  order's lines come back from `/cart`, the new ones are added, and the whole
  goes as a change, with no details asked again). Once the restaurant has
  accepted it, the cart waits ("One order at a time") until it is done or
  cancelled.
- **Changing an order.** Only while it waits to be accepted
  (`OrderStatus::isOpenToGuest()`, placed), the guest may change an order
  placed in the menu: the tracking sheet's "Change my order"
  hands it to `menu-cart.js`, which loads
  `GET /{slug}/order/{token}/cart` (lines with their choice ids, kept in the
  `order_items.options` snapshot as `option_id` / `addon_id`, and the details,
  the phone split back by `PhoneNumber::split()`) into the cart without
  touching the guest's own cart or form in storage, and "Update order" sends
  `PUT /{slug}/order/{token}` (`PlaceOrderRequest`, priced again by
  `OrderPlacer::change()` on a locked row; a shared location stays unless a
  new one comes, and a new address drops the old spot). Once accepted it answers 409 (`OrderLocked`).
  A change carries `version` (the `guest_updates` its page read from `/cart`;
  older is a 409, `OrderChanged`, so two phones never undo each other) and a
  `client_token` of its own, kept in `orders.change_token`: the same change
  sent twice (an answer lost on mobile data) is made once. Dishes marked
  unavailable since are left out and named (`unavailable` in the answer,
  `OrderPlacer::unavailable()`), and so is an offer's deal dish whose offer
  is no longer live (`LiveOffers::liveAmong()`, on a new order too; see
  offers.md, Deal dish). Each change sets `guest_updated_at` and counts
  `guest_updates`; the dashboard's pulse carries `changed` and chimes, and the
  card says "Changed by the guest at 13:05" (amber while it waits to be
  accepted). The
  sheet's "Change my order" hands the token to the cart (`qayema:edit`).
- The owner reads in-menu orders at `GET /api/orders` (only `channel = menu`;
  only `status` is writable). `PATCH /api/orders/{order}` works on the locked
  row and only moves an order on (`OrderStatus::canMoveTo()`: forward, a step
  may be skipped, or cancelled while open; the same status again is a no-op);
  anything else is a 409 `order_moved_on`. Taking on a new order sends the
  `guest_updates` the card showed; a change made since is a 409
  `order_changed` and the dashboard polls `GET /api/orders/pulse`
  (`{open, latest, changed}`) only while Pusher cannot be heard; the same
  pulse arrives live otherwise. Either way: a sound, a toast, the sidebar
  count and the tab title.
- A guest's name, phone, address and location stay on the order for as long
  as it exists (until the owner deletes it, or the restaurant goes); the
  Privacy Policy says so.
- Analytics (`MenuStats`) count only the current channel's orders
  (`order_channel` in the summary, `channel` in the funnel), and
  `orders_done` for orders placed in the menu.
