<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('QR Code') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (!$menu)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-gray-600">Please create a menu first to generate a QR code.</p>
                        <a href="{{ route('menu-owner.menus.index') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Create Menu
                        </a>
                    </div>
                </div>
            @elseif(!$menu->is_active)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-gray-600">Please activate your menu first to generate a QR code.</p>
                        <a href="{{ route('menu-owner.menus.index') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Activate Menu
                        </a>
                    </div>
                </div>
            @else
                <!-- QR Code Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">QR Code for Your Menu</h3>
                            <p class="text-gray-600 mb-8">Scan this QR code to access your digital menu</p>

                            <!-- QR Code Display Area -->
                            <div class="flex justify-center mb-8" id="qrCodeContainer">
                                @if($menuUrl)
                                    <div class="bg-white p-4 rounded-lg border-2 border-gray-200 inline-block">
                                        <img src="{{ route('menu-owner.qr-code.generate') }}" 
                                             alt="QR Code" 
                                             id="qrCodeImage"
                                             class="w-64 h-64">
                                    </div>
                                @endif
                            </div>

                            <!-- Generate Button -->
                            <button onclick="generateQRCode()" 
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Generate QR Code
                            </button>

                            <!-- Menu URL -->
                            @if($menuUrl)
                                <div class="mt-8">
                                    <p class="text-sm text-gray-600 mb-2">Menu URL:</p>
                                    <div class="flex items-center gap-2 max-w-2xl mx-auto">
                                        <input type="text" 
                                               id="menuUrl" 
                                               readonly
                                               value="{{ $menuUrl }}"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <button onclick="copyMenuUrl()"
                                                class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors text-sm font-medium">
                                            Copy
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <!-- Instructions -->
                            <div class="mt-8 text-left max-w-2xl mx-auto">
                                <h4 class="font-semibold text-gray-900 mb-3">How to use:</h4>
                                <ul class="space-y-2 text-sm text-gray-600">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Click "Generate QR Code" to create your QR code</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Download or print the QR code</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Place it on tables, menus, or promotional materials</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Customers can scan with their phone camera to view your menu</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function generateQRCode() {
            const qrCodeImage = document.getElementById('qrCodeImage');
            if (qrCodeImage) {
                // Add timestamp to force refresh
                const timestamp = new Date().getTime();
                qrCodeImage.src = '{{ route("menu-owner.qr-code.generate") }}?t=' + timestamp;
            }
        }

        function copyMenuUrl() {
            const menuUrlInput = document.getElementById('menuUrl');
            if (menuUrlInput) {
                menuUrlInput.select();
                menuUrlInput.setSelectionRange(0, 99999); // For mobile devices
                document.execCommand('copy');
                
                // Show feedback
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('bg-green-600');
                button.classList.remove('bg-gray-600');
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-600');
                    button.classList.add('bg-gray-600');
                }, 2000);
            }
        }
    </script>
</x-app-layout>
