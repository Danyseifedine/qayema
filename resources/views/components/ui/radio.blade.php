{{--
    Radio button with optional label and description.
--}}
@props([
    'name'        => null,
    'id'          => null,
    'value'       => null,
    'checked'     => false,
    'label'       => null,
    'description' => null,
    'required'    => false,
])

@php $inputId = $id ?? ($name && $value !== null ? 'rb_' . $name . '_' . $value : null); @endphp

<label class="ui-radio" @if ($inputId) for="{{ $inputId }}" @endif>
    <input
        type="radio"
        @if ($inputId) id="{{ $inputId }}"   @endif
        @if ($name)    name="{{ $name }}"     @endif
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($checked || old($name) == $value) checked @endif
        @if ($required) required @endif
        {{ $attributes->except(['class','type','name','id','value','checked']) }}
    >
    <span class="ring"></span>
    @if ($label || $description || $slot->isNotEmpty())
        <span class="meta">
            @if ($label)
                <span>{{ $label }}</span>
            @endif
            @if ($description)
                <span class="desc">{{ $description }}</span>
            @endif
            {{ $slot }}
        </span>
    @endif
</label>
