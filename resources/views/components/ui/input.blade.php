@props([
    'name'         => null,
    'id'           => null,
    'type'         => 'text',
    'placeholder'  => null,
    'value'        => null,
    'icon'         => null,
    'prefix'       => null,
    'trail'        => null,
    'state'        => null,
    'required'     => false,
    'disabled'     => false,
    'autofocus'    => false,
    'autocomplete' => null,
    'reveal'       => false,
])

@php
    $inputId   = $id ?? $name;
    $autoState = $name && $errors->has($name) ? 'error' : null;
    $resolvedState = $state ?? $autoState;
    $wrapClass = trim('ui-input-wrap ' . ($resolvedState ?? ''));
    $isDisabled = $disabled || $resolvedState === 'disabled';
@endphp

<div class="{{ $wrapClass }}"
     @if ($prefix || $type === 'number') dir="ltr" @endif
     @if ($reveal) x-data="{ showPass: false }" @endif>

    @if ($icon)
        <span class="lead-ico">{!! $icon !!}</span>
    @endif

    @if ($prefix)
        <span class="prefix">{{ $prefix }}</span>
    @endif

    <input
        @if ($inputId)    id="{{ $inputId }}"       @endif
        @if ($name)       name="{{ $name }}"         @endif
        @if ($reveal)     :type="showPass ? 'text' : 'password'"
        @else             type="{{ $type }}"          @endif
        @if ($type === 'number') dir="ltr" @endif
        class="ui-input{{ $prefix ? ' with-prefix' : '' }}"
        @if ($placeholder)   placeholder="{{ $placeholder }}"         @endif
        @if ($name !== null) value="{{ old($name ?? '', $value) }}"   @endif
        @if ($autofocus)     autofocus                                  @endif
        @if ($autocomplete)  autocomplete="{{ $autocomplete }}"        @endif
        @if ($required)      required                                   @endif
        @if ($isDisabled)    disabled                                   @endif
        {{ $attributes->except(['class','type','name','id','value','placeholder','autocomplete']) }}
    >

    @if ($trail)
        <span class="trail">{{ $trail }}</span>
    @endif

    @if ($reveal)
        <button type="button" class="trail-btn" @click="showPass = !showPass"
                :aria-label="showPass ? 'Hide password' : 'Show password'">
            {{-- eye open --}}
            <svg x-show="!showPass" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            {{-- eye off --}}
            <svg x-show="showPass" x-cloak viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/>
                <path d="M9.9 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a16.4 16.4 0 0 1-3.2 4"/>
                <path d="M6.6 6.6A16.5 16.5 0 0 0 2 12s3.5 7 10 7c1.4 0 2.6-.2 3.7-.6"/>
            </svg>
        </button>
    @endif

    {{ $slot ?? '' }}
</div>
