@props([
    'label'    => null,
    'name'     => null,
    'required' => false,
    'optional' => null,
    'help'     => null,
    'error'    => null,
    'ok'       => null,
    'id'       => null,
])

@php
    $forId  = $id ?? $name;
    $errMsg = $error ?? ($name && $errors->has($name) ? $errors->first($name) : null);
@endphp

<div class="ui-field">
    @if ($label)
        <label class="ui-label" @if ($forId) for="{{ $forId }}" @endif>
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
    @elseif ($ok)
        <div class="ui-help ok">{!! $ok !!}</div>
    @elseif ($help)
        <div class="ui-help">{!! $help !!}</div>
    @endif
</div>
