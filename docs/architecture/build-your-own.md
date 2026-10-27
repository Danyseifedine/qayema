# Build your own

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

A dish can carry up to three **builds** (`dish_builds`, model `DishBuild`):
the guest fills a number of parts the owner allows, each from a list of
choices. Half and half pizzas are one setup of it; three scoops, a mixed
grill of two meats and "pick two sides" are others.

- **Who has it:** the `build_your_own` flag is on no package. An admin
  switches it on for one restaurant (its page's Features section, a set
  grant), and `Restaurant::showsBuilds()` decides everything below. Off, the
  rows stay saved, but the menu, the orders and dish saves leave them alone.
- **How many:** `build_limit` (a limit, 3 unless an admin sets another on
  the same Features section, null for no cap) is how many dishes guests
  build (dishes with a build) the restaurant may have. `DishController`
  refuses one more (`ensureRoomFor(Feature::BuildLimit, 'builds')`: a new
  dish with builds, or a plain dish given some; a dish already built is
  changed freely). `/api/user` sends `limits.builds`; the dashboard's page
  counts its list against it, shows "3 / 3" and stops "New build" there.
  The admin's own dish form is not held to it.
- **Shape** (`App\Enums\BuildShape`):
  - `split` cuts one item into parts (a pizza in halves or thirds);
  - `pieces` is that many whole items.
- **Part counts** (`parts`, 1 to 8 sorted, `config('menu.dish_builds')`): the
  counts the guest may pick, e.g. [1, 2, 3] for whole, half and half, thirds.
- **Choices** (`App\Services\Menu\BuildChoices`, the same on the menu and
  for orders), each named by a key a cart sends:
  - **From a category** (`category_id`): its dishes guests can buy (shown, a
    price of their own, in the build's dish's currency, never that dish
    itself, never an offer's deal dish), keyed `d<dishId>`. Their prices follow the dishes.
    The owner may tick only some (`dish_builds.dish_ids`,
    `DishBuild::offersDish()`): null offers every one, a dish added to the
    category later included; a list offers only those. The dashboard sends
    null while every dish is ticked; the admin's "Only these dishes" left
    empty is null too.
  - **Its own list:** `dish_build_choices` without a dish, keyed
    `c<choiceId>`, each named and priced, 2 to 30.
  - A build left with fewer than two choices is not offered.
- **Sizes** (a pizza priced by size): while variants are on, a category dish
  with no price of its own is offered too when its first variant's options
  are full prices (Small $8, Large $12).
  - A size's key is its option name in the menu's main language, trimmed and
    lower case (`BuildChoices::sizes()`), so "Large" on Margherita and
    " LARGE " on Pepperoni are one size. The build's sizes are every key its
    choices have, in the order first met.
  - The guest picks the size first (pills in the sheet, only with more than
    one size); `BuildChoices::forSize()` then gives each sized dish its price
    at that size, shares only (its part prices are ignored), leaves out one
    not made in that size, and keeps a dish with its own price as it is at
    every size.
  - A build with sizes is offered when some size has 2 choices or more
    (`offered()`); the sheet starts at the first such size.
  - A cart line's build carries `size` (the key); without it, or with one the
    build lacks, the order is refused like an unfilled build. The snapshot
    adds `size` (its name) and `size_key`, and the words end "(Large)".
  - The dashboard twins this in `features/orders/utils/order-edit.ts`
    (`choicesAt()`, `firstSize()`, a size dropdown in `BuildPicker`); the
    Build your own editor lists a sized dish with its sizes ("Small $8.00 ·
    Large $12.00"), asks no part prices for it, and counts it at its
    cheapest size in "From".
- **Price** (`App\Services\Menu\BuildPrice`, in whole cents; twinned by
  `public/js/menu-build.js`: change one, change both):
  - **split:** a part pays the price its choice sets for that part size
    (`part_prices`, `{"2": "7.00"}`, on an own choice or on a category dish's
    row in `dish_build_choices`), else its share of the whole. The shares are
    added up first and rounded once, half up: half of $12 and half of $16 is
    $14, a $7 half and half of $16 is $15.
  - **pieces:** each part is its choice's whole price.
  - The build's price adds to the dish's, beside variants and add-ons. A dish
    with no price of its own is priced by its builds (`MenuDishOptions`
    `priced`; the card's "from" is `BuildPrice::cheapest()`).
  - An offer's discount comes off the dish's own price only, as with variants.
- **Writing:** the dashboard's Build your own page (its own section, no
  longer the dish form) sends a dish's whole list: `PATCH /api/dishes/{id}`
  with only `builds`, which changes nothing else on the dish (`[]` removes
  them all), or `POST /api/dishes` for a dish made there with its build. The
  dish form no longer sends `builds`, so a dish save leaves them alone
  (`ValidatesDishBuilds`, `DishBuildsSync`). A build or own choice with this
  dish's id is updated (hidden languages kept), anything else is created, one
  left out is deleted. A category's rows are only the dishes given a part
  price. Part prices keep only the counts the build allows, over 1, split
  only. `dish_ids` is kept for a category's build only (each a dish of that
  category, never the dish itself), null for an own list or when not sent.
  `DishResource` sends `builds` as saved, `dish_ids` included. The admin's dish form has a
  plainer editor (Repeaters), shown only where the feature is on.
- **The dish:** a build's dish is a name and an icon (`dishes.icon`, one of
  `CategoryIcons`, sent as `icon`), in no category: a dish request with
  builds may leave `category_id` null (one without still needs it). The
  page saves it with no price, description or photo, so its build alone
  prices it, and titles the build with the dish's name. Home's "dishes
  without a photo" skips it.
- **Above the search:** while the feature is on, `PublicMenuController`
  takes every dish with builds out of its category's list (one still filed
  under a category from before too) and adds those in no category
  (`builtDishes()`, one query), handed to `menu.partials.builds`: a row of
  cards above the search, each with its icon (the chef's hat without one),
  its name and "Make it your way", never a price, opening its sheet like
  any dish with choices. Only a dish whose builds can be filled is drawn.
- **Menu:** `MenuDishOptions` adds `builds: [{id, name, shape, parts,
  sizes: [{key, name}], choices: [{key, name, price, part_prices, sizes:
  {key: price}}]}]` to `#dish-options` (`price` null for a sized dish).
  `menu-dish.js` draws each build in the dish sheet:
  - the sizes as pills, when there is more than one;
  - the part counts as pills;
  - a picker per part (`partPicker()`: a button with the choice and its
    price opening a listbox of every choice, never the browser's dropdown;
    Escape closes only the list);
  - the price updating live.

  The cart (`menu-cart.js`) keeps a line's `builds: [{build, parts:
  [keys], size?}]`, prices and words it through `QAYEMA_BUILD.priced()`, sends it
  with the order and drops a line the menu no longer allows. The words and
  the "Part :number" labels are translated in every menu language.
- **Ordering:** `items.*.builds` (`PlaceOrderRequest`, `EditOrderItemsRequest`).
  - `OrderPlacer::buildChoices()` requires every offered build filled: a
    count it allows, and a choice it offers for each part. Otherwise it is
    "Choose every part of :build for :dish.", never a different thing charged.
  - It prices the line with `BuildPrice` and merges identical lines (a build's
    parts keep their order).
  - It snapshots `order_items.options.builds: [{name, shape, parts: [{choice,
    share}], price, build_id, keys, size?, size_key?}]`. `builds` appears only on lines that
    have them, so older snapshots read the same.
  - `OrderItem::built()` words it: "½ Margherita + ½ Pepperoni", "2 ×
    Vanilla, Chocolate". `choices()` gives "Your pizza: …" for WhatsApp, the
    admin and the dashboard; `picks()` is the guest's own order page.
  - The guest's cart comes back with its builds (`build_id`, `keys`) to change
    an order.
- **Tests:**
  - `tests/Unit/Services/Menu/BuildPriceTest.php` (the cases the JS twin must
    match);
  - `tests/Feature/Api/DishBuildTest.php` (saving, builds alone included);
  - `tests/Feature/Menu/MenuDishBuildsTest.php` (the page data);
  - `tests/Feature/Orders/BuildOrderTest.php` (ordering);
  - `tests/Feature/Admin/DishBuildAdminTest.php`.

  E2E scenarios take `builds` on a dish (`category` by its English name,
  `dishes` for only those by English name, `part_prices` by dish name, or
  `choices`).
