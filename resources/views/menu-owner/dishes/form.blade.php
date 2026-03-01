<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $dish ? __('menu_owner.dishes.edit_dish') : __('menu_owner.dishes.create_dish') }}
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
                    <form method="POST"
                        action="{{ $dish ? route('menu-owner.dishes.update', $dish) : route('menu-owner.dishes.store') }}"
                        enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @if ($dish)
                            @method('PUT')
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
                                    :value="old('name', $dish?->name)" :placeholder="__('menu_owner.dishes.placeholder_name')" minlength="2" maxlength="255"
                                    required />
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
                                <x-text-input id="price" name="price" type="number" step="0.01"
                                    min="0" class="pl-7 block w-full rtl:pl-3 rtl:pr-7" :value="old('price', $dish?->price)" placeholder="0.00" />
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
                                    class="mt-1 block w-full" :value="old('display_order', $dish?->display_order ?? 0)" required />
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

                        <div x-data="{
                            existingImages: @js($dish && $dish->hasMedia('images') ? $dish->getMedia('images')->map(fn($img) => ['id' => $img->id, 'url' => $img->getUrl()])->toArray() : []),
                            imagePreviews: [],
                            allFiles: [],
                            isDragging: false,
                            handleFiles(files) {
                                const fileArray = Array.from(files);
                                fileArray.forEach(file => {
                                    if (file && file.type.startsWith('image/')) {
                                        // Add to allFiles array for form submission
                                        this.allFiles.push(file);

                                        // Create preview
                                        const reader = new FileReader();
                                        reader.onload = (e) => {
                                            this.imagePreviews.push({
                                                file: file,
                                                preview: e.target.result,
                                                id: 'new-' + Date.now() + '-' + Math.random()
                                            });
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });
                                // Update the file input with all files
                                this.updateFileInput();
                            },
                            updateFileInput() {
                                const input = document.getElementById('images');
                                const dataTransfer = new DataTransfer();
                                this.allFiles.forEach(file => {
                                    dataTransfer.items.add(file);
                                });
                                input.files = dataTransfer.files;
                            },
                            removeExistingImage(imageId) {
                                this.existingImages = this.existingImages.filter(img => img.id != imageId);
                                // Add or update hidden input to mark for deletion
                                let container = document.getElementById('images-container');
                                let existingInput = container.querySelector(`input[name='delete_images[]'][value='${imageId}']`);
                                if (!existingInput) {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'delete_images[]';
                                    input.value = imageId;
                                    container.appendChild(input);
                                }
                            },
                            removePreview(id) {
                                // Remove from both arrays
                                const preview = this.imagePreviews.find(img => img.id === id);
                                if (preview && preview.file) {
                                    this.allFiles = this.allFiles.filter(f => f !== preview.file);
                                }
                                this.imagePreviews = this.imagePreviews.filter(img => img.id !== id);
                                // Update file input
                                this.updateFileInput();
                            }
                        }" @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="isDragging = false; handleFiles($event.dataTransfer.files)">
                            <x-input-label for="images" :value="__('menu_owner.dishes.dish_images_optional')" />
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.dishes.dish_images_optional_desc') }}</p>

                            <div id="images-container" class="mt-2">
                                <!-- Existing Images -->
                                <template x-for="(image, index) in existingImages" :key="image.id">
                                    <div class="mb-4">
                                        <div class="relative inline-block w-full">
                                            <img :src="image.url" :alt="'Dish image ' + (index + 1)"
                                                class="h-48 w-full object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                                            <button type="button" @click="removeExistingImage(image.id)"
                                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                                                ×
                                            </button>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-2">Existing image. Click × to delete.</p>
                                    </div>
                                </template>

                                <!-- New Images Preview -->
                                <template x-for="(image, index) in imagePreviews" :key="image.id">
                                    <div class="mb-4">
                                        <div class="relative inline-block w-full">
                                            <img :src="image.preview" :alt="'New image ' + (index + 1)"
                                                class="h-48 w-full object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                                            <button type="button" @click="removePreview(image.id)"
                                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                                                ×
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- Upload Area -->
                                <label for="images"
                                    class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                                    :class="(existingImages.length > 0 || imagePreviews.length > 0) ? '' : (isDragging ?
                                        'border-indigo-500 bg-indigo-50' :
                                        'border-gray-300 bg-gray-50 hover:bg-gray-100')">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-12 h-12 mb-3 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                            </path>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500">PNG, JPG, WEBP (MAX. 5MB each)</p>
                                        <p class="text-xs text-gray-400 mt-1">You can select multiple images at once
                                        </p>
                                    </div>
                                    <input id="images" name="images[]" type="file"
                                        accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden" multiple
                                        @change="handleFiles($event.target.files)" x-ref="fileInput" />
                                </label>
                            </div>

                            <p class="mt-2 text-sm text-gray-500">Upload images for this dish (optional, max 5MB each,
                                will be optimized to max 50KB)</p>
                            <x-input-error class="mt-2" :messages="$errors->get('images.*')" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <x-btn href="{{ route('menu-owner.dishes.index') }}" variant="secondary" size="sm">{{ __('menu_owner.common.cancel') }}</x-btn>
                            <x-btn type="submit" variant="primary" size="sm" {{ $menu && $menu->hasReachedDishLimit() && !$dish ? 'disabled' : '' }}>
                                {{ $dish ? __('menu_owner.common.update') . ' ' . __('menu_owner.dishes.title') : __('menu_owner.common.create') . ' ' . __('menu_owner.dishes.title') }}
                            </x-btn>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($dish)
        <script>
            function deleteImage(imageId) {
                if (confirm('Are you sure you want to delete this image?')) {
                    fetch(`/menu-owner/dishes/{{ $dish->id }}/images/${imageId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to delete image');
                        });
                }
            }
        </script>
    @endif
</x-app-layout>
