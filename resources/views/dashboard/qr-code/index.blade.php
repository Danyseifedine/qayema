<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.qr_code') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ __('menu_owner.qr_code.title') }}</h1>
        </div>

        @if (! $restaurant)
            <x-ui.empty
                title="{{ __('menu_owner.qr_code.create_menu_first') }}"
                description=""
                icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'>
                <x-btn href="{{ route('menu-owner.restaurant.index') }}" variant="primary" size="sm">
                    {{ __('menu_owner.menus.create_menu_button') }}
                </x-btn>
            </x-ui.empty>
        @elseif (! $restaurant->is_active)
            <x-ui.empty
                title="{{ __('menu_owner.qr_code.activate_menu_first') }}"
                description=""
                icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'>
                <x-btn href="{{ route('menu-owner.restaurant.index') }}" variant="primary" size="sm">
                    {{ __('menu_owner.qr_code.activate_menu') }}
                </x-btn>
            </x-ui.empty>
        @else

            {{-- Stats row --}}
            <div class="qr-stats-row">
                <div class="qr-stat-card">
                    <span class="qr-stat-value">{{ number_format($stats['total']) }}</span>
                    <span class="qr-stat-label">{{ __('menu_owner.qr_code.stats_total') }}</span>
                </div>
                <div class="qr-stat-card">
                    <span class="qr-stat-value">{{ number_format($stats['month']) }}</span>
                    <span class="qr-stat-label">{{ __('menu_owner.qr_code.stats_month') }}</span>
                </div>
                <div class="qr-stat-card">
                    <span class="qr-stat-value">{{ number_format($stats['week']) }}</span>
                    <span class="qr-stat-label">{{ __('menu_owner.qr_code.stats_week') }}</span>
                </div>
                <div class="qr-stat-card">
                    <span class="qr-stat-value">{{ number_format($stats['today']) }}</span>
                    <span class="qr-stat-label">{{ __('menu_owner.qr_code.stats_today') }}</span>
                </div>
            </div>
            <p class="qr-stats-note">{{ __('menu_owner.qr_code.stats_desc') }}</p>

            {{-- Main panel --}}
            <div class="qr-main-grid"
                 x-data="{
                     fg: @js($qrSettings['fg_color']),
                     bg: @js($qrSettings['bg_color']),
                     ec: @js($qrSettings['error_correction']),
                     saving: false,
                     get previewSrc() {
                         return '{{ route('menu-owner.qr-code.generate') }}?fg=' + encodeURIComponent(this.fg) + '&bg=' + encodeURIComponent(this.bg) + '&ec=' + this.ec + '&t=' + Date.now();
                     },
                     refreshPreview() {
                         this.$refs.qrImg.src = this.previewSrc;
                     },
                     _luminance(hex) {
                         const c = (hex || '').replace('#', '');
                         if (! /^[0-9a-fA-F]{6}$/.test(c)) { return null; }
                         const chan = [0, 2, 4].map(i => {
                             const v = parseInt(c.substr(i, 2), 16) / 255;
                             return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
                         });
                         return 0.2126 * chan[0] + 0.7152 * chan[1] + 0.0722 * chan[2];
                     },
                     get lowContrast() {
                         const l1 = this._luminance(this.fg);
                         const l2 = this._luminance(this.bg);
                         if (l1 === null || l2 === null) { return false; }
                         const [hi, lo] = l1 > l2 ? [l1, l2] : [l2, l1];
                         return (hi + 0.05) / (lo + 0.05) < 3;
                     },
                     async save() {
                         this.saving = true;
                         const res = await fetch('{{ route('menu-owner.qr-code.save-settings') }}', {
                             method: 'POST',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                 'Accept': 'application/json',
                             },
                             body: JSON.stringify({ fg_color: this.fg, bg_color: this.bg, error_correction: this.ec }),
                         });
                         this.saving = false;
                         if (res.ok) {
                             this.refreshPreview();
                             window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('menu_owner.qr_code.settings_saved') }}', type: 'success' } }));
                         }
                     },
                 }">

                {{-- Left: QR preview + URL --}}
                <div class="qr-preview-col">
                    <div class="qr-preview-card">
                        <h3 class="qr-preview-title">{{ __('menu_owner.qr_code.qr_for_menu') }}</h3>

                        <div class="qr-image-wrap">
                            <img x-ref="qrImg"
                                 src="{{ route('menu-owner.qr-code.generate') }}"
                                 alt="QR Code"
                                 class="qr-image">
                        </div>

                        <div class="qr-actions">
                            <a :href="'{{ route('menu-owner.qr-code.generate') }}?fg=' + encodeURIComponent(fg) + '&bg=' + encodeURIComponent(bg) + '&ec=' + ec"
                               download="menu-qr.svg"
                               class="qr-dl-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                {{ __('menu_owner.qr_code.download_svg') }}
                            </a>
                        </div>

                        <div class="qr-url-wrap">
                            <label class="qr-url-label">{{ __('menu_owner.qr_code.menu_url') }}</label>
                            <div class="qr-url-row">
                                <input type="text" id="menuUrl" readonly value="{{ $menuUrl }}" class="qr-url-input">
                                <button type="button" class="qr-copy-btn"
                                        x-data="{ copied: false }"
                                        @click="navigator.clipboard.writeText('{{ $menuUrl }}'); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-show="!copied">{{ __('menu_owner.qr_code.copy') }}</span>
                                    <span x-show="copied" x-cloak>{{ __('menu_owner.qr_code.copied') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- How to use --}}
                    <div class="qr-howto">
                        <h4 class="qr-howto-title">{{ __('menu_owner.qr_code.how_to_use') }}</h4>
                        <ul class="qr-howto-list">
                            @foreach (['instruction_1','instruction_2','instruction_3','instruction_4'] as $key)
                                <li>
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ __('menu_owner.qr_code.'.$key) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Right: Customizer --}}
                <div class="qr-custom-col">
                    <div class="qr-custom-card">
                        <h3 class="qr-custom-title">{{ __('menu_owner.qr_code.customize') }}</h3>

                        <div class="qr-field">
                            <label class="qr-label">{{ __('menu_owner.qr_code.fg_color') }}</label>
                            <div class="qr-color-row">
                                <input type="color" x-model="fg" @change="refreshPreview()" class="qr-color-swatch">
                                <input type="text" x-model="fg" @change="refreshPreview()" maxlength="7" class="qr-color-text"
                                       pattern="^#[0-9a-fA-F]{6}$">
                            </div>
                        </div>

                        <div class="qr-field">
                            <label class="qr-label">{{ __('menu_owner.qr_code.bg_color') }}</label>
                            <div class="qr-color-row">
                                <input type="color" x-model="bg" @change="refreshPreview()" class="qr-color-swatch">
                                <input type="text" x-model="bg" @change="refreshPreview()" maxlength="7" class="qr-color-text"
                                       pattern="^#[0-9a-fA-F]{6}$">
                            </div>
                        </div>

                        <div class="qr-field">
                            <label class="qr-label">{{ __('menu_owner.qr_code.error_correction') }}</label>
                            <select x-model="ec" @change="refreshPreview()" class="qr-select">
                                <option value="L">{{ __('menu_owner.qr_code.ec_l') }}</option>
                                <option value="M">{{ __('menu_owner.qr_code.ec_m') }}</option>
                                <option value="Q">{{ __('menu_owner.qr_code.ec_q') }}</option>
                                <option value="H">{{ __('menu_owner.qr_code.ec_h') }}</option>
                            </select>
                            <p class="qr-field-hint">{{ __('menu_owner.qr_code.ec_desc') }}</p>
                        </div>

                        <button type="button" class="qr-save-btn" @click="save()" :disabled="saving">
                            <span x-show="!saving">{{ __('menu_owner.qr_code.save_settings') }}</span>
                            <span x-show="saving" x-cloak>…</span>
                        </button>

                        <p class="qr-contrast-warn" x-show="lowContrast" x-cloak
                           style="display:flex;gap:8px;align-items:flex-start;margin-top:12px;padding:10px 12px;border-radius:8px;background:rgba(231,76,60,.08);border:1px solid rgba(231,76,60,.25);color:#c0392b;font-size:12.5px;line-height:1.45">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>{{ __('menu_owner.qr_code.low_contrast') }}</span>
                        </p>

                        <p class="qr-preview-hint">{{ __('menu_owner.qr_code.preview_live') }}</p>
                    </div>
                </div>

            </div>
        @endif
    </div>
</x-sidebar-layout>
