{{--
    Switch with a label and description, laid out in a row.
    Delegates to x-ui.switch for the toggle logic.
--}}
@props([
    'name'        => null,
    'checked'     => false,
    'label'       => null,
    'description' => null,
    'id'          => null,
])

<div class="ui-switch-row" {{ $attributes->except('class') }}>
    <div class="meta">
        @if ($label)
            <div class="lbl">{{ $label }}</div>
        @endif
        @if ($description)
            <div class="desc">{{ $description }}</div>
        @endif
        {{ $slot }}
    </div>
    <x-ui.switch :name="$name" :checked="$checked" :id="$id"/>
</div>
