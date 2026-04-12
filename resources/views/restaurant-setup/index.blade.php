<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Restaurant Setup') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Steps -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-center">
                    <!-- Step 1 -->
                    <div class="flex items-center">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-full border-2 {{ $currentStep >= 1 ? 'bg-orange-500 border-orange-500 text-white' : 'bg-white border-gray-300 text-gray-400' }}">
                            <span class="text-lg font-semibold">1</span>
                        </div>
                        <div class="ml-3">
                            <p
                                class="text-sm font-medium {{ $currentStep >= 1 ? 'text-orange-600' : 'text-gray-500' }}">
                                Restaurant Information</p>
                            <p class="text-xs text-gray-500">Name, Phone, Address</p>
                        </div>
                    </div>

                    <!-- Connector Line -->
                    <div class="flex-1 mx-6 h-0.5 {{ $currentStep >= 2 ? 'bg-orange-500' : 'bg-gray-300' }}"></div>

                    <!-- Step 2 -->
                    <div class="flex items-center">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-full border-2 {{ $currentStep >= 2 ? 'bg-orange-500 border-orange-500 text-white' : 'bg-white border-gray-300 text-gray-400' }}">
                            <span class="text-lg font-semibold">2</span>
                        </div>
                        <div class="ml-3">
                            <p
                                class="text-sm font-medium {{ $currentStep >= 2 ? 'text-orange-600' : 'text-gray-500' }}">
                                Logo & Cover</p>
                            <p class="text-xs text-gray-500">Upload Images</p>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Step 1: Restaurant Information -->
            @if ($currentStep == 1)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Step 1: Restaurant Information</h3>
                        <form method="POST" action="{{ route('restaurant-setup.step1') }}" id="step1Form"
                            class="space-y-6">
                            @csrf

                            <div>
                                <x-input-label for="restaurant_name" :value="__('Restaurant Name')" />
                                <x-text-input id="restaurant_name" name="restaurant_name" type="text"
                                    class="mt-1 block w-full" :value="old('restaurant_name', $user->restaurant_name)" placeholder="Enter your restaurant name"
                                    minlength="3" maxlength="255" required />
                                <p class="mt-1 text-sm text-gray-500">Minimum 3 characters required</p>
                                <div id="restaurant_name_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                <x-input-error class="mt-2" :messages="$errors->get('restaurant_name')" />
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('Phone')" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                    :value="old('phone', $user->phone)" placeholder="Enter your phone number" minlength="10"
                                    maxlength="20" required />
                                <p class="mt-1 text-sm text-gray-500">Minimum 10 characters required</p>
                                <div id="phone_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                            </div>

                            <div>
                                <x-input-label for="address" :value="__('Address')" />
                                <textarea id="address" name="address" rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm px-3 py-2"
                                    placeholder="Enter your address" minlength="10" maxlength="500" required>{{ old('address', $user->address) }}</textarea>
                                <p class="mt-1 text-sm text-gray-500">Minimum 10 characters required</p>
                                <div id="address_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                <x-input-error class="mt-2" :messages="$errors->get('address')" />
                            </div>

                            <div class="flex items-center justify-end">
                                <x-btn type="submit" variant="primary" size="sm">Continue to Step 2</x-btn>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Step 2: Logo & Cover Image -->
            @if ($currentStep == 2)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Step 2: Logo & Cover Image</h3>
                        <form method="POST" action="{{ route('restaurant-setup.step2') }}"
                            enctype="multipart/form-data" id="step2Form" class="space-y-6">
                            @csrf

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
                                        <p class="text-sm text-gray-500 mt-2" x-show="existingLogo">Existing logo shown.
                                            Upload a new one to replace it.</p>
                                    </div>

                                    <!-- Upload Area -->
                                    <label for="logo"
                                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                                        :class="logoPreview ? 'hidden' : (isDragging ? 'border-indigo-500 bg-indigo-50' :
                                            'border-gray-300 bg-gray-50 hover:bg-gray-100')">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-10 h-10 mb-3 text-gray-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
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
                                            {{ !$user->hasMedia('logo') ? 'required' : '' }}
                                            @change="handleFile($event.target.files[0])" />
                                    </label>
                                </div>

                                <p class="mt-2 text-sm text-gray-500">Upload your restaurant logo (max 5MB, will be
                                    optimized to max 50KB)</p>
                                <div id="logo_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                <x-input-error class="mt-2" :messages="$errors->get('logo')" />
                            </div>

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
                                        <div class="relative">
                                            <img :src="coverPreview" alt="Cover Image Preview"
                                                class="h-48 w-full object-cover rounded-lg border-2 border-gray-200 shadow-sm">
                                            <button type="button"
                                                @click="coverPreview = null; coverFile = null; document.getElementById('cover_image').value = ''"
                                                class="absolute top-2 right-2 !bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-lg shadow-md ring-2 ring-white/90 hover:!bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                                ×
                                            </button>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-2" x-show="existingCover">Existing cover
                                            image shown. Upload a new one to replace it.</p>
                                    </div>

                                    <!-- Upload Area -->
                                    <label for="cover_image"
                                        class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                                        :class="coverPreview ? 'hidden' : (isDragging ? 'border-indigo-500 bg-indigo-50' :
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
                                            <p class="text-xs text-gray-500">PNG, JPG, WEBP (MAX. 5MB)</p>
                                            <p class="text-xs text-gray-400 mt-1">Recommended: 1920x600px</p>
                                        </div>
                                        <input id="cover_image" name="cover_image" type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden"
                                            {{ !$user->hasMedia('cover_image') ? 'required' : '' }}
                                            @change="handleFile($event.target.files[0])" />
                                    </label>
                                </div>

                                <p class="mt-2 text-sm text-gray-500">Upload a cover image/banner for your restaurant
                                    (max 5MB, will be resized to 1920x600)</p>
                                <div id="cover_image_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                <x-input-error class="mt-2" :messages="$errors->get('cover_image')" />
                            </div>

                            <div class="flex items-center justify-end gap-4">
                                <x-btn href="{{ route('restaurant-setup.index') }}?step=1" variant="secondary" size="sm">Back to Step 1</x-btn>
                                <x-btn type="submit" variant="primary" size="sm">Complete Setup</x-btn>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Step 1 Validation
        document.getElementById('step1Form')?.addEventListener('submit', function(e) {
            const restaurantName = document.getElementById('restaurant_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            let isValid = true;

            // Clear previous errors
            document.getElementById('restaurant_name_error').classList.add('hidden');
            document.getElementById('phone_error').classList.add('hidden');
            document.getElementById('address_error').classList.add('hidden');

            // Validate restaurant name
            if (restaurantName.length < 3) {
                document.getElementById('restaurant_name_error').textContent =
                    'Restaurant name must be at least 3 characters';
                document.getElementById('restaurant_name_error').classList.remove('hidden');
                isValid = false;
            }

            // Validate phone
            if (phone.length < 10) {
                document.getElementById('phone_error').textContent = 'Phone number must be at least 10 characters';
                document.getElementById('phone_error').classList.remove('hidden');
                isValid = false;
            }

            // Validate address
            if (address.length < 10) {
                document.getElementById('address_error').textContent = 'Address must be at least 10 characters';
                document.getElementById('address_error').classList.remove('hidden');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Step 2 Validation
        document.getElementById('step2Form')?.addEventListener('submit', function(e) {
            const logo = document.getElementById('logo');
            const coverImage = document.getElementById('cover_image');
            const hasLogo = @json($user->hasMedia('logo'));
            const hasCoverImage = @json($user->hasMedia('cover_image'));
            let isValid = true;

            // Clear previous errors
            document.getElementById('logo_error')?.classList.add('hidden');
            document.getElementById('cover_image_error')?.classList.add('hidden');

            // Validate logo
            if (!hasLogo && (!logo.files || logo.files.length === 0)) {
                document.getElementById('logo_error').textContent = 'Please upload a logo';
                document.getElementById('logo_error').classList.remove('hidden');
                isValid = false;
            } else if (logo.files && logo.files.length > 0) {
                const file = logo.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

                if (file.size > maxSize) {
                    document.getElementById('logo_error').textContent = 'Logo file size must be less than 5MB';
                    document.getElementById('logo_error').classList.remove('hidden');
                    isValid = false;
                } else if (!allowedTypes.includes(file.type)) {
                    document.getElementById('logo_error').textContent =
                        'Logo must be a JPEG, PNG, JPG, or WEBP image';
                    document.getElementById('logo_error').classList.remove('hidden');
                    isValid = false;
                }
            }

            // Validate cover image
            if (!hasCoverImage && (!coverImage.files || coverImage.files.length === 0)) {
                document.getElementById('cover_image_error').textContent = 'Please upload a cover image';
                document.getElementById('cover_image_error').classList.remove('hidden');
                isValid = false;
            } else if (coverImage.files && coverImage.files.length > 0) {
                const file = coverImage.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

                if (file.size > maxSize) {
                    document.getElementById('cover_image_error').textContent =
                        'Cover image file size must be less than 5MB';
                    document.getElementById('cover_image_error').classList.remove('hidden');
                    isValid = false;
                } else if (!allowedTypes.includes(file.type)) {
                    document.getElementById('cover_image_error').textContent =
                        'Cover image must be a JPEG, PNG, JPG, or WEBP image';
                    document.getElementById('cover_image_error').classList.remove('hidden');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</x-app-layout>
