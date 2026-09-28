{{--
    Checkbox with its label in the slot, checked in the gold accent.
--}}
@props([
    'name'    => null,
    'checked' => false,
])

@php $inputId = $name ? 'cb_' . $name . '_1' : null; @endphp

<label class="ui-check olive" @if ($inputId) for="{{ $inputId }}" @endif>
    <input
        type="checkbox"
        @if ($inputId) id="{{ $inputId }}" @endif
        @if ($name)    name="{{ $name }}"  @endif
        value="1"
        @if ($checked || old($name) == '1') checked @endif
        {{ $attributes->except(['class','type','name','id','value','checked']) }}
    >
    <span class="box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6L9 17l-5-5"/>
        </svg>
    </span>
    @if ($slot->isNotEmpty())
        <span class="meta">
            {{ $slot }}
        </span>
    @endif
</label>
