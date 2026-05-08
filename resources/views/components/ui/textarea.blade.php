@props([
    'name'        => null,
    'id'          => null,
    'placeholder' => null,
    'value'       => null,
    'maxlength'   => null,
    'rows'        => null,
    'required'    => false,
    'disabled'    => false,
    'showCount'   => false,
])

@php
    $inputId = $id ?? $name;
    $current = old($name ?? '', $value ?? '');
@endphp

@if ($showCount && $maxlength)
    <div x-data="{ len: {{ mb_strlen($current) }} }">
@endif

<textarea
    @if ($inputId)  id="{{ $inputId }}"            @endif
    @if ($name)     name="{{ $name }}"              @endif
    class="ui-textarea"
    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
    @if ($maxlength)   maxlength="{{ $maxlength }}"      @endif
    @if ($rows)        rows="{{ $rows }}"                @endif
    @if ($required)    required                          @endif
    @if ($disabled)    disabled                          @endif
    @if ($showCount && $maxlength) @input="len = $event.target.value.length" @endif
    {{ $attributes->except(['class','name','id','placeholder','maxlength','rows']) }}
>{{ $current }}</textarea>

@if ($showCount && $maxlength)
    <div class="ui-help" style="display:flex;justify-content:flex-end;font-feature-settings:'tnum'">
        <span x-text="len"></span> / {{ $maxlength }}
    </div>
    </div>
@endif
