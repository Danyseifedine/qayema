{{--
    Searchable select (Select2-style) powered by Alpine.js.

    Props:
      $options     â€“ flat array: [['value'=>'', 'label'=>'', 'flag'=>'', 'meta'=>'']]
                     grouped array: [['label'=>'Group', 'items'=>[...]]]
      $value       â€“ initial selected value (string) or array of values (multi)
      $multiple    â€“ bool, allow multiple selection
      $grouped     â€“ bool, $options are grouped
      $placeholder â€“ input placeholder text
      $name        â€“ form field name (hidden input emitted for submission)
      $searchable  â€“ bool (default true)
--}}
@props([
    'name'        => null,
    'options'     => [],
    'value'       => null,
    'multiple'    => false,
    'grouped'     => false,
    'placeholder' => 'Select option',
    'searchable'  => true,
])

@php
    $initVal = $multiple ? ($value ?? []) : ($value ?? '');
@endphp

<div class="ui-combo"
     x-data="{
         options:  @js($options),
         grouped:  @js($grouped),
         multiple: @js($multiple),
         val:      @js($initVal),
         open:     false,
         q:        '',

         get flat() {
             if (!this.grouped) return this.options;
             return this.options.flatMap(g => (g.items || []).map(i => ({ ...i, _group: g.label })));
         },

         get filteredFlat() {
             const q = this.q.toLowerCase();
             if (this.grouped) {
                 const rows = [];
                 for (const g of this.options) {
                     const items = (g.items || []).filter(i =>
                         !q || (i.label || i.value || '').toLowerCase().includes(q)
                     );
                     if (items.length) {
                         rows.push({ isGroup: true,  label: g.label, value: '__g__' + g.label });
                         items.forEach(i => rows.push({ isGroup: false, ...i }));
                     }
                 }
                 return rows;
             }
             const flat = q
                 ? this.options.filter(o => (o.label || o.value || '').toLowerCase().includes(q))
                 : this.options;
             return flat.length === 0
                 ? [{ isGroup: false, value: '__empty__', label: 'No matches for &quot;' + this.q + '&quot;', _empty: true }]
                 : flat.map(o => ({ isGroup: false, ...o }));
         },

         get selectedItems() {
             if (this.multiple) return this.flat.filter(o => (this.val || []).includes(o.value));
             return this.flat.find(o => o.value === this.val) || null;
         },

         isSel(v) {
             return this.multiple ? (this.val || []).includes(v) : this.val === v;
         },

         toggle(o) {
             if (o._empty || o.isGroup) return;
             if (this.multiple) {
                 const v = this.val || [];
                 this.val = v.includes(o.value) ? v.filter(x => x !== o.value) : [...v, o.value];
             } else {
                 this.val  = o.value;
                 this.open = false;
             }
             this.q = '';
         },

         clear() { this.val = this.multiple ? [] : ''; this.q = ''; },
     }"
     :class="open ? 'open' : ''"
     @click.outside="open = false">

    {{-- â”€â”€ Trigger â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="ui-combo-control" @click="open = !open">

        {{-- Selected chips (multi) --}}
        <template x-if="multiple">
            <template x-for="item in selectedItems" :key="item.value">
                <span class="ui-chip olive">
                    <template x-if="item.flag">
                        <span x-text="item.flag" style="font-size:13px"></span>
                    </template>
                    <span x-text="item.label || item.value"></span>
                    <button class="x" type="button" @click.stop="toggle(item)" aria-label="Remove">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M6 6l12 12M6 18L18 6"/>
                        </svg>
                    </button>
                </span>
            </template>
        </template>

        {{-- Selected display (single) --}}
        <template x-if="!multiple && selectedItems">
            <span style="font-size:14.5px;padding:6px 4px;color:var(--ink);display:inline-flex;align-items:center;gap:6px">
                <template x-if="selectedItems && selectedItems.flag">
                    <span x-text="selectedItems.flag" style="font-size:13px"></span>
                </template>
                <span x-text="selectedItems ? (selectedItems.label || selectedItems.value) : ''"></span>
            </span>
        </template>

        {{-- Placeholder display when nothing selected (single mode) --}}
        <template x-if="!multiple && !selectedItems && !q">
            <span style="font-size:14.5px;padding:6px 4px;color:rgba(15,15,16,.38);pointer-events:none;user-select:none">{{ $placeholder }}</span>
        </template>

        @if ($searchable)
            <input class="ui-combo-input"
                   x-model="q"
                   @focus="open = true"
                   @click.stop="open = true">
        @else
            <input class="ui-combo-input"
                   readonly
                   style="cursor:pointer">
        @endif

        <span class="ui-combo-caret">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </span>
    </div>

    {{-- â”€â”€ Hidden form inputs â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    @if ($name)
        <template x-if="!multiple">
            <input type="hidden" name="{{ $name }}" :value="val">
        </template>
        <template x-if="multiple">
            <template x-for="v in (val || [])" :key="v">
                <input type="hidden" name="{{ $name }}[]" :value="v">
            </template>
        </template>
    @endif

    {{-- â”€â”€ Dropdown list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <div class="ui-menu" x-show="open" x-cloak>
        <div class="ui-menu-list">
            <template x-for="row in filteredFlat" :key="row.value">
                <div>
                    <div x-show="row.isGroup" class="ui-menu-group" x-text="row.label"></div>
                    <div x-show="!row.isGroup && !row._empty"
                         class="ui-menu-item"
                         :class="isSel(row.value) ? 'selected' : ''"
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
            <span x-text="multiple ? (val || []).length + ' selected' : 'Pick one'"></span>
            <template x-if="multiple ? (val || []).length > 0 : val">
                <button class="clear" type="button" @click.stop="clear()">Clear</button>
            </template>
        </div>
    </div>
</div>

