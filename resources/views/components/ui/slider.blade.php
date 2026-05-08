{{--
    Range slider with live value display.
    The CSS custom property --p drives the olive fill track.
--}}
@props([
    'name'      => null,
    'id'        => null,
    'min'       => 0,
    'max'       => 100,
    'value'     => 50,
    'step'      => 1,
    'showValue' => true,
    'unit'      => '%',
])

@php
    $inputId = $id ?? $name;
    $initial = old($name ?? '', $value);
    $pct     = $max > $min ? round(($initial - $min) / ($max - $min) * 100) : 0;
@endphp

<div x-data="{ val: {{ (int) $initial }}, pct() { return Math.round((this.val - {{ $min }}) / ({{ $max }} - {{ $min }}) * 100); } }">
    <input
        type="range"
        @if ($inputId) id="{{ $inputId }}" @endif
        @if ($name)    name="{{ $name }}"  @endif
        class="ui-slider"
        min="{{ $min }}"
        max="{{ $max }}"
        step="{{ $step }}"
        x-model="val"
        :style="`--p: ${pct()}%`"
        {{ $attributes->except(['class','type','min','max','step','name','id']) }}
    >
    @if ($showValue)
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--muted);margin-top:6px">
            <span>{{ $min }}{{ $unit }}</span>
            <span style="font-family:var(--font-display);font-style:italic;color:var(--olive-deep)"
                  x-text="val + '{{ $unit }}'"></span>
            <span>{{ $max }}{{ $unit }}</span>
        </div>
    @endif
</div>
