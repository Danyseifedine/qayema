<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Restaurant Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Update your restaurant name, contact information, and images.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.restaurant.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="restaurant_name" :value="__('Restaurant Name')" />
            <x-text-input id="restaurant_name" name="restaurant_name" type="text"
                class="mt-1 block w-full" :value="old('restaurant_name', $user->restaurant_name)" placeholder="Enter your restaurant name"
                minlength="3" maxlength="255" required />
            <p class="mt-1 text-sm text-gray-500">Minimum 3 characters required</p>
            <x-input-error class="mt-2" :messages="$errors->get('restaurant_name')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                :value="old('phone', $user->phone)" placeholder="Enter your phone number" minlength="10"
                maxlength="255" required />
            <p class="mt-1 text-sm text-gray-500">Minimum 10 characters required</p>
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="address" :value="__('Address')" />
            <textarea id="address" name="address" rows="3"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                placeholder="Enter your restaurant address" minlength="10" maxlength="500" required>{{ old('address', $user->address) }}</textarea>
            <p class="mt-1 text-sm text-gray-500">Minimum 10 characters required</p>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div class="space-y-6">
            <!-- Logo Upload -->
            <div x-data="{
                logoPreview: @js($user->hasMedia('logo') ? $user->getFirstMediaUrl('logo') : null),
                logoFile: null,
                existingLogo: @js($user->hasMedia('logo')),
                isDragging: false,
                handleFile(file) {
                    if (file && file.type.startsWith('image/')) {
                        this.logoFile = file;
                        const reader = new FileReader();
                        reader.onload = (e) => this.logoPreview = e.target.result;
                        reader.readAsDataURL(file);
                        this.existingLogo = false;
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        document.getElementById('logo').files = dataTransfer.files;
                    }
                }
            }" @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="isDragging = false; handleFile($event.dataTransfer.files[0])">
                <x-input-label for="logo" :value="__('Logo')" />

                <!-- Image Preview Area -->
                <div class="mt-2">
                    <div x-show="logoPreview" class="mb-4">
                        <div class="relative inline-block">
                            <img :src="logoPreview" alt="Logo Preview"
                                class="h-32 w-32 object-contain rounded-lg border-2 border-gray-200 shadow-sm">
                            <button type="button"
                                @click="logoPreview = null; logoFile = null; document.getElementById('logo').value = ''"
                                class="absolute -top-2 -right-2 !bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-md ring-2 ring-white/90 hover:!bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                ×
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-2" x-show="existingLogo">Existing logo shown. Upload a new one to replace it.</p>
                    </div>

                    <!-- Upload Area -->
                    <label for="logo"
                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                        :class="logoPreview ? 'hidden' : (isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100')">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500">PNG, JPG, WEBP (MAX. 5MB)</p>
                        </div>
                        <input id="logo" name="logo" type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden"
                            @change="handleFile($event.target.files[0])" />
                    </label>
                </div>

                <p class="mt-2 text-sm text-gray-500">Upload your restaurant logo (max 5MB, will be optimized to max 50KB)</p>
                <x-input-error class="mt-2" :messages="$errors->get('logo')" />
            </div>
        </div>

        <div class="space-y-6">
            <!-- Cover Image Upload -->
            <div x-data="{
                coverPreview: @js($user->hasMedia('cover_image') ? $user->getFirstMediaUrl('cover_image') : null),
                coverFile: null,
                existingCover: @js($user->hasMedia('cover_image')),
                isDragging: false,
                handleFile(file) {
                    if (file && file.type.startsWith('image/')) {
                        this.coverFile = file;
                        const reader = new FileReader();
                        reader.onload = (e) => this.coverPreview = e.target.result;
                        reader.readAsDataURL(file);
                        this.existingCover = false;
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        document.getElementById('cover_image').files = dataTransfer.files;
                    }
                }
            }" @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="isDragging = false; handleFile($event.dataTransfer.files[0])">
                <x-input-label for="cover_image" :value="__('Cover Image')" />

                <!-- Image Preview Area -->
                <div class="mt-2">
                    <div x-show="coverPreview" class="mb-4">
                        <div class="relative inline-block w-full">
                            <img :src="coverPreview" alt="Cover Image Preview"
                                class="h-32 w-full object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                            <button type="button"
                                @click="coverPreview = null; coverFile = null; document.getElementById('cover_image').value = ''"
                                class="absolute top-2 right-2 !bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-md ring-2 ring-white/90 hover:!bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                ×
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-2" x-show="existingCover">Existing cover image shown. Upload a new one to replace it.</p>
                    </div>

                    <!-- Upload Area -->
                    <label for="cover_image"
                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                        :class="coverPreview ? 'hidden' : (isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100')">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500">PNG, JPG, WEBP (MAX. 5MB)</p>
                        </div>
                        <input id="cover_image" name="cover_image" type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden"
                            @change="handleFile($event.target.files[0])" />
                    </label>
                </div>

                <p class="mt-2 text-sm text-gray-500">Upload your cover image (max 5MB, will be resized to 1920x600)</p>
                <x-input-error class="mt-2" :messages="$errors->get('cover_image')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'restaurant-information-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
