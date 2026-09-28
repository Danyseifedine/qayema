{{--
    Searchable single select powered by Alpine.js.

    Props:
      $options     : [['value'=>'', 'label'=>'', 'flag'=>'', 'meta'=>'']]
      $value       : initial selected value
      $placeholder : shown while nothing is selected
      $name        : form field name (hidden input emitted for submission)
      $up          : open the list above the field
--}}
@props([
    'name'        => null,
    'options'     => [],
    'value'       => null,
    'placeholder' => 'Select option',
    'up'          => false,
])

<div class="ui-combo"
     x-data="{
         options:  @js($options),
         val:      @js($value ?? ''),
         open:     false,
         q:        '',

         get filteredFlat() {
             const q = this.q.toLowerCase();
             const flat = q
                 ? this.options.filter(o => (o.label || o.value || '').toLowerCase().includes(q))
                 : this.options;
             return flat.length === 0
                 ? [{ value: '__empty__', label: 'No matches for &quot;' + this.q + '&quot;', _empty: true }]
                 : flat;
         },

         get selectedItem() {
             return this.options.find(o => o.value === this.val) || null;
         },

         toggle(o) {
             if (o._empty) return;
             this.val  = o.value;
             this.open = false;
             this.q = '';
             this.$nextTick(() => this.$el.dispatchEvent(
                 new CustomEvent('combo-change', { detail: { value: this.val }, bubbles: true })
             ));
         },

         clear() { this.val = ''; this.q = ''; },
     }"
     :class="open ? 'open' : ''"
     @click.outside="open = false; q = ''">

    {{-- Trigger --}}
    <div class="ui-combo-control" @click="open = !open">

        {{-- Selected display --}}
        <template x-if="selectedItem">
            <span style="font-size:14.5px;padding:6px 4px;color:var(--ink);display:inline-flex;align-items:center;gap:6px">
                <template x-if="selectedItem && selectedItem.flag">
                    <span x-text="selectedItem.flag" style="font-size:13px"></span>
                </template>
                <span x-text="selectedItem ? (selectedItem.label || selectedItem.value) : ''"></span>
            </span>
        </template>

        {{-- Placeholder display when nothing is selected --}}
        <template x-if="!selectedItem && !q">
            <span style="font-size:14.5px;padding:6px 4px;color:rgba(15,15,16,.38);pointer-events:none;user-select:none">{{ $placeholder }}</span>
        </template>

        <input class="ui-combo-input"
               x-model="q"
               @focus="open = true"
               @click.stop="open = true">

        <span class="ui-combo-caret">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </span>
    </div>

    {{-- Hidden form input --}}
    @if ($name)
        <input type="hidden" name="{{ $name }}" :value="val">
    @endif

    {{-- Dropdown list --}}
    <div class="ui-menu {{ $up ? 'up' : '' }}" x-show="open" x-cloak>
        <div class="ui-menu-list">
            <template x-for="row in filteredFlat" :key="row.value">
                <div>
                    <div x-show="!row._empty"
                         class="ui-menu-item"
                         :class="val === row.value ? 'selected' : ''"
                         @click="toggle(row)">
                        <template x-if="row.flag">
                            <span class="item-flag" x-text="row.flag"></span>
                        </template>
                        <span style="flex:1" x-text="row.label || row.value"></span>
                        <template x-if="row.meta">
                            <span class="item-meta" x-text="row.meta"></span>
                        </template>
                        <span class="ui-menu-tick">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                        </span>
                    </div>
                    <div x-show="row._empty" class="ui-menu-empty" x-text="row.label"></div>
                </div>
            </template>
        </div>
        <div class="ui-menu-foot">
            <span>Pick one</span>
            <template x-if="val">
                <button class="clear" type="button" @click.stop="clear()">Clear</button>
            </template>
        </div>
    </div>
</div>
