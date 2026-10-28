{{--
    One offer as a card: in the banner, the pop-up or the ad (`$variant`),
    in the shape it takes there (Offer::shapeIn): a rectangle, a square or
    tall, the picture filling it and the words over its foot. A picture
    alone (its words drawn into it) is the whole card and the card is the
    tap; words without a picture wear a faint mark instead. It never shows
    the discount itself (the dishes it takes off show their offer price). Hidden unless it was shown when the page was drawn
    (live, or inside its dates when it shows outside its hours);
    menu-offers.js shows and hides it as its schedule says, and handles a
    tap on it (`data-offer-go`; outside its hours a tap says when it runs).

    The picture comes in the size the guest's screen needs (`srcset`), over
    a blurred copy of its small version that shows while it loads. One far
    from its shape's proportions shows whole on that blurred copy instead
    of cropped (Offer::showsPictureWhole(); unknown for an older upload,
    menu-offers.js judges it once loaded).

    Expects $offer, $variant, $live (bool, shown now), $text (the menu's reader),
    and $eager for a card seen at once (the first banner); a
    pop-up or an ad is never hidden here (its script opens it).
--}}
@use('App\Enums\OfferPlacement')
@php
    $title = $text($offer, 'title');
    $about = $text($offer, 'description');
    $shape = $offer->shapeIn(OfferPlacement::from($variant));
    $media = $offer->getFirstMedia('image');
    $picture = $media?->getAvailableUrl(['wide']);
    $sharper = $media?->hasGeneratedConversion('wide') && $media->hasGeneratedConversion('large')
        ? $media->getUrl('wide').' 960w, '.$media->getUrl('large').' 1800w'
        : null;
    // How wide the card is drawn, for the phone to pick the size it needs.
    $drawn = match ($variant) {
        'popup' => 'min(380px, calc(100vw - 32px))',
        'ad' => 'min(440px, calc(100vw - 24px))',
        default => '(min-width: 1024px) 680px, calc(100vw - 32px)',
    };
    $whole = $picture ? $offer->showsPictureWhole($shape) : null;
    // A picture shown whole keeps its own proportions where it has room (a wide screen's banner strip).
    $ratio = $whole ? $offer->pictureRatio() : null;
    $alone = $offer->isPictureOnly() && $picture;
    $overlay = in_array($variant, ['popup', 'ad'], true);
    $link = $offer->action === 'link' && $offer->action_url;
    $dishes = $offer->action === 'dishes' && $offer->dishes->isNotEmpty();
@endphp
<article @class(['offer', 'offer-'.$variant, 'offer-shape-'.$shape, 'offer-has-photo' => $picture, 'offer-picture-only' => $alone, 'offer-fit-whole' => $whole === true]) data-offer="{{ $offer->id }}"@if ($ratio) style="--picture: {{ round($ratio, 4) }}"@endif @if (! $overlay && ! $live) hidden @endif>
    @if ($picture)
        @php $loading = $overlay || ($eager ?? false) ? 'eager' : 'lazy'; @endphp
        <span class="offer-photo-slot">
            <img class="offer-photo-blur" src="{{ $media->getAvailableUrl(['thumb']) }}" alt="" aria-hidden="true" loading="{{ $loading }}" decoding="async">
            <img class="offer-photo" src="{{ $picture }}" @if ($sharper) srcset="{{ $sharper }}" sizes="{{ $drawn }}" @endif alt="{{ $alone && ! $link && ! $dishes ? $title : '' }}" loading="{{ $loading }}" decoding="async" @if ($whole === null) data-fit="auto" @endif>
        </span>
    @endif
    @unless ($picture)
        {{-- Words alone: a large faint mark in the far corner, so the card
             looks made that way rather than missing its picture. --}}
        <span class="offer-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg></span>
    @endunless
    @if ($alone)
        @if ($link)
            <a class="offer-cover" href="{{ $offer->action_url }}" target="_blank" rel="noopener nofollow" data-offer-go aria-label="{{ $title !== '' ? $title : __('Learn more') }}"></a>
        @elseif ($dishes)
            <button type="button" class="offer-cover" data-offer-go aria-label="{{ $title !== '' ? $title : __('See the dishes') }}"></button>
        @endif
    @else
        <div class="offer-body">
            <h3 class="offer-title">{{ $title }}</h3>
            @if ($about)
                <p class="offer-text">{{ $about }}</p>
            @endif
            @if ($link)
                <a class="offer-go" href="{{ $offer->action_url }}" target="_blank" rel="noopener nofollow" data-offer-go>{{ __('Learn more') }}</a>
            @elseif ($dishes)
                <button type="button" class="offer-go" data-offer-go>{{ __('See the dishes') }}</button>
            @endif
        </div>
    @endif
</article>
