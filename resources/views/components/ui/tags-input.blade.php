{{--
    Free-form tag input with type-ahead suggestions.
    Press Enter or comma to add. Backspace removes the last tag.

    Props:
      $name        – form field name (submits as name[])
      $value       – initial tags array
      $placeholder – input placeholder
      $suggestions – clickable suggestion chips shown below
      $toneMap     – cycle of chip variant classes e.g. ['olive','clay','gold','']
--}}
@props([
    'name'        => null,
    'value'       => [],
    'placeholder' => 'Type a tag and press enter…',
    'suggestions' => [],
    'toneMap'     => ['olive', 'clay', 'gold', ''],
])

<div x-data="{
         tags:        @js($value ?? []),
         draft:       '',
         suggestions: @js($suggestions ?? []),
         toneMap:     @js($toneMap),

         tone(i) { return this.toneMap[i % this.toneMap.length] ?? ''; },

         add(t) {
             const v = t.trim().replace(/,/g, '');
             if (!v || this.tags.includes(v)) return;
             this.tags.push(v);
             this.draft = '';
         },

         remove(t) { this.tags = this.tags.filter(x => x !== t); },

         onKey(e) {
             if ((e.key === 'Enter' || e.key === ',') && this.draft.trim()) {
                 e.preventDefault();
                 this.add(this.draft);
             }
             if (e.key === 'Backspace' && !this.draft && this.tags.length) {
                 this.remove(this.tags[this.tags.length - 1]);
             }
         },

         get remaining() {
             return this.suggestions.filter(s => !this.tags.includes(s));
         },
     }">

    {{-- Input area --}}
    <div class="ui-tags-wrap" @click="$el.querySelector('input').focus()">
        <template x-for="(tag, i) in tags" :key="tag">
            <span :class="'ui-chip ' + tone(i)">
                <span x-text="tag"></span>
                <button class="x" type="button" @click.stop="remove(tag)" aria-label="Remove">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M6 6l12 12M6 18L18 6"/>
                    </svg>
                </button>
            </span>
        </template>

        <input
            x-model="draft"
            @keydown="onKey($event)"
            :placeholder="tags.length === 0 ? '{{ $placeholder }}' : ''">
    </div>

    {{-- Suggestion chips --}}
    <template x-if="remaining.length > 0">
        <div class="ui-tags-suggestions">
            <template x-for="s in remaining.slice(0, 8)" :key="s">
                <button class="ui-tag" type="button" @click="add(s)"
                        style="font-size:12px;padding:4px 10px">
                    + <span x-text="s"></span>
                </button>
            </template>
        </div>
    </template>

    {{-- Hidden inputs for form submission --}}
    @if ($name)
        <template x-for="tag in tags" :key="tag">
            <input type="hidden" name="{{ $name }}[]" :value="tag">
        </template>
    @endif
</div>
