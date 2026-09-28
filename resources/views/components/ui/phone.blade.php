{{--
    Phone input with searchable country-code picker.

    Props:
      $name    : phone number field name
      $ccName  : country code field name (stores ISO value e.g. 'LB')
      $value   : initial phone number
      $ccValue : initial country ISO code (default 'LB')

    Usage:
      <x-ui.phone name="phone" cc-name="country_code"
                  :value="$restaurant?->phone"
                  :cc-value="$restaurant?->country_code ?? 'LB'" />
--}}
@props([
    'name'    => 'phone',
    'ccName'  => 'country_code',
    'value'   => null,
    'ccValue' => 'LB',
])

@php
$list = collect(config('countries', []))
    ->map(fn (array $country, string $code): array => [
        'value' => $code,
        'label' => $country['label'],
        'flag' => $country['flag'],
        'meta' => $country['dial'],
    ])
    ->values()
    ->all();
@endphp

<div class="ui-phone-wrap"
     x-data="{
         countries: @js($list),
         cc:        @js(old($ccName, $ccValue)),
         q:         '',
         open:      false,
         get selected() {
             return this.countries.find(c => c.value === this.cc) ?? this.countries[0];
         },
         get filtered() {
             const q = this.q.toLowerCase();
             if (!q) return this.countries;
             return this.countries.filter(c =>
                 c.label.toLowerCase().includes(q) ||
                 c.meta.toLowerCase().includes(q)  ||
                 c.value.toLowerCase().includes(q)
             );
         },
         pick(c) { this.cc = c.value; this.q = ''; this.open = false; },
     }"
     @click.outside="open = false">

    {{-- ── Country code selector ──── --}}
    <div class="ui-phone-cc">
        <button type="button" class="ui-phone-trigger" @click="open = !open"
                :aria-expanded="open" aria-haspopup="listbox">
            <span class="flag" x-text="selected.flag"></span>
            <span class="dial" x-text="selected.meta"></span>
            <svg class="ui-phone-caret" :class="open ? 'open' : ''"
                 width="12" height="12" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </button>

        {{-- Hidden input stores the ISO code --}}
        <input type="hidden" name="{{ $ccName }}" :value="cc">

        {{-- Dropdown --}}
        <div class="ui-phone-dropdown" x-show="open" x-cloak role="listbox">
            <div class="ui-phone-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                </svg>
                <input x-model="q" type="text"
                       placeholder="Search country or code…"
                       @click.stop
                       autocomplete="off">
            </div>
            <div class="ui-phone-list">
                <template x-for="c in filtered" :key="c.value">
                    <button type="button" class="ui-phone-item"
                            :class="cc === c.value ? 'active' : ''"
                            @click.stop="pick(c)"
                            role="option" :aria-selected="cc === c.value">
                        <span class="flag" x-text="c.flag"></span>
                        <span class="name" x-text="c.label"></span>
                        <span class="code" x-text="c.meta"></span>
                    </button>
                </template>
                <div x-show="filtered.length === 0" class="ui-phone-empty">
                    No matches
                </div>
            </div>
        </div>
    </div>

    {{-- ── Phone number input ──── --}}
    <input
        type="tel"
        inputmode="tel"
        maxlength="30"
        name="{{ $name }}"
        class="ui-phone-num"
        placeholder="70 123 456"
        value="{{ old($name, $value) }}"
        oninput="this.value = this.value.replace(/[^0-9+()\s.\-]/g, '')"
        {{ $attributes->except(['class','type','name','placeholder']) }}
    >
</div>
