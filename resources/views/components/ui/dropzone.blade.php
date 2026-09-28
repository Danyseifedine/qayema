{{--
    Single-image drop zone with server-side optimization.

    Flow:
      1. User drops/selects a file → local preview shown instantly
      2. File is POST'd to /temp-upload → MediaService::storeTempUpload() optimizes it → returns key + size stats
      3. Hidden input `{name}_key` carries the temp key on form submit

    Props:
      $name    : field name; hidden input becomes "{name}_key"
      $context : optimization profile: logo|cover_image|dish|category|generic
      $value   : existing image URL (shows preview on load)
      $hint    : format/size hint shown in the drop zone
--}}
@props([
    'name'    => null,
    'context' => 'generic',
    'value'   => null,
    'hint'    => null,
])

@php
$accept   = 'image/jpeg,image/png,image/jpg,image/webp';
$existing = $value ? (string) $value : null;
$dispName = $existing ? basename(parse_url($existing, PHP_URL_PATH)) : null;
$keyName  = $name ? $name.'_key' : null;
@endphp

<div x-data="{
         /* state: idle | uploading | done | error */
         status:    'idle',

         /* local preview data-url (shown immediately on file pick) */
         preview:   @js($existing),
         isExisting:@js((bool) $existing),
         dispName:  @js($dispName),

         /* set after server responds */
         key:          null,
         origSize:     null,
         optSize:      null,
         savedPct:     null,
         errorMsg:     null,

         isDragging:   false,

         get hasImage() { return this.preview !== null; },

         async handleFile(file) {
             if (!file) return;

             const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
             if (!allowed.includes(file.type)) {
                 this.status   = 'error';
                 this.errorMsg = '{{ __('owner.dropzone.invalid_type') }}';
                 this.preview  = null;
                 return;
             }

             /* show local preview instantly */
             const reader = new FileReader();
             reader.onload = e => { this.preview = e.target.result; };
             reader.readAsDataURL(file);

             this.dispName   = file.name;
             this.origSize   = file.size < 1_048_576
                 ? Math.round(file.size / 1024) + ' KB'
                 : (file.size / 1_048_576).toFixed(1) + ' MB';
             this.key        = null;
             this.optSize    = null;
             this.savedPct   = null;
             this.errorMsg   = null;
             this.isExisting = false;
             this.status     = 'uploading';

             try {
                 const fd = new FormData();
                 fd.append('file',    file);
                 fd.append('context', '{{ $context }}');
                 fd.append('_token',  document.querySelector('meta[name=csrf-token]').content);

                 const res = await fetch('{{ route('temp-upload') }}', {
                     method:  'POST',
                     headers: { 'Accept': 'application/json' },
                     body:    fd,
                 });

                 if (!res.ok) {
                     const err = await res.json().catch(() => ({}));
                     throw new Error(err.message ?? 'Upload failed ('+res.status+')');
                 }

                 const data = await res.json();
                 this.key      = data.key;
                 this.optSize  = data.optimized_size;
                 this.savedPct = data.saved_percent;
                 this.status   = 'done';

             } catch (e) {
                 this.status   = 'error';
                 this.errorMsg = e.message ?? '{{ __('owner.dropzone.upload_error') }}';
                 this.preview  = null;
                 this.dispName = null;
                 console.error('[dropzone]', e);
             }
         },

         remove() {
             this.status     = 'idle';
             this.preview    = null;
             this.key        = null;
             this.dispName   = null;
             this.origSize   = null;
             this.optSize    = null;
             this.savedPct   = null;
             this.errorMsg   = null;
             this.isExisting = false;
             this.isDragging = false;
         },
     }">

    {{-- ── Drop zone (shown when no image) ─── --}}
    <label x-show="!hasImage"
           :class="'ui-uploader' + (isDragging ? ' drag' : '') + (status === 'uploading' ? ' uploading' : '')"
           @dragover.prevent="isDragging = true"
           @dragleave.prevent="isDragging = false"
           @drop.prevent="isDragging = false; handleFile($event.dataTransfer.files[0])">

        {{-- Idle state --}}
        <template x-if="status !== 'uploading' && status !== 'error'">
            <div style="display:contents">
                <div class="up-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 15V3M7 8l5-5 5 5M5 17v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3"/>
                    </svg>
                </div>
                <div class="up-title">{{ __('owner.dropzone.title') }}</div>
                <div class="up-meta">{{ $hint }}</div>
            </div>
        </template>

        {{-- Uploading / optimizing --}}
        <template x-if="status === 'uploading'">
            <div style="display:contents">
                <div class="up-spinner"></div>
                <div class="up-title" style="color:var(--olive-deep)">{{ __('owner.dropzone.optimizing') }}</div>
                <div class="up-meta" x-text="'Original: ' + (origSize ?? '-')"></div>
            </div>
        </template>

        {{-- Error --}}
        <template x-if="status === 'error'">
            <div style="display:contents">
                <div class="up-icon" style="background:var(--danger)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                    </svg>
                </div>
                <div class="up-title" style="color:var(--danger)" x-text="errorMsg"></div>
                <div class="up-meta">{{ __('owner.dropzone.retry') }}</div>
            </div>
        </template>

        <input type="file"
               @if ($name) id="{{ $name }}" @endif
               accept="{{ $accept }}"
               @change="handleFile($event.target.files[0])">
    </label>

    {{-- ── Preview card (shown when image is loaded/done) ─── --}}
    <div x-show="hasImage" x-cloak class="ui-preview">
        <div class="pv-thumb" :style="`background-image:url(${preview})`"></div>

        <div class="pv-body">
            <div>
                <div class="pv-name" x-text="dispName ?? 'Image'"></div>
                <div class="pv-meta">
                    {{-- Uploading indicator --}}
                    <template x-if="status === 'uploading'">
                        <span style="display:inline-flex;align-items:center;gap:6px;color:var(--muted)">
                            <span class="up-spinner" style="width:14px;height:14px;border-width:2px"></span>
                            Optimizing…
                        </span>
                    </template>

                    {{-- Done: show size savings --}}
                    <template x-if="status === 'done' || isExisting">
                        <span style="display:contents">
                            <span x-show="origSize" x-text="origSize"
                                  style="text-decoration:line-through;color:var(--muted-2)"></span>
                            <span x-show="origSize" class="dot-sep"></span>
                            <span x-text="isExisting ? '{{ __('owner.dropzone.uploaded') }}' : (optSize ?? '')"></span>
                            <template x-if="savedPct > 0">
                                <span class="dot-sep"></span>
                            </template>
                            <template x-if="savedPct > 0">
                                <span class="pv-savings">↓ <span x-text="savedPct + '%'"></span> {{ __('owner.dropzone.smaller') }}</span>
                            </template>
                        </span>
                    </template>
                </div>
            </div>

            <div class="pv-bar">
                <span :style="status === 'uploading' ? 'width:60%;animation:ui-spin 1s linear infinite' : 'width:100%'"></span>
            </div>

            <div class="pv-actions">
                <label class="pv-btn" style="cursor:pointer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 15V3M7 8l5-5 5 5M5 17v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3"/>
                    </svg>
                    {{ __('owner.dropzone.replace') }}
                    <input type="file" accept="{{ $accept }}" style="display:none"
                           @change="handleFile($event.target.files[0])">
                </label>
                <button type="button" class="pv-btn danger" @click="remove()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 7h16M9 7V4h6v3M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13M10 11v6M14 11v6"/>
                    </svg>
                    {{ __('owner.dropzone.remove') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ── Hidden inputs for form submission ─── --}}

    {{-- Temp key: present when a new file was successfully uploaded --}}
    @if ($keyName)
        <template x-if="key">
            <input type="hidden" name="{{ $keyName }}" :value="key">
        </template>
    @endif
</div>
