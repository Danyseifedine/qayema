{{--
    Segmented control (pill tabs).
    $options – array of ['value' => '...', 'label' => '...']
    $value   – initial active value
    $name    – hidden input name for form submission
--}}
@props([
    'name'    => null,
    'options' => [],
    'value'   => null,
])

@php
    $initial = $value ?? ($options[0]['value'] ?? '');
@endphp

<div x-data="{ active: @js($initial) }">
    <div class="ui-seg">
        @foreach ($options as $opt)
            <button
                type="button"
                :class="active === '{{ $opt['value'] }}' ? 'on' : ''"
                @click="active = '{{ $opt['value'] }}'">
                {{ $opt['label'] }}
            </button>
        @endforeach
    </div>

    @if ($name)
        <input type="hidden" name="{{ $name }}" :value="active">
    @endif
</div>
