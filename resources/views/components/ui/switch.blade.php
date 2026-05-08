{{--
    Toggle switch. Manages its own Alpine state.
    Emits a hidden input for form submission (value "1" when on, "0" when off).
    For Livewire, pass wire:model on the parent and handle x-model instead.
--}}
@props([
    'name'    => null,
    'checked' => false,
    'id'      => null,
])

<div x-data="{ on: @js((bool) $checked) }">
    <button
        type="button"
        class="ui-switch"
        :class="on ? 'on' : ''"
        @click="on = !on"
        :aria-checked="on ? 'true' : 'false'"
        role="switch"
        @if ($id) id="{{ $id }}" @endif
        {{ $attributes->except(['class','id']) }}
    ></button>

    @if ($name)
        <input type="hidden" name="{{ $name }}" :value="on ? '1' : '0'">
    @endif
</div>
