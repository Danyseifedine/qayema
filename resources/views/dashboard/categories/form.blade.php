<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <a href="{{ route('menu-owner.categories.index') }}">{{ __('menu_owner.nav.categories') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ $category ? __('menu_owner.categories.edit_category') : __('menu_owner.categories.create_category') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ $category ? __('menu_owner.categories.edit_category') : __('menu_owner.categories.create_category') }}</h1>
        </div>
        <p class="qf-desc">{{ $category ? __('menu_owner.categories.edit_desc', ['name' => $category->name]) : __('menu_owner.categories.create_desc') }}</p>

        <div class="qf-wrap">
            <form method="POST"
                  action="{{ $category ? route('menu-owner.categories.update', $category) : route('menu-owner.categories.store') }}"
                  x-data="{
                      errors: {},
                      submit(e) {
                          this.errors = {};
                          let ok = true;

                          const name = this.$el.querySelector('input[name=name]');
                          if (!name || !name.value.trim()) {
                              this.errors.name = '{{ __('menu_owner.categories.validation.name_required') }}';
                              ok = false;
                          } else if (name.value.trim().length < 2) {
                              this.errors.name = '{{ __('menu_owner.categories.validation.name_min') }}';
                              ok = false;
                          }

                          if (!ok) {
                              e.preventDefault();
                              this.$nextTick(() => {
                                  this.$el.querySelector('.ui-fe-error:not([style*=\'display: none\']),.ui-fe-error:not([style])')
                                      ?.closest('.qf-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                              });
                          }
                      },
                  }"
                  @submit="submit($event)">
                @csrf
                @if ($category) @method('PUT') @endif

                {{-- ── Details ─────────────────────────────────── --}}
                <div class="qf-section">
                    <div class="qf-section-title">{{ __('menu_owner.categories.section_details') }}</div>
                    <div class="space-y-5">

                        <div>
                            <x-ui.field name="name"
                                        label="{{ __('menu_owner.categories.category_name') }}"
                                        required
                                        help="{{ __('menu_owner.categories.category_name_desc') }}">
                                <x-ui.input name="name" type="text"
                                            :value="old('name', $category?->name)"
                                            placeholder="{{ __('menu_owner.categories.placeholder_name') }}"
                                            minlength="2" maxlength="255" required />
                            </x-ui.field>
                            <p class="ui-fe-error" x-show="errors.name" x-text="errors.name"></p>
                        </div>

                        @if ($category)
                            <x-ui.field name="display_order"
                                        label="{{ __('menu_owner.categories.display_order') }}"
                                        help="{{ __('menu_owner.categories.display_order_desc') }}"
                                        style="max-width:200px">
                                <x-ui.input name="display_order" type="number"
                                            :value="old('display_order', $category->display_order)"
                                            min="0" required />
                            </x-ui.field>
                        @endif

                    </div>
                </div>

                {{-- ── Photo ───────────────────────────────────── --}}
                <div class="qf-section">
                    <div class="qf-section-title">{{ __('menu_owner.categories.category_image_optional') }}</div>
                    <x-ui.dropzone
                        name="image"
                        context="category"
                        hint="{{ __('menu_owner.dishes.photo_hint') }}"
                        :value="$category?->getFirstMediaUrl('image')"
                        :file-name="$category?->getFirstMedia('image')?->file_name"
                        delete-name="delete_image" />
                    @error('image_key')
                        <p class="ui-help error" style="margin-top:8px">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ── Actions ─────────────────────────────────── --}}
                <div class="qf-actions">
                    <x-btn href="{{ route('menu-owner.categories.index') }}" variant="secondary">{{ __('menu_owner.common.cancel') }}</x-btn>
                    <x-btn type="submit" variant="primary">
                        {{ $category
                            ? __('menu_owner.common.update').' '.__('menu_owner.categories.title')
                            : __('menu_owner.common.create').' '.__('menu_owner.categories.title') }}
                    </x-btn>
                </div>

            </form>
        </div>
    </div>
</x-sidebar-layout>
