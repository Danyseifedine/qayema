<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Menu Settings') }}
        </h2>
    </x-slot>

    @php
        // Extract values for Alpine.js
        $currencyEnabled = false;
        $fontFamily = 'sans';
        $menuDesign = 'default';
        $categoryCollapsible = true;

        if (isset($groupedSettings['currency']['settings'])) {
            foreach ($groupedSettings['currency']['settings'] as $setting) {
                if ($setting['key'] === 'currency_enabled') {
                    $currencyEnabled = (bool) ($setting['value'] ?? false);
                    break;
                }
            }
        }
        if (isset($groupedSettings['design']['settings'])) {
            foreach ($groupedSettings['design']['settings'] as $setting) {
                if ($setting['key'] === 'font_family') {
                    $fontFamily = $setting['value'] ?? 'sans';
                }
                if ($setting['key'] === 'menu_design') {
                    $menuDesign = $setting['value'] ?? 'default';
                }
                if ($setting['key'] === 'category_collapsible') {
                    $categoryCollapsible = (bool) ($setting['value'] ?? true);
                }
                if ($setting['key'] === 'category_layout') {
                    $categoryLayout = $setting['value'] ?? 'grid';
                }
            }
        }
        $categoryLayout = $categoryLayout ?? 'grid';
        $enableShare = true;
        if (isset($groupedSettings['general']['settings'])) {
            foreach ($groupedSettings['general']['settings'] as $setting) {
                if ($setting['key'] === 'enable_share') {
                    $enableShare = (bool) ($setting['value'] ?? true);
                }
            }
        }
    @endphp
    <div class="py-6 sm:py-12" x-data="{
        activeTab: 'display',
        currencyEnabled: {{ $currencyEnabled ? 'true' : 'false' }},
        selectedFont: '{{ $fontFamily }}',
        menuDesign: '{{ $menuDesign }}',
        categoryLayout: '{{ $categoryLayout }}',
        dishLayout: '{{ $groupedSettings['design']['settings'][array_search('dish_layout', array_column($groupedSettings['design']['settings'] ?? [], 'key'))]['value'] ?? 'default' }}',
        categoryCollapsible: {{ $categoryCollapsible ? 'true' : 'false' }},
        enableShare: {{ $enableShare ? 'true' : 'false' }},
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4 sm:mb-6 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4 sm:mb-6 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if (!$menu)
                <div
                    class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4 sm:mb-6 text-sm">
                    Please create a menu first before configuring settings.
                    <a href="{{ route('menu-owner.menus.index') }}" class="underline ml-2">Go to Menu</a>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Tabs - Scrollable on mobile -->
                    <div class="border-b border-gray-200 overflow-x-auto">
                        <nav class="flex min-w-max" aria-label="Tabs">
                            @foreach ($groupedSettings as $key => $group)
                                <button @click="activeTab = '{{ $key }}'"
                                    :class="activeTab === '{{ $key }}' ?
                                        'border-indigo-500 text-indigo-600 bg-indigo-50/50' :
                                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="flex-1 min-w-[80px] whitespace-nowrap border-b-2 py-3 px-2 sm:px-4 text-center text-xs sm:text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mx-auto mb-1"
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
                                    <span class="hidden sm:inline">{{ $group['title'] }}</span>
                                    <span class="sm:hidden">{{ explode(' ', $group['title'])[0] }}</span>
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <form method="POST" action="{{ route('menu-owner.settings.update') }}" class="p-4 sm:p-6">
                        @csrf
                        @method('PUT')

                        @foreach ($groupedSettings as $key => $group)
                            <div x-show="activeTab === '{{ $key }}'" x-cloak>
                                <div class="space-y-4 sm:space-y-6">
                                    @foreach ($group['settings'] as $setting)
                                        @php
                                            $defaultDesignOnlySettings = ['price_position'];
                                            $isDefaultDesignOnly = in_array(
                                                $setting['key'],
                                                $defaultDesignOnlySettings,
                                            );

                                            // Settings disabled based on category layout
                                            // Grid: All enabled
                                            // Tabs: category_image disabled
                                            // List: category_image, dish_image disabled
                                            // Cards: category_image disabled
                                            // Grid-only settings: category_collapsible, category_default_state
                                            $layoutDisabledSettings = [
                                                'tabs' => ['show_category_image'],
                                                'list' => ['show_category_image', 'show_dish_image'],
                                                'cards' => ['show_category_image'],
                                            ];
                                            $isLayoutDependent = in_array($setting['key'], [
                                                'show_category_image',
                                                'show_dish_image',
                                            ]);

                                            // Grid-only settings (disabled for all layouts except grid)
                                            $gridOnlySettings = ['category_collapsible', 'category_default_state'];
                                            $isGridOnly = in_array($setting['key'], $gridOnlySettings);

                                            // Dish layout is disabled for list and cards (horizontal cards) category layouts
                                            $isDishLayout = $setting['key'] === 'dish_layout';
                                            $shareDependentSettings = ['share_button_position'];
                                            $isShareDependent = in_array($setting['key'], $shareDependentSettings);
                                            $currencyDependentSettings = ['exchange_currency', 'exchange_rate'];
                                            $isCurrencyDependent = in_array(
                                                $setting['key'],
                                                $currencyDependentSettings,
                                            );
                                        @endphp
                                        <div class="border-b border-gray-100 pb-4 sm:pb-6 last:border-b-0 last:pb-0"
                                            @if ($isShareDependent) x-show="enableShare"
                                                x-cloak @endif>
                                            <!-- Setting Row - Stack on mobile, row on desktop -->
                                            @php
                                                $layoutConditionForSetting = collect($layoutDisabledSettings)
                                                    ->map(
                                                        fn($settings, $layout) => in_array($setting['key'], $settings)
                                                            ? "categoryLayout === '$layout'"
                                                            : null,
                                                    )
                                                    ->filter()
                                                    ->implode(' || ');

                                                // Grid-only settings are disabled when layout is not grid
                                                $gridOnlyCondition = $isGridOnly ? "categoryLayout !== 'grid'" : '';

                                                // Dish layout is disabled for list and cards (horizontal cards) category layouts
                                                $dishLayoutCondition = $isDishLayout
                                                    ? "categoryLayout === 'list' || categoryLayout === 'cards'"
                                                    : '';

                                                // Combine conditions for visual indicators
                                                $visualDisabledCondition = '';
                                                $conditions = array_filter([
                                                    $layoutConditionForSetting,
                                                    $gridOnlyCondition,
                                                    $dishLayoutCondition,
                                                ]);
                                                if (count($conditions) > 1) {
                                                    $visualDisabledCondition =
                                                        '(' . implode(') || (', $conditions) . ')';
                                                } elseif (count($conditions) === 1) {
                                                    $visualDisabledCondition = reset($conditions);
                                                }
                                            @endphp
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4"
                                                @if ($visualDisabledCondition) :class="{
                                                        'opacity-50 pointer-events-none': {{ $visualDisabledCondition }}
                                                    }" @endif>
                                                <!-- Label Section -->
                                                <div class="flex-1 min-w-0">
                                                    <label
                                                        class="text-sm sm:text-base font-medium text-gray-900 flex flex-wrap items-center gap-2"
                                                        @if ($visualDisabledCondition) :class="{
                                                                'text-gray-400': {{ $visualDisabledCondition }}
                                                            }" @endif>
                                                        {{ $setting['title'] }}
                                                        @if ($visualDisabledCondition)
                                                            <span x-show="{{ $visualDisabledCondition }}"
                                                                class="text-[10px] sm:text-xs font-normal text-amber-600 bg-amber-50 px-1.5 sm:px-2 py-0.5 rounded-full whitespace-nowrap">
                                                                @if ($isGridOnly)
                                                                    Only available for Grid layout
                                                                @elseif ($isDishLayout)
                                                                    Not available for List or Horizontal Cards layout
                                                                @else
                                                                    Disabled for this layout
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </label>
                                                    @if ($setting['description'])
                                                        <p class="mt-0.5 sm:mt-1 text-xs sm:text-sm text-gray-500">
                                                            {{ $setting['description'] }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <!-- Input Section - Right aligned on desktop -->
                                                <div class="flex-shrink-0 sm:ml-4 flex justify-end">
                                                    @if ($setting['type'] === 'boolean')
                                                        @php
                                                            $disabledCondition = '';
                                                            if ($isCurrencyDependent) {
                                                                $disabledCondition = '!currencyEnabled';
                                                            }
                                                            if ($isLayoutDependent) {
                                                                $layoutConditions = collect($layoutDisabledSettings)
                                                                    ->map(
                                                                        fn($settings, $layout) => in_array(
                                                                            $setting['key'],
                                                                            $settings,
                                                                        )
                                                                            ? "categoryLayout === '$layout'"
                                                                            : null,
                                                                    )
                                                                    ->filter()
                                                                    ->implode(' || ');
                                                                if ($layoutConditions) {
                                                                    $disabledCondition = $disabledCondition
                                                                        ? "($disabledCondition) || ($layoutConditions)"
                                                                        : $layoutConditions;
                                                                }
                                                            }
                                                            if ($isGridOnly) {
                                                                $gridOnlyCondition = "categoryLayout !== 'grid'";
                                                                $disabledCondition = $disabledCondition
                                                                    ? "($disabledCondition) || ($gridOnlyCondition)"
                                                                    : $gridOnlyCondition;
                                                            }
                                                            if ($isDishLayout) {
                                                                $dishLayoutCondition =
                                                                    "categoryLayout === 'list' || categoryLayout === 'cards'";
                                                                $disabledCondition = $disabledCondition
                                                                    ? "($disabledCondition) || ($dishLayoutCondition)"
                                                                    : $dishLayoutCondition;
                                                            }
                                                        @endphp
                                                        <label class="relative inline-flex items-center cursor-pointer"
                                                            @if ($disabledCondition) :class="({{ $disabledCondition }}) ? 'opacity-50 pointer-events-none' : ''" @endif>
                                                            <input type="hidden" name="settings[{{ $setting['id'] }}]"
                                                                value="0">
                                                            <input type="checkbox"
                                                                name="settings[{{ $setting['id'] }}]" value="1"
                                                                {{ $setting['value'] ?? false ? 'checked' : '' }}
                                                                @if ($setting['key'] === 'currency_enabled') x-model="currencyEnabled" @endif
                                                                @if ($setting['key'] === 'category_collapsible') x-model="categoryCollapsible" @endif
                                                                @if ($setting['key'] === 'enable_share') x-model="enableShare" @endif
                                                                @if ($disabledCondition) :disabled="{{ $disabledCondition }}" @endif
                                                                class="sr-only peer"
                                                                onchange="this.previousElementSibling.value = this.checked ? '1' : '0'">
                                                            <div
                                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600">
                                                            </div>
                                                        </label>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'menu_design')
                                                        <select name="settings[{{ $setting['id'] }}]"
                                                            x-model="menuDesign" disabled
                                                            class="block w-full sm:w-48 text-sm rounded-md border-gray-300 shadow-sm bg-gray-100 cursor-not-allowed opacity-50">
                                                            <option value="default">Default</option>
                                                        </select>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'category_layout')
                                                        <select name="settings[{{ $setting['id'] }}]"
                                                            x-model="categoryLayout"
                                                            class="block w-full sm:w-48 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="grid"
                                                                {{ ($setting['value'] ?? 'grid') === 'grid' ? 'selected' : '' }}>
                                                                Grid (Default)</option>
                                                            <option value="tabs"
                                                                {{ ($setting['value'] ?? '') === 'tabs' ? 'selected' : '' }}>
                                                                Tabs</option>
                                                            <option value="list"
                                                                {{ ($setting['value'] ?? '') === 'list' ? 'selected' : '' }}>
                                                                List</option>
                                                            <option value="cards"
                                                                {{ ($setting['value'] ?? '') === 'cards' ? 'selected' : '' }}>
                                                                Horizontal Cards</option>
                                                        </select>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'dish_layout')
                                                        <select name="settings[{{ $setting['id'] }}]"
                                                            x-model="dishLayout"
                                                            :disabled="categoryLayout === 'list' ||
                                                                categoryLayout === 'cards'"
                                                            :class="(categoryLayout === 'list' ||
                                                                categoryLayout === 'cards') ?
                                                            'bg-gray-100 cursor-not-allowed opacity-50' : ''"
                                                            class="block w-full sm:w-48 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="default"
                                                                {{ ($setting['value'] ?? 'default') === 'default' ? 'selected' : '' }}>
                                                                Default</option>
                                                            <option value="compact"
                                                                {{ ($setting['value'] ?? '') === 'compact' ? 'selected' : '' }}>
                                                                Compact</option>
                                                            <option value="minimal"
                                                                {{ ($setting['value'] ?? '') === 'minimal' ? 'selected' : '' }}>
                                                                Minimal</option>
                                                            <option value="decomposed"
                                                                {{ ($setting['value'] ?? '') === 'decomposed' ? 'selected' : '' }}>
                                                                Decomposed</option>
                                                        </select>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'price_position')
                                                        <select name="settings[{{ $setting['id'] }}]"
                                                            class="block w-full sm:w-56 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="next_to_title"
                                                                {{ ($setting['value'] ?? 'bottom_right') === 'next_to_title' ? 'selected' : '' }}>
                                                                Next to Title
                                                            </option>
                                                            <option value="bottom_left"
                                                                {{ ($setting['value'] ?? '') === 'bottom_left' ? 'selected' : '' }}>
                                                                Bottom Left (on image)
                                                            </option>
                                                            <option value="bottom_right"
                                                                {{ ($setting['value'] ?? 'bottom_right') === 'bottom_right' ? 'selected' : '' }}>
                                                                Bottom Right (on image)
                                                            </option>
                                                            <option value="top_left"
                                                                {{ ($setting['value'] ?? '') === 'top_left' ? 'selected' : '' }}>
                                                                Top Left (on image)
                                                            </option>
                                                            <option value="top_right"
                                                                {{ ($setting['value'] ?? '') === 'top_right' ? 'selected' : '' }}>
                                                                Top Right (on image)
                                                            </option>
                                                        </select>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'category_default_state')
                                                        <select name="settings[{{ $setting['id'] }}]"
                                                            :disabled="!categoryCollapsible || categoryLayout !== 'grid'"
                                                            :class="(!categoryCollapsible || categoryLayout !== 'grid') ?
                                                            'bg-gray-100 cursor-not-allowed opacity-50' : ''"
                                                            class="block w-full sm:w-40 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="open"
                                                                {{ ($setting['value'] ?? 'open') === 'open' ? 'selected' : '' }}>
                                                                Open
                                                            </option>
                                                            <option value="closed"
                                                                {{ ($setting['value'] ?? '') === 'closed' ? 'selected' : '' }}>
                                                                Closed
                                                            </option>
                                                        </select>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'share_button_position')
                                                        <select name="settings[{{ $setting['id'] }}]"
                                                            :disabled="!enableShare"
                                                            :class="!enableShare ? 'bg-gray-100 cursor-not-allowed opacity-50' :
                                                                ''"
                                                            class="block w-full sm:w-48 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="bottom_right"
                                                                {{ ($setting['value'] ?? 'bottom_right') === 'bottom_right' ? 'selected' : '' }}>
                                                                Bottom Right
                                                            </option>
                                                            <option value="bottom_left"
                                                                {{ ($setting['value'] ?? '') === 'bottom_left' ? 'selected' : '' }}>
                                                                Bottom Left
                                                            </option>
                                                            <option value="top_right"
                                                                {{ ($setting['value'] ?? '') === 'top_right' ? 'selected' : '' }}>
                                                                Top Right
                                                            </option>
                                                            <option value="top_left"
                                                                {{ ($setting['value'] ?? '') === 'top_left' ? 'selected' : '' }}>
                                                                Top Left
                                                            </option>
                                                        </select>
                                                    @elseif ($setting['type'] === 'string' && $setting['key'] === 'font_family')
                                                        <div class="w-full sm:w-auto sm:text-right">
                                                            <select name="settings[{{ $setting['id'] }}]"
                                                                x-model="selectedFont"
                                                                class="block w-full sm:w-56 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                <optgroup label="System Fonts">
                                                                    <option value="sans"
                                                                        {{ ($setting['value'] ?? 'sans') === 'sans' ? 'selected' : '' }}>
                                                                        Sans Serif (System)</option>
                                                                    <option value="serif"
                                                                        {{ ($setting['value'] ?? '') === 'serif' ? 'selected' : '' }}>
                                                                        Serif (System)</option>
                                                                    <option value="mono"
                                                                        {{ ($setting['value'] ?? '') === 'mono' ? 'selected' : '' }}>
                                                                        Monospace (System)</option>
                                                                    <option value="cursive"
                                                                        {{ ($setting['value'] ?? '') === 'cursive' ? 'selected' : '' }}>
                                                                        Cursive (System)</option>
                                                                </optgroup>
                                                                <optgroup label="Modern Sans Serif">
                                                                    <option value="inter"
                                                                        {{ ($setting['value'] ?? '') === 'inter' ? 'selected' : '' }}>
                                                                        Inter</option>
                                                                    <option value="roboto"
                                                                        {{ ($setting['value'] ?? '') === 'roboto' ? 'selected' : '' }}>
                                                                        Roboto</option>
                                                                    <option value="open-sans"
                                                                        {{ ($setting['value'] ?? '') === 'open-sans' ? 'selected' : '' }}>
                                                                        Open Sans</option>
                                                                    <option value="lato"
                                                                        {{ ($setting['value'] ?? '') === 'lato' ? 'selected' : '' }}>
                                                                        Lato</option>
                                                                    <option value="montserrat"
                                                                        {{ ($setting['value'] ?? '') === 'montserrat' ? 'selected' : '' }}>
                                                                        Montserrat</option>
                                                                    <option value="poppins"
                                                                        {{ ($setting['value'] ?? '') === 'poppins' ? 'selected' : '' }}>
                                                                        Poppins</option>
                                                                    <option value="raleway"
                                                                        {{ ($setting['value'] ?? '') === 'raleway' ? 'selected' : '' }}>
                                                                        Raleway</option>
                                                                    <option value="nunito"
                                                                        {{ ($setting['value'] ?? '') === 'nunito' ? 'selected' : '' }}>
                                                                        Nunito</option>
                                                                    <option value="ubuntu"
                                                                        {{ ($setting['value'] ?? '') === 'ubuntu' ? 'selected' : '' }}>
                                                                        Ubuntu</option>
                                                                    <option value="source-sans-pro"
                                                                        {{ ($setting['value'] ?? '') === 'source-sans-pro' ? 'selected' : '' }}>
                                                                        Source Sans Pro</option>
                                                                    <option value="pt-sans"
                                                                        {{ ($setting['value'] ?? '') === 'pt-sans' ? 'selected' : '' }}>
                                                                        PT Sans</option>
                                                                    <option value="noto-sans"
                                                                        {{ ($setting['value'] ?? '') === 'noto-sans' ? 'selected' : '' }}>
                                                                        Noto Sans</option>
                                                                    <option value="work-sans"
                                                                        {{ ($setting['value'] ?? '') === 'work-sans' ? 'selected' : '' }}>
                                                                        Work Sans</option>
                                                                    <option value="rubik"
                                                                        {{ ($setting['value'] ?? '') === 'rubik' ? 'selected' : '' }}>
                                                                        Rubik</option>
                                                                    <option value="quicksand"
                                                                        {{ ($setting['value'] ?? '') === 'quicksand' ? 'selected' : '' }}>
                                                                        Quicksand</option>
                                                                    <option value="karla"
                                                                        {{ ($setting['value'] ?? '') === 'karla' ? 'selected' : '' }}>
                                                                        Karla</option>
                                                                    <option value="dm-sans"
                                                                        {{ ($setting['value'] ?? '') === 'dm-sans' ? 'selected' : '' }}>
                                                                        DM Sans</option>
                                                                    <option value="manrope"
                                                                        {{ ($setting['value'] ?? '') === 'manrope' ? 'selected' : '' }}>
                                                                        Manrope</option>
                                                                    <option value="outfit"
                                                                        {{ ($setting['value'] ?? '') === 'outfit' ? 'selected' : '' }}>
                                                                        Outfit</option>
                                                                    <option value="plus-jakarta-sans"
                                                                        {{ ($setting['value'] ?? '') === 'plus-jakarta-sans' ? 'selected' : '' }}>
                                                                        Plus Jakarta Sans</option>
                                                                    <option value="space-grotesk"
                                                                        {{ ($setting['value'] ?? '') === 'space-grotesk' ? 'selected' : '' }}>
                                                                        Space Grotesk</option>
                                                                    <option value="josefin-sans"
                                                                        {{ ($setting['value'] ?? '') === 'josefin-sans' ? 'selected' : '' }}>
                                                                        Josefin Sans</option>
                                                                </optgroup>
                                                                <optgroup label="Serif Fonts">
                                                                    <option value="playfair"
                                                                        {{ ($setting['value'] ?? '') === 'playfair' ? 'selected' : '' }}>
                                                                        Playfair Display</option>
                                                                    <option value="merriweather"
                                                                        {{ ($setting['value'] ?? '') === 'merriweather' ? 'selected' : '' }}>
                                                                        Merriweather</option>
                                                                    <option value="crimson-text"
                                                                        {{ ($setting['value'] ?? '') === 'crimson-text' ? 'selected' : '' }}>
                                                                        Crimson Text</option>
                                                                    <option value="lora"
                                                                        {{ ($setting['value'] ?? '') === 'lora' ? 'selected' : '' }}>
                                                                        Lora</option>
                                                                    <option value="libre-baskerville"
                                                                        {{ ($setting['value'] ?? '') === 'libre-baskerville' ? 'selected' : '' }}>
                                                                        Libre Baskerville</option>
                                                                    <option value="pt-serif"
                                                                        {{ ($setting['value'] ?? '') === 'pt-serif' ? 'selected' : '' }}>
                                                                        PT Serif</option>
                                                                    <option value="eb-garamond"
                                                                        {{ ($setting['value'] ?? '') === 'eb-garamond' ? 'selected' : '' }}>
                                                                        EB Garamond</option>
                                                                    <option value="cormorant-garamond"
                                                                        {{ ($setting['value'] ?? '') === 'cormorant-garamond' ? 'selected' : '' }}>
                                                                        Cormorant Garamond</option>
                                                                    <option value="libre-caslon-text"
                                                                        {{ ($setting['value'] ?? '') === 'libre-caslon-text' ? 'selected' : '' }}>
                                                                        Libre Caslon Text</option>
                                                                </optgroup>
                                                            </select>
                                                            <!-- Font Preview -->
                                                            <div
                                                                class="mt-3 p-3 sm:p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border border-gray-200 shadow-sm">
                                                                <p
                                                                    class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                                                    Live Preview</p>
                                                                <div class="space-y-2">
                                                                    <p class="text-xl sm:text-2xl font-semibold leading-tight"
                                                                        :style="getFontStyle(selectedFont)">
                                                                        The quick brown fox
                                                                    </p>
                                                                    <p class="text-sm sm:text-base leading-relaxed"
                                                                        :style="getFontStyle(selectedFont)">
                                                                        Sample menu item - $12.99
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif ($setting['type'] === 'string')
                                                        @if ($setting['key'] === 'language')
                                                            <div>
                                                                <input type="text"
                                                                    name="settings[{{ $setting['id'] }}]"
                                                                    value="{{ $setting['value'] ?? '' }}"
                                                                    placeholder="e.g., en, ar" disabled
                                                                    class="block w-full sm:w-40 text-sm rounded-md border-gray-300 shadow-sm bg-gray-100 cursor-not-allowed">
                                                                <p class="mt-1 text-[10px] sm:text-xs text-gray-400">
                                                                    Coming soon</p>
                                                            </div>
                                                        @elseif ($setting['key'] === 'exchange_currency')
                                                            <input type="text"
                                                                name="settings[{{ $setting['id'] }}]"
                                                                value="{{ $setting['value'] ?? '' }}"
                                                                placeholder="e.g., LBP, EUR"
                                                                :disabled="!currencyEnabled"
                                                                :class="!currencyEnabled ?
                                                                    'bg-gray-100 cursor-not-allowed opacity-50' : ''"
                                                                class="block w-full sm:w-40 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @else
                                                            <input type="text"
                                                                name="settings[{{ $setting['id'] }}]"
                                                                value="{{ $setting['value'] ?? '' }}"
                                                                class="block w-full sm:w-48 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @endif
                                                    @elseif ($setting['type'] === 'float' || $setting['type'] === 'integer')
                                                        <input type="number" name="settings[{{ $setting['id'] }}]"
                                                            value="{{ $setting['value'] ?? '' }}"
                                                            step="{{ $setting['type'] === 'float' ? '0.01' : '1' }}"
                                                            @if ($setting['key'] === 'exchange_rate') placeholder="e.g., 15000"
                                                                :disabled="!currencyEnabled"
                                                                :class="!currencyEnabled ? 'bg-gray-100 cursor-not-allowed opacity-50' : ''" @endif
                                                            class="block w-full sm:w-40 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <!-- Save Button -->
                        <div
                            class="mt-6 sm:mt-8 flex items-center justify-end gap-4 border-t border-gray-200 pt-4 sm:pt-6">
                            <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
