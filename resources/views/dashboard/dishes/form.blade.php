<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant->name }}</a>
        <span class="sbl-crumb-sep">/</span>
        <a href="{{ route('menu-owner.dishes.index') }}">{{ __('menu_owner.nav.dishes') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ $dish ? __('menu_owner.dishes.edit_dish') : __('menu_owner.dishes.create_dish') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ $dish ? __('menu_owner.dishes.edit_dish') : __('menu_owner.dishes.create_dish') }}</h1>
        </div>
        <p class="qf-desc">
            {{ $dish
                ? __('menu_owner.dishes.edit_desc', ['name' => $dish->name])
                : __('menu_owner.dishes.create_desc') }}
        </p>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if ($restaurant && $restaurant->hasReachedDishLimit() && ! $dish)
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                {{ __('menu_owner.dishes.limit_reached_max', ['limit' => $restaurant->dish_limit]) }}
            </div>
        @endif

        <div class="qf-wrap">
            @if ($dish)
                <form id="dish-form" method="POST" action="{{ route('menu-owner.dishes.update', $dish) }}"
            @else
                <form id="dish-form" method="POST" action="{{ route('menu-owner.dishes.store') }}"
            @endif
                      x-data="{
                          errors: {},
                          isCreate: @js(! $dish),

                          submit(e) {
                              this.errors = {};
                              let ok = true;

                              // Category required
                              const cat = this.$el.querySelector('input[name=category_id]');
                              if (!cat || !cat.value) {
                                  this.errors.category_id = '{{ __('menu_owner.dishes.validation.category_required') }}';
                                  ok = false;
                              }

                              // Image required on create
                              if (this.isCreate) {
                                  const img = this.$el.querySelector('input[name=dish_image_key]');
                                  if (!img || !img.value) {
                                      this.errors.dish_image = '{{ __('menu_owner.dishes.validation.image_required') }}';
                                      ok = false;
                                  }
                              }

                              // Price non-negative
                              const price = this.$el.querySelector('input[name=price]');
                              if (price && price.value !== '' && parseFloat(price.value) < 0) {
                                  this.errors.price = '{{ __('menu_owner.dishes.validation.price_negative') }}';
                                  ok = false;
                              }

                              if (!ok) {
                                  e.preventDefault();
                                  this.$nextTick(() => {
                                      const first = this.$el.querySelector('.ui-fe-error[style]:not([style*=\'display: none\']),.ui-fe-error:not([style])');
                                      first?.closest('.qf-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                  });
                              }
                          },
                      }"
                      @submit="submit($event)">
                @csrf
                @if ($dish) @method('PUT') @endif

                {{-- ── Basic info ──────────────────────────────────── --}}
                <div class="qf-section">
                    <div class="qf-section-title">{{ __('menu_owner.dishes.section_basic') }}</div>
                    <div class="space-y-5">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <x-ui.field name="name"
                                        label="{{ __('menu_owner.dishes.dish_name') }}"
                                        help="{{ __('menu_owner.dishes.dish_name_desc') }}"
                                        required>
                                <x-ui.input name="name" type="text"
                                            :value="old('name', $dish?->name)"
                                            placeholder="{{ __('menu_owner.dishes.placeholder_name') }}"
                                            minlength="2" maxlength="255" required />
                            </x-ui.field>

                            <div>
                                <x-ui.field name="category_id"
                                            label="{{ __('menu_owner.dishes.category') }}"
                                            help="{{ __('menu_owner.dishes.category_desc') }}"
                                            required>
                                    @php
                                        $categoryOptions = $categories->map(fn($c) => [
                                            'value' => (string) $c->id,
                                            'label' => $c->name,
                                        ])->values()->all();
                                        $categoryValue = (string) old('category_id', $dish?->category_id ?? '');
                                    @endphp
                                    <x-ui.combo
                                        name="category_id"
                                        :options="$categoryOptions"
                                        :value="$categoryValue"
                                        placeholder="{{ __('menu_owner.dishes.select_category') }}" />
                                </x-ui.field>
                                <p class="ui-fe-error" x-show="errors.category_id" x-text="errors.category_id"></p>
                            </div>

                        </div>

                        {{-- Ingredients / description --}}
                        <x-ui.field name="ingredients"
                                    label="{{ __('menu_owner.dishes.ingredients_optional') }}">
                            <x-ui.textarea name="ingredients" rows="3" maxlength="1000"
                                           placeholder="{{ __('menu_owner.dishes.ingredient_placeholder') }}"
                                           :value="old('ingredients', $dish?->ingredients)" />
                        </x-ui.field>

                    </div>
                </div>

                {{-- ── Pricing & visibility ─────────────────────────── --}}
                <div class="qf-section">
                    <div class="qf-section-title">{{ __('menu_owner.dishes.section_pricing') }}</div>
                    <div class="grid grid-cols-1 md:grid-cols-{{ $dish ? '3' : '2' }} gap-5">

                        <div>
                            <x-ui.field name="price"
                                        label="{{ __('menu_owner.dishes.price_optional') }}"
                                        help="{{ __('menu_owner.dishes.price_optional_desc') }}">
                                <x-ui.input name="price" type="number" step="0.01" min="0"
                                            prefix="$"
                                            :value="old('price', $dish?->price)"
                                            placeholder="0.00" />
                            </x-ui.field>
                            <p class="ui-fe-error" x-show="errors.price" x-text="errors.price"></p>
                        </div>

                        @if ($dish)
                            <x-ui.field name="display_order"
                                        label="{{ __('menu_owner.dishes.display_order') }}"
                                        help="{{ __('menu_owner.dishes.display_order_desc') }}">
                                <x-ui.input name="display_order" type="number" min="0"
                                            :value="old('display_order', $dish->display_order)"
                                            required />
                            </x-ui.field>
                        @endif

                        <div class="flex items-center pb-1">
                            <x-ui.checkbox name="is_available" value="1"
                                           :checked="old('is_available', $dish?->is_available ?? true)"
                                           label="{{ __('menu_owner.dishes.dish_available') }}" />
                        </div>

                    </div>
                </div>

                {{-- ── Photo ───────────────────────────────────────── --}}
                <div class="qf-section">
                    <div class="qf-section-title">
                        {{ __('menu_owner.dishes.section_photo') }}
                        @if (! $dish)
                            <span class="req" style="margin-left:4px">*</span>
                        @endif
                    </div>
                    <x-ui.dropzone
                        name="dish_image"
                        context="dish"
                        hint="{{ __('menu_owner.dishes.photo_hint') }}"
                        :value="$dish?->getFirstMediaUrl('images')"
                        :file-name="$dish?->getFirstMedia('images')?->file_name"
                        delete-name="delete_dish_image" />
                    <p class="ui-fe-error" style="margin-top:8px" x-show="errors.dish_image" x-text="errors.dish_image"></p>
                    @error('dish_image_key')
                        <p class="ui-help error" style="margin-top:8px">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ── Actions ─────────────────────────────────────── --}}
                <div class="qf-actions">
                    <x-btn href="{{ route('menu-owner.dishes.index') }}" variant="secondary">
                        {{ __('menu_owner.common.cancel') }}
                    </x-btn>
                    @if ($restaurant && $restaurant->hasReachedDishLimit() && ! $dish)
                        <x-btn type="submit" variant="primary" disabled>
                            {{ __('menu_owner.common.create') }} {{ __('menu_owner.dishes.title') }}
                        </x-btn>
                    @else
                        <x-btn type="submit" variant="primary">
                            {{ $dish
                                ? __('menu_owner.common.update').' '.__('menu_owner.dishes.title')
                                : __('menu_owner.common.create').' '.__('menu_owner.dishes.title') }}
                        </x-btn>
                    @endif
                </div>

            </form>
        </div>
    </div>
</x-sidebar-layout>
