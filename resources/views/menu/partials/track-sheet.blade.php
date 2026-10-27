{{--
    Following an order placed in the menu, in a sheet over it
    (public/js/menu-order.js); live through Pusher when the keys are set,
    its library loaded only when there is an order. The order itself is
    drawn by menu.partials.order-tracking, which the script asks for.
--}}
@use('App\Support\Asset')
@php
    // Live order tracking through Pusher, when its keys are set; the
    // public key and cluster only, the secret stays on the server.
    $pusher = config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key')
        ? [
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'script' => Asset::versioned('js/pusher.min.js'),
        ]
        : null;
@endphp
<dialog class="dish-sheet track-sheet" id="track-sheet" aria-labelledby="track-sheet-title">
    <div class="dish-sheet-body">
        <div class="track-sheet-head">
            <h2 id="track-sheet-title">{{ __('Order tracking') }}</h2>
            <button type="button" class="track-sheet-close" data-track-close aria-label="{{ __('Close') }}">
                <span class="icon">{!! $icons['close'] !!}</span>
            </button>
        </div>
        <div class="dish-sheet-scroll track-sheet-scroll" data-track-body></div>
    </div>
</dialog>

<script>
    window.QAYEMA_ORDERS = {
        orderUrl: @js(route('public.order', $restaurant->slug)),
        orderKey: @js('qayema-order-'.$restaurant->slug),
        pusher: @js($pusher),
        icons: { cart: @js($icons['cart']), close: @js($icons['close']) },
        strings: {
            close: @js(__('Close')),
            track: @js(__('Track your order')),
            trackShort: @js(__('Track')),
            yourOrder: @js(__('Your order #:reference')),
            statusWaiting: @js(__('Waiting for the restaurant')),
            statusAccepted: @js(__('Accepted, being prepared')),
            statusOnItsWay: @js(__('On its way')),
            statusReady: @js(__('Ready for pickup')),
            statusDelivered: @js(__('Delivered')),
            statusPickedUp: @js(__('Picked up')),
            statusComing: @js(__('Coming to your table')),
            statusServed: @js(__('Served')),
            statusCancelled: @js(__('Cancelled')),
        },
    };
</script>
<script src="{{ Asset::versioned('js/menu-order.js') }}" defer></script>
