# Variants and add-ons

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

A dish can carry **variants** (owner-named choices such as Size or Spice
level; the guest picks exactly one option of each) and **add-ons** (Extra
cheese; the guest picks any). Every option and add-on has a `price` that is
**added** to the dish's (0 for a choice that costs nothing more). Tables
`dish_variants`, `dish_variant_options`, `dish_addons` (models `DishVariant`,
`DishVariantOption`, `DishAddon`, names translatable like the dish's).

- Package flags `variants` and `addons` (Pro, Premium, Custom), each with its
  own switch on the Features page. Off, or not on the package: the rows stay
  saved but leave the menu and orders, and dish saves leave them alone.
- **Writing:** the dashboard's dish form sends each whole list in order
  (`variants: [{id?, name, options: [{id?, name, price}]}]`, `addons: [{id?,
  name, price}]`). `ValidatesDishOptions` (on Store/UpdateDishRequest) checks a
  list only while it is shown, so a switched-off list never reaches
  `validated()`. `App\Services\Menu\DishOptionsSync` writes it: an id of this
  dish's row updates it (hidden languages kept), anything else is created, a
  saved row left out is deleted. Validated nested input can come back with
  its indexes shuffled; the sync orders rows by index. Caps per dish are
  `config/menu.php` (5 variants, 10 options each, 20 add-ons).
- **Price:** a dish may have no price of its own when it has variants: its
  first variant's options are then full prices (a sandwich: Small $7, Large
  $12), each one required, and everything else (other variants, add-ons)
  still adds on top. Add-ons alone need a dish price. `MenuDishOptions` sends
  `priced: false` so the sheet shows those options as "$7.00", not "+$7.00";
  the card, the cart (`data-price="0.00"`) and `OrderPlacer` start from 0.
- **Menu:** `App\Services\Menu\MenuDishOptions` builds each dish's choices in
  the guest's language (a dish with a price or one variant to price it,
  only variants with 2+
  options), passed to the template as `$dish_options`. The card shows the
  cheapest combination's price; one
  `#dish-sheet` dialog is filled by `public/js/menu-dish.js` from the
  `#dish-options` JSON. With ordering it hands `qayema:add` events to
  `menu-cart.js` (a cart line is a dish plus its choice ids, so one dish can
  sit in the cart twice); without ordering the sheet only shows the choices.
- **Ordering:** `items.*.options` (option ids) and `items.*.addons`.
  `OrderPlacer` requires one option of every variant ("Choose a Size for
  Burger." in the guest's language), ignores ids that are not this dish's,
  prices `unit_price` = dish + options + add-ons, merges identical lines and
  snapshots `order_items.options` `{variants: [{name, choice, price}], addons:
  [{name, price}]}`. `OrderItem::choices()` is the readable form used by the
  WhatsApp message and the admin's orders list.
