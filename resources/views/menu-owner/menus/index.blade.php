<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Menu') }}
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
                        {{ $menu ? 'Edit Menu' : 'Create Your Menu' }}
                    </h3>

                    <form method="POST" action="{{ route('menu-owner.menus.store-or-update') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Menu Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name', $menu?->name)" placeholder="Enter your menu name" minlength="3" maxlength="255"
                                required />
                            <p class="mt-1 text-sm text-gray-500">Minimum 3 characters required</p>
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="slug" :value="__('URL Slug')" />
                            <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full bg-gray-100 cursor-not-allowed opacity-60"
                                :value="old('slug', $menu?->slug)" placeholder="menu-url-slug" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" disabled readonly />
                            <p class="mt-1 text-sm text-gray-500">Auto-generated from menu name. Used in the menu URL.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                                placeholder="Enter a description for your menu">{{ old('description', $menu?->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div>
                            <x-input-label for="menu_style" :value="__('Menu Style')" />
                            <select id="menu_style" name="menu_style"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                                required>
                                <option value="">Select menu style</option>
                                <option value="restaurant"
                                    {{ old('menu_style', $menu?->menu_style) == 'restaurant' ? 'selected' : '' }}>
                                    Restaurant</option>
                                <option value="home"
                                    {{ old('menu_style', $menu?->menu_style ?? 'home') == 'home' ? 'selected' : '' }}>
                                    Home Cook</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('menu_style')" />
                        </div>

                        <div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('is_active', $menu?->is_active ?? true) ? 'checked' : '' }} />
                                <span class="ms-2 text-sm text-gray-600">Menu is active (visible to public)</span>
                            </label>
                            <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ $menu ? 'Update Menu' : 'Create Menu' }}
                            </button>
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
