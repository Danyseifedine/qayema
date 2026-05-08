{{--
    Checkbox with optional label and description.
    $olive – use olive-green checked colour instead of dark ink
--}}
@props([
    'name'        => null,
    'id'          => null,
    'value'       => '1',
    'checked'     => false,
    'label'       => null,
    'description' => null,
    'olive'       => false,
    'required'    => false,
])

@php $inputId = $id ?? ($name ? 'cb_' . $name . '_' . $value : null); @endphp

<label class="ui-check {{ $olive ? 'olive' : '' }}" @if ($inputId) for="{{ $inputId }}" @endif>
    <input
        type="checkbox"
        @if ($inputId) id="{{ $inputId }}" @endif
        @if ($name)    name="{{ $name }}"  @endif
        value="{{ $value }}"
        @if ($checked || old($name) == $value) checked @endif
        @if ($required) required @endif
        {{ $attributes->except(['class','type','name','id','value','checked']) }}
    >
    <span class="box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6L9 17l-5-5"/>
        </svg>
    </span>
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
