{{--
    Filter chip / toggleable tag button.
    $active  – bool, whether currently selected
    $count   – optional count shown after label
    $onClick – Alpine expression or leave empty (parent manages state)
--}}
@props([
    'active'  => false,
    'count'   => null,
    'onClick' => null,
])

<button type="button"
        class="ui-tag {{ $active ? 'on' : '' }}"
        @if ($onClick) @click="{{ $onClick }}" @endif
        {{ $attributes->except('class') }}>
    {{ $slot }}
    @if ($count !== null)
        <span class="count">{{ $count }}</span>
    @endif
</button>
