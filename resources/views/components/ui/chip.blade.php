{{--
    Chip / removable tag.
    Variants: (default dark) | olive | clay | gold
    $onRemove: Alpine expression called on remove click, e.g. "removeTag('x')"
--}}
@props([
    'variant'  => '',
    'onRemove' => null,
])

<span class="ui-chip {{ $variant }} {{ $onRemove ? '' : 'no-remove' }}" {{ $attributes->except('class') }}>
    {{ $slot }}
    @if ($onRemove)
        <button class="x" type="button" @click.stop="{{ $onRemove }}" aria-label="Remove">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M6 6l12 12M6 18L18 6"/>
            </svg>
        </button>
    @endif
</span>
