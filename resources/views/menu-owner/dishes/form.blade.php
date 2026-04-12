<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if ($dish)
                {{ __('menu_owner.dishes.edit_dish') }}
            @else
                {{ __('menu_owner.dishes.create_dish') }}
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if ($menu && $menu->hasReachedDishLimit() && !$dish)
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                    You have reached the maximum number of dishes ({{ $menu->dish_limit }}) allowed for your menu.
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    @if ($dish)
                        <form id="dish-form" method="POST" action="{{ route('menu-owner.dishes.update', $dish) }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @method('PUT')
                    @else
                        <form id="dish-form" method="POST" action="{{ route('menu-owner.dishes.store') }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                    @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="category_id" :value="__('menu_owner.dishes.category_optional')" />
                                    <p class="mt-0.5 text-sm text-gray-500">{{ __('menu_owner.dishes.category_optional_desc') }}</p>
                                    <select id="category_id" name="category_id"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2">
                                        <option value="">—</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('category_id', $dish?->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
                                </div>

                                <div>
                                    <x-input-label for="name" :value="__('menu_owner.dishes.dish_name')" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                        :value="old('name', $dish?->name)"
                                        :placeholder="__('menu_owner.dishes.placeholder_name')"
                                        minlength="2" maxlength="255" required />
                                    <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.dishes.dish_name_desc') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="price" :value="__('menu_owner.dishes.price_optional')" />
                                <div class="mt-1 relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none rtl:left-auto rtl:right-0 rtl:pl-0 rtl:pr-3">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <x-text-input id="price" name="price" type="number" step="0.01" min="0"
                                        class="pl-7 block w-full rtl:pl-3 rtl:pr-7"
                                        :value="old('price', $dish?->price)"
                                        placeholder="0.00" />
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.dishes.price_optional_desc') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('price')" />
                            </div>

                            <div>
                                <x-input-label for="ingredients" :value="__('menu_owner.dishes.ingredients_optional')" />
                                <textarea id="ingredients" name="ingredients" rows="4"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                                    placeholder="List ingredients, comma or new line separated">{{ old('ingredients', $dish?->ingredients) }}</textarea>
                                <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.dishes.ingredients_optional_desc') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('ingredients')" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="display_order" :value="__('menu_owner.dishes.display_order')" />
                                    <x-text-input id="display_order" name="display_order" type="number" min="0"
                                        class="mt-1 block w-full"
                                        :value="old('display_order', $dish?->display_order ?? 0)"
                                        required />
                                    <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.dishes.display_order_desc') }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ __('menu_owner.dishes.lower_first') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('display_order')" />
                                </div>

                                <div class="flex items-center">
                                    <label class="inline-flex items-center cursor-pointer mt-6">
                                        <input type="checkbox" name="is_available" value="1"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            {{ old('is_available', $dish?->is_available ?? true) ? 'checked' : '' }} />
                                        <span class="ms-2 text-sm text-gray-600">{{ __('menu_owner.dishes.dish_available') }}</span>
                                    </label>
                                    <x-input-error class="mt-2" :messages="$errors->get('is_available')" />
                                </div>
                            </div>

                            <script type="application/json" id="dish-existing-images-json">
                                @json($dish && $dish->hasMedia('images') ? $dish->getMedia('images')->map(fn ($img) => ['id' => $img->id, 'url' => $img->getUrl()])->values()->all() : [])
                            </script>

                            {{-- Images --}}
                            <div>
                                <x-input-label for="images-input" :value="__('menu_owner.dishes.dish_images_optional')" />
                                <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.dishes.dish_images_optional_desc') }}</p>

                                <div id="images-container" class="mt-2" data-dish-id="{{ $dish?->id ?? '' }}">
                                </div>

                                <p class="mt-2 text-sm text-gray-500">Upload images for this dish (optional, max 5MB each, will be optimized to max 50KB)</p>
                                <x-input-error class="mt-2" :messages="$errors->get('images.*')" />
                            </div>

                            <div class="flex items-center justify-end gap-4">
                                <x-btn href="{{ route('menu-owner.dishes.index') }}" variant="secondary" size="sm">
                                    {{ __('menu_owner.common.cancel') }}
                                </x-btn>

                                @if ($menu && $menu->hasReachedDishLimit() && !$dish)
                                    <x-btn type="submit" variant="primary" size="sm" disabled>
                                        {{ __('menu_owner.common.create') }} {{ __('menu_owner.dishes.title') }}
                                    </x-btn>
                                @else
                                    <x-btn type="submit" variant="primary" size="sm">
                                        @if ($dish)
                                            {{ __('menu_owner.common.update') }} {{ __('menu_owner.dishes.title') }}
                                        @else
                                            {{ __('menu_owner.common.create') }} {{ __('menu_owner.dishes.title') }}
                                        @endif
                                    </x-btn>
                                @endif
                            </div>

                        </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var container = document.getElementById('images-container');
        var form = document.getElementById('dish-form');
        var jsonEl = document.getElementById('dish-existing-images-json');
        var existingImages = [];
        if (jsonEl && jsonEl.textContent.trim() !== '') {
            try {
                existingImages = JSON.parse(jsonEl.textContent);
            } catch (e) {
                existingImages = [];
            }
        }
        if (!container || !form) {
            return;
        }
        var newFiles = [];
        var deletedIds = [];

        function render() {
            container.innerHTML = '';

            // Existing images
            existingImages.forEach(function (image) {
                var wrap = document.createElement('div');
                wrap.className = 'mb-4';
                var inner = document.createElement('div');
                inner.className = 'relative inline-block w-full';

                var img = document.createElement('img');
                img.src = image.url;
                img.className = 'h-48 w-full object-cover rounded-lg border-2 border-gray-200 shadow-sm';

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'absolute top-2 right-2 !bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg shadow-md ring-2 ring-white/90 hover:!bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500';
                btn.innerHTML = '&times;';
                btn.setAttribute('aria-label', 'Remove image');
                btn.addEventListener('click', function () {
                    existingImages = existingImages.filter(function (i) { return i.id != image.id; });
                    deletedIds.push(image.id);
                    syncDeletedInputs();
                    render();
                });

                inner.appendChild(img);
                inner.appendChild(btn);
                wrap.appendChild(inner);

                var hint = document.createElement('p');
                hint.className = 'text-sm text-gray-500 mt-2';
                hint.textContent = 'Existing image. Click \u00d7 to delete.';
                wrap.appendChild(hint);
                container.appendChild(wrap);
            });

            // New file previews
            newFiles.forEach(function (item) {
                var wrap = document.createElement('div');
                wrap.className = 'mb-4';
                var inner = document.createElement('div');
                inner.className = 'relative inline-block w-full';

                var img = document.createElement('img');
                img.src = item.preview;
                img.className = 'h-48 w-full object-cover rounded-lg border-2 border-gray-200 shadow-sm';

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'absolute top-2 right-2 !bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg shadow-md ring-2 ring-white/90 hover:!bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500';
                btn.innerHTML = '&times;';
                btn.setAttribute('aria-label', 'Remove new image');
                btn.addEventListener('click', function () {
                    newFiles = newFiles.filter(function (f) { return f.key !== item.key; });
                    syncFileInput();
                    render();
                });

                inner.appendChild(img);
                inner.appendChild(btn);
                wrap.appendChild(inner);
                container.appendChild(wrap);
            });

            // Upload area
            var label = document.createElement('label');
            label.setAttribute('for', 'images-input');
            label.className = 'flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors';
            label.innerHTML =
                '<div class="flex flex-col items-center justify-center pt-5 pb-6">' +
                    '<svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>' +
                    '</svg>' +
                    '<p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>' +
                    '<p class="text-xs text-gray-500">PNG, JPG, WEBP (MAX. 5MB each)</p>' +
                    '<p class="text-xs text-gray-400 mt-1">You can select multiple images at once</p>' +
                '</div>';

            var fileInput = document.createElement('input');
            fileInput.id = 'images-input';
            fileInput.type = 'file';
            fileInput.accept = 'image/jpeg,image/png,image/jpg,image/webp';
            fileInput.multiple = true;
            fileInput.className = 'hidden';
            fileInput.addEventListener('change', function () { handleFiles(this.files); });
            label.appendChild(fileInput);

            label.addEventListener('dragover', function (e) {
                e.preventDefault();
                label.classList.add('border-indigo-500', 'bg-indigo-50');
                label.classList.remove('border-gray-300', 'bg-gray-50');
            });
            label.addEventListener('dragleave', function () {
                label.classList.remove('border-indigo-500', 'bg-indigo-50');
                label.classList.add('border-gray-300', 'bg-gray-50');
            });
            label.addEventListener('drop', function (e) {
                e.preventDefault();
                label.classList.remove('border-indigo-500', 'bg-indigo-50');
                label.classList.add('border-gray-300', 'bg-gray-50');
                handleFiles(e.dataTransfer.files);
            });

            container.appendChild(label);
        }

        function handleFiles(files) {
            Array.from(files).forEach(function (file) {
                if (!file.type.startsWith('image/')) return;
                var key = 'f-' + Date.now() + '-' + Math.random();
                var reader = new FileReader();
                reader.onload = function (e) {
                    newFiles.push({ key: key, file: file, preview: e.target.result });
                    syncFileInput();
                    render();
                };
                reader.readAsDataURL(file);
            });
        }

        function syncFileInput() {
            form.querySelectorAll('input.js-dish-new-image').forEach(function (el) {
                el.remove();
            });
            newFiles.forEach(function (item) {
                var dt = new DataTransfer();
                dt.items.add(item.file);
                var inp = document.createElement('input');
                inp.type = 'file';
                inp.name = 'images[]';
                inp.className = 'js-dish-new-image hidden';
                inp.setAttribute('aria-hidden', 'true');
                inp.tabIndex = -1;
                inp.style.display = 'none';
                inp.files = dt.files;
                form.appendChild(inp);
            });
        }

        function syncDeletedInputs() {
            document.querySelectorAll('input[name="delete_images[]"]').forEach(function (el) { el.remove(); });
            deletedIds.forEach(function (id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'delete_images[]';
                inp.value = id;
                form.appendChild(inp);
            });
        }

        render();
    })();
    </script>

</x-app-layout>
