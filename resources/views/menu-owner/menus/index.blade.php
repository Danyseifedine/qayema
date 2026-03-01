<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('menu_owner.menus.title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">
                        {{ $menu ? __('menu_owner.menus.edit_menu') : __('menu_owner.menus.create_menu') }}
                    </h3>

                    <form method="POST" action="{{ route('menu-owner.menus.store-or-update') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('menu_owner.menus.menu_name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name', $menu?->name)" :placeholder="__('menu_owner.menus.placeholder_name')" minlength="3" maxlength="255"
                                required />
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.menus.menu_name_desc') }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ __('menu_owner.menus.min_chars') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="slug" :value="__('menu_owner.menus.url_slug')" />
                            <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full bg-gray-100 cursor-not-allowed opacity-60"
                                :value="old('slug', $menu?->slug)" :placeholder="__('menu_owner.menus.placeholder_slug')" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" disabled readonly />
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.menus.url_slug_desc') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('menu_owner.menus.description')" />
                            <textarea id="description" name="description" rows="4"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                                :placeholder="__('menu_owner.menus.placeholder_description')">{{ old('description', $menu?->description) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.menus.description_desc') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div>
                            <x-input-label for="menu_style" :value="__('menu_owner.menus.menu_style')" />
                            <select id="menu_style" name="menu_style"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                                required>
                                <option value="">{{ __('menu_owner.menus.select_menu_style') }}</option>
                                <option value="restaurant"
                                    {{ old('menu_style', $menu?->menu_style) == 'restaurant' ? 'selected' : '' }}>
                                    {{ __('menu_owner.menus.restaurant') }}</option>
                                <option value="home"
                                    {{ old('menu_style', $menu?->menu_style ?? 'home') == 'home' ? 'selected' : '' }}>
                                    {{ __('menu_owner.menus.home_cook') }}</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.menus.menu_style_desc') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('menu_style')" />
                        </div>

                        <div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('is_active', $menu?->is_active ?? true) ? 'checked' : '' }} />
                                <span class="ms-2 text-sm text-gray-600">{{ __('menu_owner.menus.is_active') }}</span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.menus.is_active_desc') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                        </div>

                        <div class="flex items-center justify-end">
                            <x-primary-button>
                                {{ $menu ? __('menu_owner.menus.update_menu') : __('menu_owner.menus.create_menu_button') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-generate slug from name (slug field is disabled, but we still update it visually)
        document.getElementById('name')?.addEventListener('input', function() {
            const slugInput = document.getElementById('slug');
            if (slugInput) {
                const slug = this.value.toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });
    </script>
</x-app-layout>
