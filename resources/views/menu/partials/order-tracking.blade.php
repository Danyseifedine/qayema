{{--
    An order placed in the menu, as the guest follows it: where it has got
    to (sent, accepted, on its way, ready or coming to the table, then
    delivered, picked up or served, or cancelled), what they ordered and
    where it goes. Drawn by the server
    and shown in the menu's tracking sheet (public/js/menu-order.js), which
    asks for it again whenever Pusher says the order moved.
    Expects $order (with its items), $restaurant and $locale.
--}}
@use('App\Enums\Fulfilment')
@use('App\Enums\OrderStatus')
@use('App\Services\Menu\MenuLanguages')
@use('App\Services\Menu\OpeningHours')
@use('App\Services\Orders\WhatsAppLink')
@use('App\Support\MenuIcons')
@use('App\Support\Price')
@php
    $name = MenuLanguages::reader($restaurant, $locale)($restaurant, 'name');
    $icons = MenuIcons::all();
    $currency = config("currencies.{$order->currency}.symbol", $order->currency);
    $timezone = OpeningHours::for($restaurant)->timezone();
    $time = fn ($moment): ?string => $moment?->copy()->setTimezone($timezone)->format('H:i');
    $number = WhatsAppLink::internationalNumber($restaurant);
    $call_url = $number !== null ? 'tel:+'.$number : null;

    $delivery = $order->fulfilment === Fulfilment::Delivery;
    $dineIn = $order->fulfilment === Fulfilment::DineIn;
    $status = $order->status;
    $left = match (true) {
        $delivery => __('On its way'),
        $dineIn => __('Coming to your table'),
        default => __('Ready for pickup'),
    };
    $handedOver = match (true) {
        $delivery => __('Delivered'),
        $dineIn => __('Served'),
        default => __('Picked up'),
    };

    [$title, $body, $tone] = match ($status) {
        OrderStatus::Placed => [__('Order sent'), __('Waiting for the restaurant to accept it.'), 'waiting'],
        OrderStatus::Accepted => [$dineIn ? __('Being prepared') : __('Accepted'), __('The restaurant is preparing your order.'), 'moving'],
        OrderStatus::Ready => [$left, match (true) {
            $delivery => __('Your order has left the restaurant.'),
            $dineIn => __('Your order is ready and on its way to your table.'),
            default => __('Your order is ready to collect.'),
        }, 'moving'],
        OrderStatus::Done => [$handedOver, __('Enjoy your meal!'), 'done'],
        OrderStatus::Cancelled => [__('Cancelled'), __('The restaurant cancelled this order. Call them if you have a question.'), 'cancelled'],
    };

    // The steps it goes through: four when it leaves the restaurant, three
    // at a table (sent, being prepared, served), where the guest needs no
    // word that it is ready. A step the restaurant skipped still counts as
    // passed, just without a time.
    $steps = $dineIn
        ? [
            ['label' => __('Sent'), 'time' => $time($order->placed_at)],
            ['label' => __('Being prepared'), 'time' => $time($order->accepted_at)],
            ['label' => $handedOver, 'time' => $status === OrderStatus::Done ? $time($order->closed_at) : null],
        ]
        : [
            ['label' => __('Sent'), 'time' => $time($order->placed_at)],
            ['label' => __('Accepted'), 'time' => $time($order->accepted_at)],
            ['label' => $delivery ? __('On its way') : __('Ready'), 'time' => $time($order->ready_at)],
            ['label' => $handedOver, 'time' => $status === OrderStatus::Done ? $time($order->closed_at) : null],
        ];
    $reached = match ($status) {
        OrderStatus::Placed, OrderStatus::Cancelled => 1,
        OrderStatus::Accepted => 2,
        OrderStatus::Ready => $dineIn ? 2 : 3,
        OrderStatus::Done => count($steps),
    };
@endphp
<section class="track-card track-status is-{{ $tone }}" aria-live="polite">
    <span class="track-mark">
        <span class="icon">{!! $icons[$tone === 'cancelled' ? 'close' : ($tone === 'waiting' ? 'clock' : 'check')] !!}</span>
    </span>
    <h3 class="track-title">{{ $title }}</h3>
    <p class="track-body">{{ $body }}</p>
    <p class="track-ref">{{ __('Order #:reference', ['reference' => $order->reference]) }}</p>

    @unless ($status === OrderStatus::Cancelled)
        <ol class="track-steps">
            @foreach ($steps as $index => $step)
                <li @class(['reached' => $index < $reached, 'current' => $index === $reached - 1])>
                    <span class="track-dot" aria-hidden="true">
                        @if ($index < $reached)
                            <span class="icon">{!! $icons['check'] !!}</span>
                        @endif
                    </span>
                    <span class="track-step-label">{{ $step['label'] }}</span>
                    @if ($step['time'] && $index < $reached)
                        <span class="track-step-time">{{ $step['time'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @endunless

    @if ($order->guest_updated_at)
        <p class="track-changed">{{ __('You changed this order at :time.', ['time' => $time($order->guest_updated_at)]) }}</p>
    @endif
    @if ($order->owner_updated_at)
        <p class="track-changed">{{ __('The restaurant updated your order at :time.', ['time' => $time($order->owner_updated_at)]) }}</p>
    @endif

    {{-- Until the restaurant accepts it, the guest may still change it:
         the cart takes it back (menu-cart.js). --}}
    @if ($status->isOpenToGuest())
        <button type="button" class="track-button track-edit" data-edit-order="{{ $order->tracking_token }}">{{ __('Change my order') }}</button>
    @endif
</section>

<section class="track-card">
    <h3 class="track-section">{{ __('Your order') }}</h3>
    <ul class="track-lines">
        @foreach ($order->items as $item)
            <li>
                <span class="track-qty">{{ $item->quantity }}×</span>
                <span class="track-line-name">
                    {{ $item->name }}
                    @if ($item->picks() !== [])
                        <span class="track-line-choices">{{ implode(', ', $item->picks()) }}</span>
                    @endif
                </span>
                <span class="track-line-total">{{ $currency }}{{ Price::format($item->line_total) }}</span>
            </li>
        @endforeach
    </ul>
    <p class="track-total">
        <span>{{ __('Total') }}</span>
        <span>{{ $currency }}{{ Price::format($order->total) }}</span>
    </p>
    @if ($order->note !== null)
        <p class="track-note"><strong>{{ __('Note') }}:</strong> {{ $order->note }}</p>
    @endif
</section>

<section class="track-card">
    @if ($delivery)
        <h3 class="track-section">{{ __('Delivery to') }}</h3>
        <p class="track-address">
            <span class="icon">{!! $icons['pin'] !!}</span>
            <span>{{ $order->address ?? '-' }}</span>
        </p>
    @elseif ($dineIn)
        <h3 class="track-section">{{ __('Served at') }}</h3>
        <p class="track-address">
            <span class="icon">{!! $icons['table'] !!}</span>
            <span>{{ $order->table_name ?? '-' }}</span>
        </p>
    @else
        <h3 class="track-section">{{ __('Pickup at') }}</h3>
        <p class="track-address">
            <span class="icon">{!! $icons['pin'] !!}</span>
            <span>{{ $name }}</span>
        </p>
    @endif
    <div class="track-actions">
        @if ($call_url)
            <a class="track-button" href="{{ $call_url }}">
                <span class="icon">{!! $icons['phone'] !!}</span>
                <span>{{ __('Call the restaurant') }}</span>
            </a>
        @endif
        @if (! $delivery && ! $dineIn && $restaurant->google_maps_url)
            <a class="track-button" href="{{ $restaurant->google_maps_url }}" target="_blank" rel="noopener">
                <span class="icon">{!! $icons['pin'] !!}</span>
                <span>{{ __('Directions') }}</span>
            </a>
        @endif
    </div>
</section>
