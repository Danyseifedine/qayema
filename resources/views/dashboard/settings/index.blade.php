<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.settings') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ __('menu_owner.settings.title') }}</h1>
        </div>

        @if (! $restaurant)
            <x-ui.empty
                title="{{ __('menu_owner.settings.create_menu_first') }}"
                description=""
                icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>'>
                <x-btn href="{{ route('menu-owner.restaurant.index') }}" variant="primary" size="sm">
                    {{ __('menu_owner.menus.create_menu_button') }}
                </x-btn>
            </x-ui.empty>
        @else
            <div class="qf-wrap">
                <form method="POST" action="{{ route('menu-owner.settings.update') }}">
                    @csrf

                    {{-- Display section --}}
                    <div class="qf-section">
                        <div class="qf-section-title">{{ __('menu_owner.settings.section_display') }}</div>
                        <div class="st-toggle-list">

                            <div class="st-toggle-row">
                                <div class="st-toggle-info">
                                    <span class="st-toggle-label">{{ __('menu_owner.settings.show_cover_image') }}</span>
                                    <span class="st-toggle-desc">{{ __('menu_owner.settings.show_cover_image_desc') }}</span>
                                </div>
                                <label class="st-switch">
                                    <input type="hidden" name="show_cover_image" value="0">
                                    <input type="checkbox" name="show_cover_image" value="1"
                                           {{ ($settings['show_cover_image'] ?? true) ? 'checked' : '' }}>
                                    <span class="st-slider"></span>
                                </label>
                            </div>

                            <div class="st-toggle-row">
                                <div class="st-toggle-info">
                                    <span class="st-toggle-label">{{ __('menu_owner.settings.show_prices') }}</span>
                                    <span class="st-toggle-desc">{{ __('menu_owner.settings.show_prices_desc') }}</span>
                                </div>
                                <label class="st-switch">
                                    <input type="hidden" name="show_prices" value="0">
                                    <input type="checkbox" name="show_prices" value="1"
                                           {{ ($settings['show_prices'] ?? true) ? 'checked' : '' }}>
                                    <span class="st-slider"></span>
                                </label>
                            </div>

                            <div class="st-toggle-row">
                                <div class="st-toggle-info">
                                    <span class="st-toggle-label">{{ __('menu_owner.settings.show_phone_number') }}</span>
                                    <span class="st-toggle-desc">{{ __('menu_owner.settings.show_phone_number_desc') }}</span>
                                </div>
                                <label class="st-switch">
                                    <input type="hidden" name="show_phone_number" value="0">
                                    <input type="checkbox" name="show_phone_number" value="1"
                                           {{ ($settings['show_phone_number'] ?? true) ? 'checked' : '' }}>
                                    <span class="st-slider"></span>
                                </label>
                            </div>

                            <div class="st-toggle-row">
                                <div class="st-toggle-info">
                                    <span class="st-toggle-label">{{ __('menu_owner.settings.show_address') }}</span>
                                    <span class="st-toggle-desc">{{ __('menu_owner.settings.show_address_desc') }}</span>
                                </div>
                                <label class="st-switch">
                                    <input type="hidden" name="show_address" value="0">
                                    <input type="checkbox" name="show_address" value="1"
                                           {{ ($settings['show_address'] ?? true) ? 'checked' : '' }}>
                                    <span class="st-slider"></span>
                                </label>
                            </div>

                            <div class="st-toggle-row">
                                <div class="st-toggle-info">
                                    <span class="st-toggle-label">{{ __('menu_owner.settings.show_social_links') }}</span>
                                    <span class="st-toggle-desc">{{ __('menu_owner.settings.show_social_links_desc') }}</span>
                                </div>
                                <label class="st-switch">
                                    <input type="hidden" name="show_social_links" value="0">
                                    <input type="checkbox" name="show_social_links" value="1"
                                           {{ ($settings['show_social_links'] ?? true) ? 'checked' : '' }}>
                                    <span class="st-slider"></span>
                                </label>
                            </div>

                            <div class="st-toggle-row">
                                <div class="st-toggle-info">
                                    <span class="st-toggle-label">{{ __('menu_owner.settings.show_ingredients') }}</span>
                                    <span class="st-toggle-desc">{{ __('menu_owner.settings.show_ingredients_desc') }}</span>
                                </div>
                                <label class="st-switch">
                                    <input type="hidden" name="show_ingredients" value="0">
                                    <input type="checkbox" name="show_ingredients" value="1"
                                           {{ ($settings['show_ingredients'] ?? true) ? 'checked' : '' }}>
                                    <span class="st-slider"></span>
                                </label>
                            </div>

                        </div>
                    </div>

                    {{-- Menu behaviour section --}}
                    <div class="qf-section">
                        <div class="qf-section-title">{{ __('menu_owner.settings.section_behaviour') }}</div>
                        <div class="space-y-5">

                            {{-- Menu direction --}}
                            <x-ui.field name="menu_direction"
                                        label="{{ __('menu_owner.settings.menu_direction') }}"
                                        help="{{ __('menu_owner.settings.menu_direction_desc') }}">
                                <div class="st-dir-toggle">
                                    <label class="st-dir-opt {{ ($settings['menu_direction'] ?? 'ltr') === 'ltr' ? 'active' : '' }}"
                                           x-data>
                                        <input type="radio" name="menu_direction" value="ltr"
                                               {{ ($settings['menu_direction'] ?? 'ltr') === 'ltr' ? 'checked' : '' }}
                                               @change="$el.closest('.st-dir-toggle').querySelectorAll('.st-dir-opt').forEach(o => o.classList.remove('active')); $el.closest('.st-dir-opt').classList.add('active')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><path d="M3 18h7"/></svg>
                                        LTR
                                    </label>
                                    <label class="st-dir-opt {{ ($settings['menu_direction'] ?? 'ltr') === 'rtl' ? 'active' : '' }}"
                                           x-data>
                                        <input type="radio" name="menu_direction" value="rtl"
                                               {{ ($settings['menu_direction'] ?? 'ltr') === 'rtl' ? 'checked' : '' }}
                                               @change="$el.closest('.st-dir-toggle').querySelectorAll('.st-dir-opt').forEach(o => o.classList.remove('active')); $el.closest('.st-dir-opt').classList.add('active')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="12" x2="9" y2="12"/><path d="M21 18h-7"/></svg>
                                        RTL
                                    </label>
                                </div>
                            </x-ui.field>

                            {{-- Share button --}}
                            <div class="st-toggle-row" style="border:none;padding:0">
                                <div class="st-toggle-info">
                                    <span class="st-toggle-label">{{ __('menu_owner.settings.enable_share') }}</span>
                                    <span class="st-toggle-desc">{{ __('menu_owner.settings.enable_share_desc') }}</span>
                                </div>
                                <label class="st-switch">
                                    <input type="hidden" name="enable_share" value="0">
                                    <input type="checkbox" name="enable_share" value="1"
                                           id="enableShare"
                                           {{ ($settings['enable_share'] ?? true) ? 'checked' : '' }}>
                                    <span class="st-slider"></span>
                                </label>
                            </div>

                            <x-ui.field name="share_button_position"
                                        label="{{ __('menu_owner.settings.share_position') }}"
                                        help="{{ __('menu_owner.settings.share_position_desc') }}">
                                <select name="share_button_position" class="ui-select">
                                    @foreach ([
                                        'bottom_right' => __('menu_owner.settings.pos_bottom_right'),
                                        'bottom_left'  => __('menu_owner.settings.pos_bottom_left'),
                                        'top_right'    => __('menu_owner.settings.pos_top_right'),
                                        'top_left'     => __('menu_owner.settings.pos_top_left'),
                                    ] as $val => $label)
                                        <option value="{{ $val }}" @selected(($settings['share_button_position'] ?? 'bottom_right') === $val)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-ui.field>

                        </div>
                    </div>

                    <div class="qf-actions">
                        <button type="submit" class="ui-btn ui-btn-primary">
                            {{ __('menu_owner.common.save_settings') }}
                        </button>
                    </div>

                </form>
            </div>
        @endif
    </div>
</x-sidebar-layout>
