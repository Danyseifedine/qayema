<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $category ? __('menu_owner.categories.edit_category') : __('menu_owner.categories.create_category') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" 
                        action="{{ $category ? route('menu-owner.categories.update', $category) : route('menu-owner.categories.store') }}"
                        enctype="multipart/form-data" 
                        class="space-y-6">
                        @csrf
                        @if($category)
                            @method('PUT')
                        @endif

                        <div>
                            <x-input-label for="name" :value="__('menu_owner.categories.category_name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" 
                                :value="old('name', $category?->name)" :placeholder="__('menu_owner.categories.placeholder_name')" 
                                minlength="2" maxlength="255" required />
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.categories.category_name_desc') }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ __('menu_owner.categories.min_chars') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('menu_owner.categories.description_optional')" />
                            <textarea id="description" name="description" rows="4"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                                :placeholder="__('menu_owner.categories.placeholder_description')">{{ old('description', $category?->description) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.categories.description_optional_desc') }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ __('menu_owner.categories.this_field_optional') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="display_order" :value="__('menu_owner.categories.display_order')" />
                                <x-text-input id="display_order" name="display_order" type="number" class="mt-1 block w-full" 
                                    :value="old('display_order', $category?->display_order ?? 0)" 
                                    min="0" required />
                                <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.categories.display_order_desc') }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ __('menu_owner.categories.lower_first') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('display_order')" />
                            </div>
                        </div>

                        <div x-data="{ 
                            imagePreview: @js($category && $category->hasMedia('image') ? $category->getFirstMediaUrl('image') : null),
                            imageFile: null,
                            existingImage: @js($category && $category->hasMedia('image')),
                            isDragging: false,
                            handleFile(file) {
                                if (file && file.type.startsWith('image/')) {
                                    this.imageFile = file;
                                    const reader = new FileReader();
                                    reader.onload = (e) => this.imagePreview = e.target.result;
                                    reader.readAsDataURL(file);
                                    this.existingImage = false;
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    document.getElementById('image').files = dataTransfer.files;
                                }
                            }
                        }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false; handleFile($event.dataTransfer.files[0])">
                            <x-input-label for="image" :value="__('menu_owner.categories.category_image_optional')" />
                            
                            <!-- Image Preview Area -->
                            <div class="mt-2">
                                <div x-show="imagePreview" class="mb-4">
                                    <div class="relative inline-block">
                                        <img :src="imagePreview" alt="Category Image Preview"
                                            class="h-48 w-full object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                                        <button type="button" @click="imagePreview = null; imageFile = null; document.getElementById('image').value = ''"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            ×
                                        </button>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2" x-show="existingImage">{{ __('menu_owner.categories.existing_image_note') }}</p>
                                </div>
                                
                                <!-- Upload Area -->
                                <label for="image" 
                                    class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                                    :class="imagePreview ? 'hidden' : (isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100')">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500">PNG, JPG, WEBP (MAX. 5MB)</p>
                                    </div>
                                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/jpg,image/webp"
                                        class="hidden"
                                        @change="handleFile($event.target.files[0])" />
                                </label>
                            </div>
                            
                            <p class="mt-2 text-sm text-gray-500">{{ __('menu_owner.categories.category_image_optional_desc') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('image')" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <x-btn href="{{ route('menu-owner.categories.index') }}" variant="secondary" size="sm">{{ __('menu_owner.common.cancel') }}</x-btn>
                            <x-btn type="submit" variant="primary" size="sm">
                                {{ $category ? __('menu_owner.common.update') . ' ' . __('menu_owner.categories.title') : __('menu_owner.common.create') . ' ' . __('menu_owner.categories.title') }}
                            </x-btn>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

