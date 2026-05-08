@props([
    'name'     => null,
    'id'       => null,
    'value'    => null,
    'required' => false,
    'disabled' => false,
])

@php $inputId = $id ?? $name; @endphp

<select
    @if ($inputId) id="{{ $inputId }}" @endif
    @if ($name)    name="{{ $name }}"  @endif
    class="ui-select"
    @if ($required) required  @endif
    @if ($disabled) disabled  @endif
    {{ $attributes->except(['class','name','id']) }}
>
    {{ $slot }}
</select>
