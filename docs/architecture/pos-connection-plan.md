# Connect to POS: the full plan

Status: all five phases built and tested; phases 1 to 4 also checked end to end
against the real POS on PostgreSQL (catalog, live pings, sell-out, revoke, and an
order accepted, readied and completed into a sale). Not committed. Written 29 Oct 2026 from a read of both codebases. Two repos take part:

- **Qayema** (`qayema` backend, `qayema-dashboard` SPA): the menu.
- **The POS** (`swipensell`, Laravel 13, Inertia + Vue, Pest, modular monolith
  in `app/Modules/<Module>`; its own rules in its `CLAUDE.md` and
  `docs/ARCHITECTURE_V2_PLAN.md` apply to every change there).

Nothing in Qayema is named after the POS product. Qayema calls it **Connect to
POS** (`pos_connection`, `PosConnection`, `pos_*`), so another POS can sit behind
the same contract later. In the POS it is a module called **Online menu**
(`online_menu`, folder `app/Modules/OnlineMenu`).

## 1. What the owner gets

While a Qayema restaurant is connected to a POS branch:

- **The POS makes the menu.** Categories and dishes are created, edited and
  removed in the POS: names and descriptions in both languages, prices, photos,
  choices (variants and add-ons), the ingredients line and what is sold out.
  They reach the menu within seconds.
- **Qayema keeps how the menu looks and works:** designs, appearance, fonts,
  QR codes, tables, opening hours, languages, the order of categories and
  dishes, category icons.
- **Switched off while connected:** adding, editing and deleting categories and
  dishes in Qayema; **Offers** and **Build your own** (their pages say why, the
  menu shows neither, saved offers and builds are kept and come back on
  disconnect).
- **Orders** (optional): "To your POS" joins "On WhatsApp" and "In your menu"
  under How guests send orders. An order placed in the menu goes to the POS's
  Online orders screen; each step there (accepted, ready, done, cancelled) shows
  on the guest's tracking page. The owner can switch back to WhatsApp any time.

## 2. Decisions

| Question | Decision |
|---|---|
| Accounts | Two: the restaurant's Qayema account and its POS account, linked once with a connection key |
| One connection is | One POS branch (its stock, its online orders) to one Qayema restaurant |
| Who may use it | A Qayema feature flag `pos_connection`, on no package; an admin switches it on per restaurant (like Build your own) |
| Second language | The POS holds both languages (new fields in the POS) |
| Ingredients line | The POS recipe's ingredient names in order; without a recipe, the product's description |
| Choices | A required pick-one POS group becomes a variant; every other group becomes an add-on group with its minimum and maximum (new in Qayema) |
| Currency | The POS company currency must be the menu's main currency (checked when linking); products in the restaurant's second currency come across in it |
| Offers, Build your own | Switched off while connected |
| Removed in the POS | Hidden on the menu, never deleted (past orders keep their lines) |
| Tracking | Live from the POS |

## 3. Architecture

```
POS (branch)                                   Qayema (restaurant)
------------                                   -------------------
catalog changes --observers--> ping job  --->  POST /api/pos/webhook  (signed)
                                                  queue SyncPosCatalog
GET /api/v1/menu/catalog  <-----------------------  pull snapshot, apply diff
                                                    clear the menu cache
POST /api/v1/menu/orders  <-----------------------  SendOrderToPos (order placed)
Online orders screen: accept, ready, done
status change --job-->  POST /api/pos/webhook  ---> order status, guest tracking
```

**Ping, then pull.** The POS never pushes data, only a short signed "the
catalog changed" ping; Qayema answers by pulling the whole snapshot and applying
the difference. Per-change events would be unreliable in this POS: the product
to modifier-group pivot has no timestamps, recipe lines are deleted and recreated
on every save, and stock is a sum of movements with no event. A snapshot is a few
hundred rows, always right, and makes a missed ping harmless: an hourly pull and
a "Sync now" button repair anything.

**Orders are a queue in the POS, not a sale.** Today every POS order is a
finished sale (a cashier, a drawer, a payment row, stock taken; `orders.status`
is a database enum of completed, voided, debt). An online order therefore lives
in its own table until staff complete it, and completing it rings it up through
the POS's existing `CheckoutAction`. Nothing in the POS's sale rules changes.

## 4. The contract

All calls are JSON. The POS answers in its envelope (`{success, message, data,
meta}` / `{success: false, message, errors, code, data}`). Text fields are
`{locale: string|null}`. Money is a decimal string.

### Authentication

- **Qayema to POS:** `Authorization: Bearer <connection key>`. The key is a
  random 48-character secret shown once in the POS, stored there as a SHA-256
  hash, stored in Qayema encrypted. It resolves to one connection (company and
  branch).
- **POS to Qayema (webhooks):** `X-Pos-Connection: <connection uuid>`,
  `X-Pos-Timestamp: <unix>`, `X-Pos-Signature: sha256=<HMAC(secret,
  timestamp + "." + body)>`. Refused when older than 5 minutes, compared with
  `hash_equals`. The signing secret is made by Qayema when linking, sent once
  over the authenticated `POST /api/v1/menu/connection`, stored encrypted on
  both sides.

### POS endpoints (`/api/v1/menu/*`, middleware `menu.key`, module `online_menu`)

| Call | Purpose |
|---|---|
| `GET /connection` | Who this key is: company and branch names, currency, the two locales, the POS's own version |
| `POST /connection` | Finish linking: `{webhook_url, signing_secret}`; idempotent |
| `DELETE /connection` | Unlink from Qayema's side (the key stops working) |
| `GET /catalog` | The snapshot (below) |
| `POST /orders` | Place an online order; idempotent by `reference` |
| `GET /orders/{reference}` | One order's status (Qayema's repair call) |

### The catalog snapshot

```json
{
  "currency": "USD",
  "locales": ["en", "ar"],
  "categories": [
    {"id": "12", "parent_id": null,
     "name": {"en": "Mezze", "ar": "مقبلات"},
     "description": {"en": "Small plates", "ar": null}}
  ],
  "products": [
    {"id": "301", "category_id": "12",
     "name": {"en": "Hummus", "ar": "حمص"},
     "description": {"en": "Our house hummus", "ar": null},
     "ingredients": {"en": ["Chickpeas", "Tahini", "Lemon"], "ar": ["حمص", "طحينة", "ليمون"]},
     "price": "5.50", "currency": "USD",
     "image": {"url": "https://pos.example/storage/7/hummus.jpg", "version": "7-1730200000"},
     "available": true,
     "modifier_groups": [
       {"id": "4", "name": {"en": "Size", "ar": "الحجم"},
        "selection": "single", "required": true, "min": 1, "max": 1,
        "options": [{"id": "9", "name": {"en": "Large", "ar": "كبير"}, "price_delta": "2.00"}]}
     ]}
  ]
}
```

- `products`: `Product::sellable()`, active, not deleted, in an active category or none.
- `price`: `getSellingPrice()` (sale price when set, else base price), in the
  product's `currency`. `price_delta` is in the company `currency`, as the POS keeps it.
- `available`: the POS's own rule at the connection's branch location
  (`PosProductPresenter::availability()`): false when stock is tracked and below 1.
- `ingredients`: the recipe's ingredient names in `sort` order (prep items by
  their own name) while Recipes is on and the product has a recipe; otherwise null.
- `image`: null without a photo (never the POS's placeholder); `version` changes
  only when the photo does.
- `min`, `max`: `ModifierGroup::minimum()` / `maximum()`; `max` null is no limit.

### Orders

`POST /api/v1/menu/orders`:

```json
{
  "reference": "qy_8f3c...",
  "placed_at": "2026-10-29T18:02:11Z",
  "fulfilment": "delivery",
  "table": null,
  "guest": {"name": "Rami", "phone": "+96170123456", "address": "Hamra street", "location": "33.89,35.47"},
  "note": "No onions",
  "lines": [
    {"product_id": "301", "quantity": 2, "unit_price": "7.50",
     "option_ids": ["9"], "name": "Hummus"}
  ],
  "total": "15.00"
}
```

Answer `201 {reference, status: "received", pos_number}`; the same `reference`
again answers the first order (`200`). A product or option the POS no longer
sells is a `422` with the lines at fault.

### Webhooks to Qayema (`POST {qayema}/api/pos/webhook`)

```json
{"event": "catalog.changed", "at": "2026-10-29T18:00:00Z"}
{"event": "order.status", "reference": "qy_8f3c...", "status": "accepted", "at": "..."}
{"event": "connection.revoked", "at": "..."}
```

Statuses: `received`, `accepted`, `ready`, `done`, `cancelled` (with an optional
`reason`).

### Kept in step

One JSON fixture per call and webhook, committed in both repos
(`tests/Fixtures/pos-contract/*.json`). The POS's tests assert it produces them;
Qayema's tests feed them in. A contract change edits both fixtures in the same
pair of commits.

## 5. How Qayema applies the snapshot

`PosCatalogSync::apply(Restaurant, array $snapshot)`, one transaction, writes
only what changed (so the menu cache clears only when something did):

- **Categories:** matched by `categories.pos_id`. A POS child category becomes a
  category of its own (Qayema does not nest). Name and description from the POS;
  icon and order stay Qayema's. A new one goes last.
- **Dishes:** matched by `dishes.pos_id`. From the POS: name, ingredients (the
  list joined with the language's separator through `Ingredients::join()`, else
  the description tidied), price, currency, category, availability, photo
  (through `MediaService::replace()`, only when `image.version` changed;
  `pos_image_version` remembers it). Order stays Qayema's; a new dish goes last in
  its category.
- **Choices:** a group with `selection: single` and `min: 1` becomes a variant;
  any other group becomes an add-on group (`min`, `max`). Options matched by
  `pos_id`, written with `DishOptionsSync` (extended for add-on groups).
  `price_delta` is converted from the company currency to the dish's currency
  at the restaurant's rate when they differ.
- **Languages:** the POS locales map onto the menu's main and second language; a
  locale the menu does not use is ignored; a menu language the POS leaves empty
  keeps what Qayema had.
- **Gone from the POS:** hidden (`is_available = false`, `pos_removed_at` set),
  never deleted. Back in the POS: shown again.
- **Limits:** the package's dish and category limits still count; what does not
  fit is skipped and listed as a problem.
- **Problems** (a third currency, over a limit, a photo that would not download)
  never stop the sync; they are stored on the connection and listed on the
  Connect to POS card with the time of the last good sync.

## 6. Add-on groups (new in Qayema, used everywhere)

Today a dish's add-ons are one flat list (pick any). They gain optional groups:

- `dish_addon_groups` (dish, name translatable, `min_select` 0+, `max_select`
  null or 1+, `display_order`); `dish_addons.dish_addon_group_id` nullable.
  Add-ons without a group behave exactly as today.
- The menu's dish sheet shows each group with its own heading and "Pick up to 2"
  / "Pick at least 1", and `menu-dish.js` stops a third pick; `OrderPlacer`
  refuses a choice outside a group's limits (the order's snapshot keeps the
  group name).
- The dashboard's dish form and order editor show the groups read-only for a
  connected restaurant (the POS owns them); letting owners make groups by hand is
  a later, separate change.

## 7. Orders, end to end

1. The guest orders in the menu. `OrderPlacer` checks and prices it exactly as
   today and saves the Qayema order (`OrderChannel::Pos`). The guest gets the
   tracking page at once.
2. After commit, `SendOrderToPos` posts it (retries 10 s, 30 s, 2 min, 10 min,
   30 min). Accepted: the order keeps the POS's number. Refused for good or
   still unanswered after the retries: the order shows on Qayema's Orders page
   with "Not sent to your POS" and Try again, and the owner gets the usual
   new-order chime, so nothing is lost.
3. In the POS, the Online orders screen chimes and lists it. Staff **Accept**,
   then **Ready**, then **Complete**: completing rings it up through
   `CheckoutAction` as a normal sale by the cashier who completed it (their
   drawer, the stock, a cash payment, the order type delivery, takeaway or dine
   in, the table when there is one). The line prices are Qayema's (the guest saw
   and accepted them); a difference from the POS's own beyond its tolerance sets
   `needs_review` like an offline sale. **Cancel** takes nothing from stock.
4. Each step posts `order.status` to Qayema, which moves its order (accepted,
   ready, done, cancelled); the guest's tracking and the owner's dashboard follow
   through the existing Pusher news.
5. While orders go to the POS, the Qayema Orders page shows them read-only with
   their POS status (the POS is where they are handled).

## 8. Work in the POS (`swipensell`)

Each step follows the POS's Architecture v2 (Actions, Queries, thin controllers,
the JSON envelope, the module gate, a module-off test) and is tested with Pest.

**P1. Two languages for menu text**
- Migration: `translations` JSON (nullable) on `categories`, `products`,
  `modifier_groups`, `modifier_options`, shaped `{ar: {name, description}}`; the
  existing `name`/`description` stay the company language. `companies.second_locale`
  (nullable).
- A small `HasTranslations`-style trait in `app/Helpers` (no new package):
  `translation($field, $locale)` with fallback to the base column.
- Forms (category, product, modifier group and option): the second-language
  fields, shown while Online menu is on and a second locale is set.
- Tests: saving and reading both languages; the fields absent with the module off.

**P2. The Online menu module**
- `Module::OnlineMenu` (label, permissions `manage online menu` and
  `handle online orders`, depends on Catalog; its orders part also needs POS),
  folder `OnlineMenu`, the permission-sync migration (roles, plans), rows in the
  route-gating and module-off tests.
- `menu_connections`: company, branch, uuid, name, key hash, key prefix (shown),
  webhook URL, signing secret (encrypted), status (pending, linked, revoked),
  last call, last ping, last error. Settings screen: create a key (shown once),
  see the link, revoke.
- Middleware `menu.key` (resolves the bearer key to its connection, refuses a
  revoked one, sets the company and branch for the request, rate limited).
- The module's `Routes/api.php` under `/api/v1/menu`, exempted from the web-only
  middleware, listed in `ROUTES_THAT_STAY_ON` only where needed.

**As built in phase 1** (where it differs from the above):
- Online menu depends on POS (not only Catalog), so its orders part needs nothing more.
- One permission for now, `manage online menu` (granted with `manage settings`);
  `handle online orders` comes with P5.
- The main language is `companies.locale` (it existed, unused). Both languages are
  picked on the Online menu screen, from the 11 languages Qayema offers.
- No trait: the four models cast `translations`; `OnlineMenu\Services\MenuTranslations`
  gives the form rules, the merge on save and the text per language.
- The API routes live in `OnlineMenu/Routes/menu-api.php`, loaded by
  `OnlineMenuServiceProvider` (the shared `Routes/api.php` files sit under
  `auth:sanctum`). `module:online_menu` gates them through the connection's company.
- Keys look like `menu_` plus 43 characters. A wrong key counts against the caller's
  address: 30 failures in 10 minutes and it is held off.
- An ingredient list in a language is null unless every ingredient has a name in it.
- Ingredients (products of kind ingredient) also get the second-language name.

**P3. Catalog API**
- `Queries/MenuCatalogQuery` (eager loaded, one pass): categories, products,
  modifier groups and options through `ModifierCatalog`, availability through
  `PosProductPresenter`, ingredients through recipe lines ordered by `sort`,
  translations, image URL and version.
- `GET /connection`, `POST /connection`, `DELETE /connection`, `GET /catalog`,
  with API resources.
- Tests: the snapshot against the contract fixture; availability with stock on
  and off; ingredients with Recipes on and off; translations; a revoked key.

**P4. Pings**
- Observers (after commit) on Product, Category, ModifierGroup, ModifierOption,
  StockMovement and Company, plus explicit calls after the pivot sync in
  `SaveModifierGroupAction` and after `SaveRecipeAction`: mark the company's
  connections changed.
- Job `PingMenuConnection` (unique per connection for 10 s, `EnsureModuleOn`,
  backoff 5, 15, 60, 180, 600 s, retry until an hour), signed POST to the webhook.
  A failure is reported, never thrown into a sale.
- Tests: one ping for a burst of changes; none with the module off; the signature.

**As built in phase 3:** `CatalogChangeObserver` watches categories, products
(not cost-only changes), option groups and options, recipe lines and recipe
versions (a recipe save replaces its lines in bulk, so the version it records is
the sign), product photos, stock movements (only the branch whose location
moved) and the company's currency, languages and name. `MenuPings` queues
`SendMenuWebhook` after commit, 5 seconds later, one per menu and event until
it starts (`ShouldBeUniqueUntilProcessing`), gated by `EnsureModuleOn`. A menu
that does not answer is retried (5 s, 15 s, 1 min, 3 min, 10 min, for an hour)
and the error is kept on the connection; the job never throws, so a sale is
never troubled. Revoking a key in the POS sends `connection.revoked`; unlinking
from the menu's side does not. Checked live: a rename reached the menu in 5
seconds, a sell-out hid the dish in 5 seconds, a revoke disconnected the menu.

**P5. Online orders**
- `online_orders` (connection, branch, reference unique per connection, status,
  fulfilment, table, guest JSON, note, total, POS order id once completed, status
  timestamps) and `online_order_items` (product, quantity, unit price, option ids,
  name snapshot).
- Actions: `ReceiveOnlineOrderAction` (validate products and options at the
  branch, idempotent by reference), `AcceptOnlineOrderAction`,
  `MarkOnlineOrderReadyAction`, `CompleteOnlineOrderAction` (through
  `CheckoutAction`, recording Qayema's prices like `RecordOfflineSaleAction`),
  `CancelOnlineOrderAction`. Each status change dispatches `NotifyOnlineOrderStatus`
  (signed webhook, same retries).
- Screen `Pages/OnlineOrders/Index` (feature `online-menu`): new orders first with
  a chime and a badge in the sidebar, each card with the guest, the lines and
  options, the note, and its one next step.
- Tests: receive (valid, a gone product, a repeat), each step, completion making
  a real sale with stock and payment, cancel taking nothing, the webhooks, the
  module off.

**As built in phase 4 (POS side):** `online_orders` and `online_order_items`
(the POS's names, the menu's prices); permission `handle online orders`, given to
roles that may use the POS. `ReceiveOnlineOrderAction` checks every line (sold,
active, options within their groups' rules) and refuses the order with the lines
at fault (422 `lines_unavailable`, `data.lines`). `OnlineOrderFlow` moves an order
under a lock (409 `online_order_moved` when someone else moved it first) and
pings `order.status` with the reference, status, `pos_number` ("W-0012") and a
cancel reason. Completing goes through `RecordOfflineSaleAction` (offline id
`online-{id}`, cash, the staff member's open drawer, stock taken, a price that
no longer matches flagged for review); pickup becomes a takeaway sale, dine-in
a dine-in sale without a table session. The screen is `OnlineOrders/Index`
(polls every 10 s, chimes on a new order, a badge in the sidebar from the
shared `onlineOrdersWaiting`).

## 9. Work in Qayema

Backend (PHPUnit, Pint, Larastan) and dashboard (Vitest, Playwright) as usual.

**Q1. Flag and data**
- `Feature::PosConnection` (Flag, default 0, on no package; `lang/{en,ar}/features.php`),
  admin switch on the restaurant page.
- `pos_connections` (restaurant unique, POS address, key encrypted, signing
  secret encrypted, uuid, company and branch names, currency, locales,
  `sends_orders`, status, last sync, last error, problems JSON).
- `pos_id` (string, unique per restaurant) on `categories`, `dishes`,
  `dish_variants`, `dish_variant_options`, `dish_addons`, `dish_addon_groups`;
  `pos_removed_at` on categories and dishes; `pos_image_version` on dishes.
- Add-on groups (section 6): the table, the models, `DishOptionsSync`,
  `MenuDishOptions`, the sheet, `OrderPlacer`, the order snapshot, the
  dashboard's read-only display.

**Q2. Linking and the sync**
- `app/Services/Pos/`: `PosClient` (HTTP with the key, timeouts, the envelope),
  `PosSignature`, `PosCatalogSync`, `PosMatching` (name matches for the first
  link), jobs `SyncPosCatalog` (unique per restaurant) and an hourly schedule
  for every linked restaurant.
- API (owner): `GET/POST/DELETE /api/pos-connection` (show, link with
  `{address, key}` then confirm the matches, unlink), `POST /api/pos-connection/sync`.
- Webhook: `POST /api/pos/webhook` (public, signed, rate limited, outside
  CSRF); `catalog.changed` queues the sync, `connection.revoked` unlinks.
- `Restaurant::isConnectedToPos()`; while true the category and dish requests
  refuse create, delete and POS-owned fields (422 "Managed in your POS"), order
  and icon stay editable; Offers and Build your own are off (`LiveOffers` and
  `BuildChoices` return nothing, their API refuses writes, saved rows kept).
- Tests: linking (the currency check, the matches), the snapshot applied and
  applied again with no change, each mapping rule, a missed ping repaired by
  the hourly sync, signature and replay refusals, the locks, offers and builds
  off and back on disconnect.

**As built in phase 2** (where it differs from the above):
- No "confirm the matches" step. The first sync takes over the owner's own
  categories and dishes whose main-language name is the same as a POS one
  (they keep their photo, icon and place). Every other row the owner made is
  hidden from guests while connected, never deleted, and back on disconnect.
- On a dish the POS sends, its choices are the POS's: choices the owner had
  added before the link are replaced.
- A required pick-one group with a single option becomes an add-on group with
  minimum 1 and maximum 1 (a variant needs two options to show).
- `Restaurant::showsOffers()` and `showsBuilds()` are false while connected, so
  the menu, its prices and orders all follow; the Offers API refuses writes
  (reads still work) and saved offers come back on disconnect.
- While connected, a PATCH to a category or dish applies only `icon`; create,
  delete and the availability switch answer 422 "Managed in your POS."
- Disconnecting keeps the dishes the POS made, editable again, with their
  `pos_id`, so linking again matches them.
- The POS address may carry a path. Production calls only public https
  addresses (`services.pos.allow_private` / POS_ALLOW_PRIVATE_ADDRESSES lifts
  that for local development); photos are fetched under the same rule.
- A webhook signed call is acted on once (its signature is remembered for ten
  minutes). The hourly pull is the `pos:sync` command.

**Q3. Dashboard**
- Features page: the Connect to POS card (address and key, the matches to
  confirm, linked branch, last sync, problems, Sync now, disconnect), shown only
  with the flag.
- Categories and Dishes: no Add and no Delete while connected, POS fields
  read-only with "Managed in your POS", drag order and the icon still editable.
- Offers and Build your own pages: "Off while your menu is connected to a POS".
- Tests: Vitest for the card and the locked forms; Playwright against a fake POS
  (a small e2e route answering the contract fixtures).

**Q4. Orders**
- `OrderChannel::Pos`, "To your POS" in How guests send orders (needs the link),
  `SendOrderToPos`, the `order.status` webhook moving the order, the "Not sent to
  your POS" badge with Try again, the Orders page read-only for POS orders.
- Tests: an order sent and repeated (idempotent), refused, unanswered then the
  badge, each status reaching the guest's tracking, switching back to WhatsApp.

**As built in phase 4 (Qayema side):** no new channel. An order placed in the
menu stays a menu order with tracking; while `pos_connections.sends_orders` is
on it is also sent (`PosOrders`, `SendOrderToPos`, retried 10 s, 30 s, 2 min,
10 min, 30 min), with `orders.pos_reference`, `pos_status` (sending, sent,
failed), `pos_number`, `pos_error` and `cancel_reason`. WhatsApp orders are never
sent; switching How guests send orders to WhatsApp, or the card's switch off,
keeps orders out of the POS. While sending or sent, the guest can no longer
change the order and the owner cannot move or edit it (409 `handled_in_pos`);
a failed one can be sent again (`POST /api/orders/{order}/send-to-pos`). The
POS's steps move the order through `PosOrders::reported()` and the existing
Pusher news.

## 10. Phases (in this order)

| Phase | Repo | Delivers |
|---|---|---|
| 1 | POS | P1 languages, P2 module and connections, P3 catalog API |
| 2 | Qayema | Q1 flag and data (with add-on groups), Q2 linking and sync, Q3 dashboard |
| 3 | POS | P4 pings (live updates in seconds) |
| 4 | both | P5 online orders, Q4 orders |
| 5 | both | Hardening: problems list, an alert when the POS has been unreachable for an hour, docs in both repos |

After phase 2 the menu already follows the POS (hourly and on Sync now); phase 3
makes it live; phase 4 brings the orders.

**Developing both together:** the POS runs locally on its own port (8100) with
its PostgreSQL and queue worker; Qayema on 8000 with its queue worker. On this
machine the POS runs in Docker: its database is the `swipensell-postgres`
container (postgres:16, 127.0.0.1:5433, user postgres, no password, local
only, volume `swipensell-pgdata`) and its PHP is the `swipensell-php-pg` image,
run with `--network host`, the repo mounted at /app and `-e DB_PORT=5433
-e APP_URL=http://127.0.0.1:8100`. Serve it with `php -S` started inside
`public/` (artisan serve drops DB_PORT), and run `php artisan queue:work` for
the pings. Qayema's e2e stack (8001) is a safe partner for trying a link. The
connection key from the local POS links the local Qayema. Each repo's tests never
call the other: they use the contract fixtures and `Http::fake`.

**As built in phase 5 (hardening):**
- `pos:watch` (every 15 minutes) tells an outage once: a link whose last pull
  failed and whose last good one (or the link itself) is over an hour old gets
  `alerted_at`, an email to the owner when the account has one (`PosUnreachable`)
  and a push to the admins' phones (`AdminAlerts::posUnreachable()`); owners have
  no push here. The next pull that works clears it. The Connect to POS card says
  "Your POS has not answered since ..." (`unreachable_since`).
- The POS's Online menu screen shows a link's last error when the menu could not
  be reached (pings keep retrying on their own).
- On the guest's dish sheet, a group where every add-on is required (a required
  POS group with one option) comes ticked, locked and labelled "Included".

## 11. Risks and how they are handled

| Risk | Handling |
|---|---|
| A ping is lost | Hourly pull and Sync now; pings carry no data, so order does not matter |
| The POS is down when an order comes | The order lives in Qayema first; retries; the badge and the chime |
| A product disappears between menu and order | The POS answers 422 with the lines; Qayema shows the order as not sent with the reason |
| Prices differ (rounding, a change mid-order) | The POS records Qayema's prices and flags `needs_review` |
| Many orders at one branch | The POS locks the branch only at completion, as for any sale |
| A leaked key | Revocable on either side, hashed in the POS, encrypted in Qayema, scoped to one branch |
| Package limits | Still counted; what does not fit is listed, never silently dropped |
