@props([
    'label'    => null,
    'name'     => null,
    'required' => false,
    'optional' => null,
])

@php
    $errMsg = $name && $errors->has($name) ? $errors->first($name) : null;
@endphp

<div class="ui-field">
    @if ($label)
        <label class="ui-label" @if ($name) for="{{ $name }}" @endif>
            <span>
                {!! $label !!}
                @if ($required) <span class="req">*</span> @endif
            </span>
            @if ($optional)
                <span class="opt">{{ $optional }}</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($errMsg)
        <div class="ui-help error">{!! $errMsg !!}</div>
    @endif
</div>
