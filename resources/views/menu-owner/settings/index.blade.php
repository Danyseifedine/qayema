<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Menu Settings') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ 
        activeTab: 'display',
        currencyEnabled: @php
            $currencyEnabled = false;
            if (isset($groupedSettings['currency']['settings'])) {
                foreach ($groupedSettings['currency']['settings'] as $setting) {
                    if ($setting['key'] === 'currency_enabled') {
                        $currencyEnabled = (bool)($setting['value'] ?? false);
                        break;
                    }
                }
            }
            echo $currencyEnabled ? 'true' : 'false';
        @endphp,
        selectedFont: @php
            $fontFamily = 'sans';
            if (isset($groupedSettings['design']['settings'])) {
                foreach ($groupedSettings['design']['settings'] as $setting) {
                    if ($setting['key'] === 'font_family') {
                        $fontFamily = $setting['value'] ?? 'sans';
                        break;
                    }
                }
            }
            echo "'" . $fontFamily . "'";
        @endphp,
        getFontStyle(font) {
            const fontMap = {
                'sans': 'font-family: system-ui, -apple-system, sans-serif;',
                'serif': 'font-family: Georgia, serif;',
                'mono': 'font-family: \'Courier New\', monospace;',
                'cursive': 'font-family: \'Brush Script MT\', cursive;',
                'inter': 'font-family: \'Inter\', system-ui, sans-serif;',
                'roboto': 'font-family: \'Roboto\', sans-serif;',
                'open-sans': 'font-family: \'Open Sans\', sans-serif;',
                'lato': 'font-family: \'Lato\', sans-serif;',
                'montserrat': 'font-family: \'Montserrat\', sans-serif;',
                'poppins': 'font-family: \'Poppins\', sans-serif;',
                'raleway': 'font-family: \'Raleway\', sans-serif;',
                'nunito': 'font-family: \'Nunito\', sans-serif;',
                'ubuntu': 'font-family: \'Ubuntu\', sans-serif;',
                'source-sans-pro': 'font-family: \'Source Sans Pro\', sans-serif;',
                'pt-sans': 'font-family: \'PT Sans\', sans-serif;',
                'noto-sans': 'font-family: \'Noto Sans\', sans-serif;',
                'work-sans': 'font-family: \'Work Sans\', sans-serif;',
                'rubik': 'font-family: \'Rubik\', sans-serif;',
                'quicksand': 'font-family: \'Quicksand\', sans-serif;',
                'karla': 'font-family: \'Karla\', sans-serif;',
                'dm-sans': 'font-family: \'DM Sans\', sans-serif;',
                'manrope': 'font-family: \'Manrope\', sans-serif;',
                'outfit': 'font-family: \'Outfit\', sans-serif;',
                'plus-jakarta-sans': 'font-family: \'Plus Jakarta Sans\', sans-serif;',
                'space-grotesk': 'font-family: \'Space Grotesk\', sans-serif;',
                'josefin-sans': 'font-family: \'Josefin Sans\', sans-serif;',
                'playfair': 'font-family: \'Playfair Display\', serif;',
                'merriweather': 'font-family: \'Merriweather\', serif;',
                'crimson-text': 'font-family: \'Crimson Text\', serif;',
                'lora': 'font-family: \'Lora\', serif;',
                'libre-baskerville': 'font-family: \'Libre Baskerville\', serif;',
                'pt-serif': 'font-family: \'PT Serif\', serif;',
                'eb-garamond': 'font-family: \'EB Garamond\', serif;',
                'cormorant-garamond': 'font-family: \'Cormorant Garamond\', serif;',
                'libre-caslon-text': 'font-family: \'Libre Caslon Text\', serif;'
            };
            return fontMap[font] || fontMap['sans'];
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            @if (!$menu)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
                    Please create a menu first before configuring settings.
                    <a href="{{ route('menu-owner.menus.index') }}" class="underline ml-2">Go to Menu</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px" aria-label="Tabs">
                            @foreach ($groupedSettings as $key => $group)
                                <button @click="activeTab = '{{ $key }}'"
                                    :class="activeTab === '{{ $key }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="flex-1 whitespace-nowrap border-b-2 py-4 px-1 text-center text-sm font-medium transition-colors">
                                    <svg class="w-5 h-5 mx-auto mb-1"
                                        :class="activeTab === '{{ $key }}' ? 'text-indigo-600' : 'text-gray-400'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if ($group['icon'] === 'eye')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        @elseif ($group['icon'] === 'currency-dollar')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @elseif ($group['icon'] === 'paint-brush')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21a4 4 0 01-4-4V5a2 2 0 014 0v12a4 4 0 01-4 4zm0 0h12M7 21h12M7 21v-4m12 4v-4M7 17h.01M19 17h.01M19 13h.01M7 13h.01m12 0h.01M7 9h.01M19 9h.01" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        @endif
                                    </svg>
                                    {{ $group['title'] }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <form method="POST" action="{{ route('menu-owner.settings.update') }}" class="p-6">
                        @csrf
                        @method('PUT')

                        @foreach ($groupedSettings as $key => $group)
                            <div x-show="activeTab === '{{ $key }}'" x-cloak>
                                <div class="space-y-6">
                                    @foreach ($group['settings'] as $setting)
                                        <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <label class="text-base font-medium text-gray-900">
                                                        {{ $setting['title'] }}
                                                    </label>
                                                    @if ($setting['description'])
                                                        <p class="mt-1 text-sm text-gray-500">{{ $setting['description'] }}</p>
                                                    @endif
                                                </div>

                                                <div class="ml-4 flex-shrink-0">
                                                    @if ($setting['type'] === 'boolean')
                                                        <label class="relative inline-flex items-center cursor-pointer">
                                                            <input type="hidden" name="settings[{{ $setting['id'] }}]" value="0">
                                                            <input type="checkbox" name="settings[{{ $setting['id'] }}]"
                                                                value="1"
                                                                {{ ($setting['value'] ?? false) ? 'checked' : '' }}
                                                                @if ($setting['key'] === 'currency_enabled')
                                                                    x-model="currencyEnabled"
                                                                @endif
                                                                class="sr-only peer"
                                                                onchange="this.previousElementSibling.value = this.checked ? '1' : '0'">
                                                            <div
                                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600">
                                                            </div>
                                                        </label>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'menu_design')
                                                        <select name="settings[{{ $setting['id'] }}]"
                                                            class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="default" {{ ($setting['value'] ?? 'default') === 'default' ? 'selected' : '' }}>
                                                                Default
                                                            </option>
                                                            <option value="modern" {{ ($setting['value'] ?? '') === 'modern' ? 'selected' : '' }}>
                                                                Modern
                                                            </option>
                                                            <option value="classic" {{ ($setting['value'] ?? '') === 'classic' ? 'selected' : '' }}>
                                                                Classic
                                                            </option>
                                                            <option value="minimal" {{ ($setting['value'] ?? '') === 'minimal' ? 'selected' : '' }}>
                                                                Minimal
                                                            </option>
                                                        </select>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'font_family')
                                                        <div class="mt-1">
                                                            <select name="settings[{{ $setting['id'] }}]"
                                                                x-model="selectedFont"
                                                                class="block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                <optgroup label="System Fonts">
                                                                    <option value="sans" {{ ($setting['value'] ?? 'sans') === 'sans' ? 'selected' : '' }}>
                                                                        Sans Serif (System Default)
                                                                    </option>
                                                                    <option value="serif" {{ ($setting['value'] ?? '') === 'serif' ? 'selected' : '' }}>
                                                                        Serif (System Default)
                                                                    </option>
                                                                    <option value="mono" {{ ($setting['value'] ?? '') === 'mono' ? 'selected' : '' }}>
                                                                        Monospace (System Default)
                                                                    </option>
                                                                    <option value="cursive" {{ ($setting['value'] ?? '') === 'cursive' ? 'selected' : '' }}>
                                                                        Cursive (System Default)
                                                                    </option>
                                                                </optgroup>
                                                                <optgroup label="Modern Sans Serif">
                                                                    <option value="inter" {{ ($setting['value'] ?? '') === 'inter' ? 'selected' : '' }}>
                                                                        Inter
                                                                    </option>
                                                                    <option value="roboto" {{ ($setting['value'] ?? '') === 'roboto' ? 'selected' : '' }}>
                                                                        Roboto
                                                                    </option>
                                                                    <option value="open-sans" {{ ($setting['value'] ?? '') === 'open-sans' ? 'selected' : '' }}>
                                                                        Open Sans
                                                                    </option>
                                                                    <option value="lato" {{ ($setting['value'] ?? '') === 'lato' ? 'selected' : '' }}>
                                                                        Lato
                                                                    </option>
                                                                    <option value="montserrat" {{ ($setting['value'] ?? '') === 'montserrat' ? 'selected' : '' }}>
                                                                        Montserrat
                                                                    </option>
                                                                    <option value="poppins" {{ ($setting['value'] ?? '') === 'poppins' ? 'selected' : '' }}>
                                                                        Poppins
                                                                    </option>
                                                                    <option value="raleway" {{ ($setting['value'] ?? '') === 'raleway' ? 'selected' : '' }}>
                                                                        Raleway
                                                                    </option>
                                                                    <option value="nunito" {{ ($setting['value'] ?? '') === 'nunito' ? 'selected' : '' }}>
                                                                        Nunito
                                                                    </option>
                                                                    <option value="ubuntu" {{ ($setting['value'] ?? '') === 'ubuntu' ? 'selected' : '' }}>
                                                                        Ubuntu
                                                                    </option>
                                                                    <option value="source-sans-pro" {{ ($setting['value'] ?? '') === 'source-sans-pro' ? 'selected' : '' }}>
                                                                        Source Sans Pro
                                                                    </option>
                                                                    <option value="pt-sans" {{ ($setting['value'] ?? '') === 'pt-sans' ? 'selected' : '' }}>
                                                                        PT Sans
                                                                    </option>
                                                                    <option value="noto-sans" {{ ($setting['value'] ?? '') === 'noto-sans' ? 'selected' : '' }}>
                                                                        Noto Sans
                                                                    </option>
                                                                    <option value="work-sans" {{ ($setting['value'] ?? '') === 'work-sans' ? 'selected' : '' }}>
                                                                        Work Sans
                                                                    </option>
                                                                    <option value="rubik" {{ ($setting['value'] ?? '') === 'rubik' ? 'selected' : '' }}>
                                                                        Rubik
                                                                    </option>
                                                                    <option value="quicksand" {{ ($setting['value'] ?? '') === 'quicksand' ? 'selected' : '' }}>
                                                                        Quicksand
                                                                    </option>
                                                                    <option value="karla" {{ ($setting['value'] ?? '') === 'karla' ? 'selected' : '' }}>
                                                                        Karla
                                                                    </option>
                                                                    <option value="dm-sans" {{ ($setting['value'] ?? '') === 'dm-sans' ? 'selected' : '' }}>
                                                                        DM Sans
                                                                    </option>
                                                                    <option value="manrope" {{ ($setting['value'] ?? '') === 'manrope' ? 'selected' : '' }}>
                                                                        Manrope
                                                                    </option>
                                                                    <option value="outfit" {{ ($setting['value'] ?? '') === 'outfit' ? 'selected' : '' }}>
                                                                        Outfit
                                                                    </option>
                                                                    <option value="plus-jakarta-sans" {{ ($setting['value'] ?? '') === 'plus-jakarta-sans' ? 'selected' : '' }}>
                                                                        Plus Jakarta Sans
                                                                    </option>
                                                                    <option value="space-grotesk" {{ ($setting['value'] ?? '') === 'space-grotesk' ? 'selected' : '' }}>
                                                                        Space Grotesk
                                                                    </option>
                                                                    <option value="josefin-sans" {{ ($setting['value'] ?? '') === 'josefin-sans' ? 'selected' : '' }}>
                                                                        Josefin Sans
                                                                    </option>
                                                                </optgroup>
                                                                <optgroup label="Serif Fonts">
                                                                    <option value="playfair" {{ ($setting['value'] ?? '') === 'playfair' ? 'selected' : '' }}>
                                                                        Playfair Display
                                                                    </option>
                                                                    <option value="merriweather" {{ ($setting['value'] ?? '') === 'merriweather' ? 'selected' : '' }}>
                                                                        Merriweather
                                                                    </option>
                                                                    <option value="crimson-text" {{ ($setting['value'] ?? '') === 'crimson-text' ? 'selected' : '' }}>
                                                                        Crimson Text
                                                                    </option>
                                                                    <option value="lora" {{ ($setting['value'] ?? '') === 'lora' ? 'selected' : '' }}>
                                                                        Lora
                                                                    </option>
                                                                    <option value="libre-baskerville" {{ ($setting['value'] ?? '') === 'libre-baskerville' ? 'selected' : '' }}>
                                                                        Libre Baskerville
                                                                    </option>
                                                                    <option value="pt-serif" {{ ($setting['value'] ?? '') === 'pt-serif' ? 'selected' : '' }}>
                                                                        PT Serif
                                                                    </option>
                                                                    <option value="eb-garamond" {{ ($setting['value'] ?? '') === 'eb-garamond' ? 'selected' : '' }}>
                                                                        EB Garamond
                                                                    </option>
                                                                    <option value="cormorant-garamond" {{ ($setting['value'] ?? '') === 'cormorant-garamond' ? 'selected' : '' }}>
                                                                        Cormorant Garamond
                                                                    </option>
                                                                    <option value="libre-caslon-text" {{ ($setting['value'] ?? '') === 'libre-caslon-text' ? 'selected' : '' }}>
                                                                        Libre Caslon Text
                                                                    </option>
                                                                </optgroup>
                                                            </select>
                                                            <!-- Font Preview -->
                                                            <div class="mt-4 p-5 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border-2 border-gray-200 shadow-sm">
                                                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Live Preview</p>
                                                                <div class="space-y-3">
                                                                    <p class="text-3xl font-semibold leading-tight" :style="getFontStyle(selectedFont)">
                                                                        The quick brown fox jumps over the lazy dog
                                                                    </p>
                                                                    <p class="text-lg leading-relaxed" :style="getFontStyle(selectedFont)">
                                                                        Sample menu item with price
                                                                    </p>
                                                                    <div class="pt-2 border-t border-gray-300">
                                                                        <p class="text-xs text-gray-600 mb-1">Character Set:</p>
                                                                        <p class="text-sm leading-relaxed" :style="getFontStyle(selectedFont)">
                                                                            ABCDEFGHIJKLMNOPQRSTUVWXYZ<br>
                                                                            abcdefghijklmnopqrstuvwxyz<br>
                                                                            0123456789 !@#$%^&*()
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif ($setting['type'] === 'string')
                                                        @if ($setting['key'] === 'language')
                                                            <input type="text" name="settings[{{ $setting['id'] }}]"
                                                                value="{{ $setting['value'] ?? '' }}"
                                                                placeholder="e.g., en, ar"
                                                                disabled
                                                                class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm bg-gray-100 cursor-not-allowed">
                                                            <p class="mt-1 text-xs text-gray-500">Language setting is currently disabled</p>
                                                        @elseif ($setting['key'] === 'exchange_currency')
                                                            <input type="text" name="settings[{{ $setting['id'] }}]"
                                                                value="{{ $setting['value'] ?? '' }}"
                                                                placeholder="e.g., LBP, EUR"
                                                                :disabled="!currencyEnabled"
                                                                :class="!currencyEnabled ? 'bg-gray-100 cursor-not-allowed' : ''"
                                                                class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @else
                                                            <input type="text" name="settings[{{ $setting['id'] }}]"
                                                                value="{{ $setting['value'] ?? '' }}"
                                                                class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @endif
                                                    @elseif ($setting['type'] === 'float' || $setting['type'] === 'integer')
                                                        <input type="number" name="settings[{{ $setting['id'] }}]"
                                                            value="{{ $setting['value'] ?? '' }}"
                                                            step="{{ $setting['type'] === 'float' ? '0.01' : '1' }}"
                                                            @if ($setting['key'] === 'exchange_rate')
                                                                placeholder="e.g., 15000"
                                                                :disabled="!currencyEnabled"
                                                                :class="!currencyEnabled ? 'bg-gray-100 cursor-not-allowed' : ''"
                                                            @endif
                                                            class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <!-- Save Button -->
                        <div class="mt-8 flex items-center justify-end gap-4 border-t border-gray-200 pt-6">
                            <button type="submit"
                                class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
