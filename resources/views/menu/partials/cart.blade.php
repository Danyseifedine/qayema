{{--
    The cart: its sheet on a phone (the wide screen's panel is the design's
    `[data-cart-panel="desktop"]`), what public/js/menu-cart.js needs to
    know, and, for orders placed in the menu while the package tracks
    orders, the tracking sheet (menu.partials.track-sheet). Without it the
    cart still sends the order, and the guest is only told its number: no
    order is kept to follow, change or add to. Included by
    menu.partials.scripts when the menu takes orders.
--}}
@use('App\Support\Asset')
@php
    // Ordering in the menu: who to call and where to bring it. On
    // WhatsApp the guest only adds a note.
    $inMenu = $order_channel === \App\Enums\OrderChannel::Menu;
    // The guest's details are asked in the menu, and on WhatsApp when the
    // owner chose to ask any (Restaurant::whatsappAsks()).
    $asksDetails = $inMenu || collect($whatsapp_asks ?? [])->contains(fn (string $rule): bool => $rule !== \App\Enums\Ask::Off->value);
    $guestCountries = $asksDetails
        ? collect(config('countries'))->map(fn (array $country, string $code): array => [
            'code' => $code,
            'flag' => $country['flag'],
            'dial' => $country['dial'],
            // In the menu's language, to read and to search; the English
            // name is searched too.
            'name' => class_exists(\Locale::class) ? (\Locale::getDisplayRegion('-'.$code, $locale) ?: $country['label']) : $country['label'],
            'label' => $country['label'],
        ])->values()->all()
        : [];
@endphp
<dialog class="sheet" id="cart-sheet">
    <div class="sheet-body">
        <div class="sheet-head">
            <button type="button" class="sheet-close" data-close-cart aria-label="{{ __('Close') }}">
                <span class="icon icon-back">{!! $icons['back'] !!}</span>
            </button>
            <h2>{{ __('Your cart') }}</h2>
            <span style="width:36px"></span>
        </div>
        <div class="sheet-scroll" data-cart-panel="sheet"></div>
        <div class="sheet-foot" data-cart-foot></div>
    </div>
</dialog>

<script>
    window.QAYEMA_MENU = {
        orderUrl: @js(route('public.order', $restaurant->slug)),
        locale: @js($locale),
        currency: @js($currency),
        // How a line's choices are listed in the menu's language ("، " in Arabic).
        listSeparator: @js(App\Support\Ingredients::separator($locale)),
        // Under a total that turned dishes priced in the second currency
        // into the main one: the rate it used ("$1 = L.L 89,000").
        rateNote: @js($restaurant->second_currency !== null && $restaurant->exchange_rate !== null
            ? App\Support\Price::money(config('currencies.'.$restaurant->second_currency.'.symbol', $restaurant->second_currency), '1').' = '.App\Support\Price::money($currency, App\Support\Price::toMain('1', (string) $restaurant->exchange_rate))
            : null),
        storageKey: @js('qayema-cart-'.$restaurant->slug),
        mode: @js($order_channel->value),
        // The table whose QR code opened the menu, and where the page
        // remembers it, so a reload or a language switch stays at it.
        table: @js($table ? ['code' => $table->code, 'name' => $table->name] : null),
        tableKey: @js('qayema-table-'.$restaurant->slug),
        @if ($asksDetails)
        guestKey: @js('qayema-guest-'.$restaurant->slug),
        addressUrl: @js(route('public.address', $restaurant->slug)),
        // Delivery and pickup taken in the menu, or asked on WhatsApp
        // with the address; dine-in is its own feature, offered only at
        // a table (menu-cart.js types()).
        types: @js($order_types ?? []),
        country: @js(isset(config('countries')[$restaurant->country_code]) ? $restaurant->country_code : array_key_first(config('countries'))),
        countries: @js($guestCountries),
        @endif
        @if ($inMenu)
        dineIn: @js($dine_in ?? false),
        // An order placed in the menu waits for someone to read it, so
        // outside the hours the cart only shows what was picked.
        closed: @js($open === false),
        @else
        // What this WhatsApp cart asks for: each off, optional or required.
        asks: @js($whatsapp_asks ?? null),
        @endif
        icons: { plus: @js($icons['plus']), minus: @js($icons['minus']), chevron: @js($icons['chevron']), check: @js($icons['check']), close: @js($icons['close']), pin: @js($icons['pin']) },
        strings: {
            empty: @js(__('Nothing added yet.')),
            each: @js(__('each')),
            // "5 items" in each plural form the language has ("صنفان" for
            // two in Arabic), by a number that takes it; the cart picks the
            // form with Intl.PluralRules.
            items: @js(collect([0, 1, 2, 3, 11, 100])->mapWithKeys(fn (int $count): array => [$count => str_replace((string) $count, ':count', trans_choice(':count item|:count items', $count))])),
            place: @js(__('Place order')),
            placing: @js(__('Sending…')),
            total: @js(__('Total')),
            failed: @js(__('That did not send. Please try again.')),
            // Refusals the server answers before it knows the guest's language.
            expired: @js(__('This page was open for too long. Refresh it and try again.')),
            tooMany: @js(__('Too many requests. Please slow down.')),
            refresh: @js(__('This menu was just updated. Please refresh the page to order.')),
            remove: @js(__('Remove')),
            add: @js(__('Add')),
            options: @js(__('See options')),
            close: @js(__('Close')),
            optional: @js(__('Optional')),
            note: @js(__('Note for the restaurant')),
            noteHint: @js(__('Anything they should know?')),
            check: @js(__('Check the details above.')),
            forTable: @js(__('This order is for :table.')),
            goneFromCart: @js(__('Taken off your cart')),
            goneFromCartBody: @js(__(':dishes is not available right now.')),
            // A line some of whose dishes a pair deal makes cheaper.
            pairOff: @js(__(':count at :percent% off')),
            pairFree: @js(__(':count free')),
            @if ($asksDetails)
            how: @js(__('How would you like it?')),
            delivery: @js(__('Delivery')),
            pickup: @js(__('Pickup')),
            dine_in: @js(__('At my table')),
            needsTable: @js(__('Scan the QR code on your table to order.')),
            needsTableShort: @js(__('Scan your table to order')),
            name: @js(__('Your name')),
            nameHint: @js(__('First and last name')),
            nameMissing: @js(__('Add your name so the restaurant knows who to ask for.')),
            phone: @js(__('Phone number')),
            phoneHint: @js(__('Mobile number')),
            country: @js(__('Country code')),
            countrySearch: @js(__('Search a country or code')),
            countryNone: @js(__('No country matches that.')),
            address: @js(__('Address')),
            addressHint: @js(__('Street, building, floor')),
            locate: @js(__('Use my current location')),
            locating: @js(__('Finding your location…')),
            located: @js(__('Location added')),
            locatedFilled: @js(__('We filled in your street. Add the building and floor.')),
            locateFailed: @js(__('We could not get your location. Your address is enough.')),
            phoneMissing: @js(__('Add your phone number so the restaurant can call you.')),
            phoneInvalid: @js(__('Check your phone number.')),
            addressMissing: @js(__('Add your address for the delivery.')),
            @endif
            @if ($inMenu)
            closed: @js(__('Closed now')),
            closedNote: @js(__('We are closed right now. Ordering opens again when we do.')),
            sent: @js(__('Order sent')),
            track: @js(__('Track your order')),
            editing: @js(__('Changing order #:reference')),
            adding: @js(__('Adding to your order #:reference')),
            addToOrder: @js(__('Add to order')),
            added: @js(__('Added to your order')),
            addedBody: @js(__('The restaurant will see what you added.')),
            waitingAccepted: @js(__('Your order #:reference is being prepared. You can order again once it is done.')),
            waitingDelivery: @js(__('Your order #:reference is on its way. You can order again once it arrives.')),
            waitingPickup: @js(__('Your order #:reference is ready for pickup. You can order again once you have it.')),
            waitingDineIn: @js(__('Your order #:reference is coming to your table. You can order again once it is served.')),
            oneAtATime: @js(__('One order at a time')),
            keepOrder: @js(__('Keep it as it was')),
            update: @js(__('Update order')),
            updated: @js(__('Order updated')),
            updatedBody: @js(__('The restaurant will see your changes.')),
            unavailable: @js(__('Sent. :dishes is no longer available, so it was taken off your order.')),
            locked: @js(__('This order can no longer be changed')),
            lockedBody: @js(__('The restaurant has already accepted it. Call them if you need to change something.')),
            sentBody: @js(__('The restaurant will call you to confirm. Your order number is :reference.')),
            sentBodyDineIn: @js(__('The restaurant has your order for :table. Your order number is :reference.')),
            @endif
        },
    };
</script>
<script src="{{ Asset::versioned('js/menu-cart.js') }}" defer></script>

@if ($inMenu && $restaurant->tracksOrders())
    @include('menu.partials.track-sheet')
@endif
