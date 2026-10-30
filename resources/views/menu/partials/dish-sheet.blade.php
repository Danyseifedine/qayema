{{--
    One sheet for every dish with choices, filled by public/js/menu-dish.js
    from the data below: a bottom sheet on a phone, a card on a wide screen.
    Without ordering it only shows the choices and their prices. Included
    by menu.partials.scripts when a dish has choices.
--}}
@use('App\Support\Asset')
<dialog class="dish-sheet" id="dish-sheet" aria-labelledby="dish-sheet-title">
    <div class="dish-sheet-body">
        <button type="button" class="dish-sheet-close" data-dish-close aria-label="{{ __('Close') }}">
            <span class="icon">{!! $icons['close'] !!}</span>
        </button>
        <div class="dish-sheet-scroll">
            <img class="dish-sheet-photo" data-dish-photo alt="" hidden>
            <div class="dish-sheet-head">
                {{-- Opening the sheet focuses its title, not the close button,
                     which would wear a focus ring after a tap. --}}
                <h2 id="dish-sheet-title" data-dish-title tabindex="-1" autofocus></h2>
                {{-- On a live offer: the price before it struck out, and the card's badge. --}}
                <div class="dish-sheet-prices">
                    <span class="dish-sheet-price" data-dish-price></span>
                    <s class="price-was" data-dish-was hidden></s>
                </div>
                <p class="dish-sheet-ingredients" data-dish-ingredients></p>
            </div>
            <div data-dish-groups></div>
        </div>
        <div class="dish-sheet-foot" data-dish-foot></div>
    </div>
</dialog>

<script type="application/json" id="dish-options">@json($dish_options)</script>
<script>
    window.QAYEMA_DISH = {
        currency: @js($currency),
        canOrder: @js($can_order),
        icons: { plus: @js($icons['plus']), minus: @js($icons['minus']), check: @js($icons['check']), chevron: @js($icons['chevron']) },
        strings: {
            pickOne: @js(__('Pick 1')),
            optional: @js(__('Optional')),
            addons: @js(__('Add-ons')),
            upTo: @js(__('Pick up to :max')),
            atLeast: @js(__('Pick at least :min')),
            between: @js(__('Pick :min to :max')),
            exactly: @js(__('Pick exactly :count')),
            included: @js(__('Included')),
            missing: @js(__('Pick at least :min from :group.')),
            add: @js(__('Add')),
            remove: @js(__('Remove')),
            quantity: @js(__('Quantity')),
            free: @js(__('Free')),
            whole: @js(__('Whole')),
            halves: @js(__('Half & half')),
            thirds: @js(__('Thirds')),
            quarters: @js(__('Quarters')),
            parts: @js(__(':count parts')),
            pieces: @js(__(':count pieces')),
            part: @js(__('Part :number')),
            size: @js(__('Size')),
            split: @js(__('Parts')),
            howMany: @js(__('How many')),
        },
    };
</script>
<script src="{{ Asset::versioned('js/menu-dish.js') }}" defer></script>
