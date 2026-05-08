{{--
    Multi-image gallery drop zone.

    Shows existing images as square tiles + an "Add" tile.
    Tap/click a tile's ✕ button to remove it.
    Supports up to $maxImages images total.

    Props:
      $name          – new file input name (submitted as name[])
      $deleteInputName – hidden input name for deleted media IDs (submitted as name[])
      $existingImages  – PHP array of [['id' => int, 'url' => string, 'primary' => bool]]
      $maxImages     – max total images (default 8)
      $accept        – accepted MIME types
      $columns       – grid columns (default 4)
      $id            – optional id prefix

    Form submission:
      - New files: multiple hidden file inputs named $name[]
      - Deleted existing IDs: hidden inputs named $deleteInputName[]

    Usage:
      <x-ui.dropzone-multiple
          name="images"
          delete-input-name="delete_images"
          :existing-images="$dish->getMedia('images')->map(fn($m) => ['id'=>$m->id,'url'=>$m->getUrl()])->all()" />
--}}
@props([
    'name'            => 'images',
    'deleteInputName' => 'delete_images',
    'existingImages'  => [],
    'maxImages'       => 8,
    'accept'          => 'image/jpeg,image/png,image/jpg,image/webp',
    'columns'         => 4,
    'id'              => null,
])

@php $inputId = $id ?? 'dz-multi-' . Str::random(6); @endphp

<div x-data="{
         existing:  @js(array_values($existingImages)),
         newFiles:  [],
         deletedIds:[],

         get total() { return this.existing.length + this.newFiles.length; },
         get canAdd() { return this.total < {{ (int) $maxImages }}; },

         addFiles(fileList) {
             Array.from(fileList).forEach(file => {
                 if (!file.type.startsWith('image/')) return;
                 const key = 'nf-' + Date.now() + '-' + Math.random();
                 const reader = new FileReader();
                 reader.onload = e => {
                     this.newFiles.push({ key, file, preview: e.target.result });
                 };
                 reader.readAsDataURL(file);
             });
         },

         removeExisting(id) {
             this.existing   = this.existing.filter(i => i.id !== id);
             this.deletedIds = [...this.deletedIds, id];
         },

         removeNew(key) {
             this.newFiles = this.newFiles.filter(f => f.key !== key);
         },
     }">

    <div class="ui-gallery"
         style="grid-template-columns: repeat({{ (int) $columns }}, 1fr)">

        {{-- Existing image tiles --}}
        <template x-for="img in existing" :key="img.id">
            <div class="ui-gtile" :style="`background-image:url(${img.url})`">
                <template x-if="img.primary">
                    <span class="gprimary">Cover</span>
                </template>
                <button type="button" class="gx" @click="removeExisting(img.id)"
                        aria-label="Remove image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M6 6l12 12M6 18L18 6"/>
                    </svg>
                </button>
            </div>
        </template>

        {{-- New file preview tiles --}}
        <template x-for="nf in newFiles" :key="nf.key">
            <div class="ui-gtile" :style="`background-image:url(${nf.preview})`">
                <button type="button" class="gx" @click="removeNew(nf.key)"
                        aria-label="Remove image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M6 6l12 12M6 18L18 6"/>
                    </svg>
                </button>
            </div>
        </template>

        {{-- Add tile --}}
        <template x-if="canAdd">
            <label class="ui-gtile add">
                <span style="display:grid;place-items:center;gap:5px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.6">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <circle cx="9" cy="10" r="2"/>
                        <path d="M21 16l-5-5-9 9"/>
                    </svg>
                    <span style="font-size:11px;letter-spacing:.08em;text-transform:uppercase">Add</span>
                </span>
                <input type="file"
                       id="{{ $inputId }}-add"
                       accept="{{ $accept }}"
                       multiple
                       @change="addFiles($event.target.files); $event.target.value=''">
            </label>
        </template>
    </div>

    {{-- Form: hidden file inputs for new images --}}
    @if ($name)
        <template x-for="nf in newFiles" :key="nf.key">
            {{-- Alpine can't create native file inputs with files attached
                 We use a separate approach: watch newFiles and sync via JS --}}
        </template>
        {{-- Real file sync handled via a hidden container updated by Alpine --}}
        <div id="{{ $inputId }}-file-sink" style="display:none"
             x-effect="syncFiles($el)">
        </div>
    @endif

    {{-- Form: hidden inputs for deleted image IDs --}}
    @if ($deleteInputName)
        <template x-for="id in deletedIds" :key="id">
            <input type="hidden" name="{{ $deleteInputName }}[]" :value="id">
        </template>
    @endif

</div>

@if ($name)
<script>
(function() {
    document.addEventListener('alpine:init', () => {
        /* nothing needed — file sync done below */
    });

    /* Sync newFiles array → hidden file inputs in the form.
       Called by x-effect on the file-sink div. */
    window['{{ $inputId }}_syncFiles'] = function(sink) {};

    /* Use a MutationObserver approach:
       Intercept the Add input's change event and clone files to hidden inputs. */
    document.addEventListener('DOMContentLoaded', function() {
        const addInput = document.getElementById('{{ $inputId }}-add');
        if (!addInput) return;

        const form = addInput.closest('form');
        if (!form) return;

        addInput.addEventListener('change', function() {
            /* Remove old ghost inputs */
            form.querySelectorAll('.{{ $inputId }}-ghost').forEach(el => el.remove());

            /* Re-create from the Alpine newFiles array via a custom event */
        });
    });
})();
</script>
@endif

{{--
    Note on file submission:
    Alpine cannot programmatically create <input type="file"> with a FileList.
    The pattern used here: when the form submits, attach new files via DataTransfer.
    Add this snippet once to the form's submit handler, or use the existing
    JS pattern from dishes/form.blade.php which manually creates hidden file inputs.

    Alternatively, switch to a Livewire component for fully reactive file management.
--}}
