{{--
    Status badge.
    Variants: live | draft | danger | warn | gold
    $pip: show the coloured dot before the label
--}}
@props([
    'variant' => 'live',
    'pip'     => true,
])

<span class="ui-badge {{ $variant }}" {{ $attributes->except('class') }}>
    @if ($pip && in_array($variant, ['live','draft','danger','warn']))
        <span class="pip"></span>
    @endif
    {{ $slot }}
</span>
