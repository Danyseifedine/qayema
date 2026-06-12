<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.restaurant') }}</span>
    </x-slot>

    {{-- Phone picker component --}}
    <script>
    function phonePicker(initCode, initPhone) {
        const COUNTRIES = [
            ['AF','Afghanistan','93'],['AL','Albania','355'],['DZ','Algeria','213'],
            ['AS','American Samoa','1684'],['AD','Andorra','376'],['AO','Angola','244'],
            ['AI','Anguilla','1264'],['AG','Antigua and Barbuda','1268'],['AR','Argentina','54'],
            ['AM','Armenia','374'],['AW','Aruba','297'],['AU','Australia','61'],
            ['AT','Austria','43'],['AZ','Azerbaijan','994'],['BS','Bahamas','1242'],
            ['BH','Bahrain','973'],['BD','Bangladesh','880'],['BB','Barbados','1246'],
            ['BY','Belarus','375'],['BE','Belgium','32'],['BZ','Belize','501'],
            ['BJ','Benin','229'],['BM','Bermuda','1441'],['BT','Bhutan','975'],
            ['BO','Bolivia','591'],['BA','Bosnia and Herzegovina','387'],['BW','Botswana','267'],
            ['BR','Brazil','55'],['BN','Brunei','673'],['BG','Bulgaria','359'],
            ['BF','Burkina Faso','226'],['BI','Burundi','257'],['KH','Cambodia','855'],
            ['CM','Cameroon','237'],['CA','Canada','1'],['CV','Cape Verde','238'],
            ['KY','Cayman Islands','1345'],['CF','Central African Republic','236'],['TD','Chad','235'],
            ['CL','Chile','56'],['CN','China','86'],['CO','Colombia','57'],
            ['KM','Comoros','269'],['CD','Congo (DRC)','243'],['CG','Congo (Republic)','242'],
            ['CK','Cook Islands','682'],['CR','Costa Rica','506'],['HR','Croatia','385'],
            ['CU','Cuba','53'],['CW','Curaçao','599'],['CY','Cyprus','357'],
            ['CZ','Czech Republic','420'],['DK','Denmark','45'],['DJ','Djibouti','253'],
            ['DM','Dominica','1767'],['DO','Dominican Republic','1809'],['EC','Ecuador','593'],
            ['EG','Egypt','20'],['SV','El Salvador','503'],['GQ','Equatorial Guinea','240'],
            ['ER','Eritrea','291'],['EE','Estonia','372'],['ET','Ethiopia','251'],
            ['FJ','Fiji','679'],['FI','Finland','358'],['FR','France','33'],
            ['GA','Gabon','241'],['GM','Gambia','220'],['GE','Georgia','995'],
            ['DE','Germany','49'],['GH','Ghana','233'],['GI','Gibraltar','350'],
            ['GR','Greece','30'],['GL','Greenland','299'],['GD','Grenada','1473'],
            ['GU','Guam','1671'],['GT','Guatemala','502'],['GN','Guinea','224'],
            ['GW','Guinea-Bissau','245'],['GY','Guyana','592'],['HT','Haiti','509'],
            ['HN','Honduras','504'],['HK','Hong Kong','852'],['HU','Hungary','36'],
            ['IS','Iceland','354'],['IN','India','91'],['ID','Indonesia','62'],
            ['IR','Iran','98'],['IQ','Iraq','964'],['IE','Ireland','353'],
            ['IL','Israel','972'],['IT','Italy','39'],['CI','Ivory Coast','225'],
            ['JM','Jamaica','1876'],['JP','Japan','81'],['JO','Jordan','962'],
            ['KZ','Kazakhstan','7'],['KE','Kenya','254'],['KI','Kiribati','686'],
            ['KW','Kuwait','965'],['KG','Kyrgyzstan','996'],['LA','Laos','856'],
            ['LV','Latvia','371'],['LB','Lebanon','961'],['LS','Lesotho','266'],
            ['LR','Liberia','231'],['LY','Libya','218'],['LI','Liechtenstein','423'],
            ['LT','Lithuania','370'],['LU','Luxembourg','352'],['MO','Macau','853'],
            ['MG','Madagascar','261'],['MW','Malawi','265'],['MY','Malaysia','60'],
            ['MV','Maldives','960'],['ML','Mali','223'],['MT','Malta','356'],
            ['MH','Marshall Islands','692'],['MR','Mauritania','222'],['MU','Mauritius','230'],
            ['MX','Mexico','52'],['FM','Micronesia','691'],['MD','Moldova','373'],
            ['MC','Monaco','377'],['MN','Mongolia','976'],['ME','Montenegro','382'],
            ['MA','Morocco','212'],['MZ','Mozambique','258'],['MM','Myanmar','95'],
            ['NA','Namibia','264'],['NP','Nepal','977'],['NL','Netherlands','31'],
            ['NZ','New Zealand','64'],['NI','Nicaragua','505'],['NE','Niger','227'],
            ['NG','Nigeria','234'],['KP','North Korea','850'],['MK','North Macedonia','389'],
            ['NO','Norway','47'],['OM','Oman','968'],['PK','Pakistan','92'],
            ['PW','Palau','680'],['PS','Palestine','970'],['PA','Panama','507'],
            ['PG','Papua New Guinea','675'],['PY','Paraguay','595'],['PE','Peru','51'],
            ['PH','Philippines','63'],['PL','Poland','48'],['PT','Portugal','351'],
            ['PR','Puerto Rico','1787'],['QA','Qatar','974'],['RO','Romania','40'],
            ['RU','Russia','7'],['RW','Rwanda','250'],['KN','Saint Kitts and Nevis','1869'],
            ['LC','Saint Lucia','1758'],['VC','Saint Vincent and the Grenadines','1784'],
            ['WS','Samoa','685'],['SM','San Marino','378'],['SA','Saudi Arabia','966'],
            ['SN','Senegal','221'],['RS','Serbia','381'],['SC','Seychelles','248'],
            ['SL','Sierra Leone','232'],['SG','Singapore','65'],['SK','Slovakia','421'],
            ['SI','Slovenia','386'],['SB','Solomon Islands','677'],['SO','Somalia','252'],
            ['ZA','South Africa','27'],['KR','South Korea','82'],['SS','South Sudan','211'],
            ['ES','Spain','34'],['LK','Sri Lanka','94'],['SD','Sudan','249'],
            ['SR','Suriname','597'],['SE','Sweden','46'],['CH','Switzerland','41'],
            ['SY','Syria','963'],['TW','Taiwan','886'],['TJ','Tajikistan','992'],
            ['TZ','Tanzania','255'],['TH','Thailand','66'],['TL','Timor-Leste','670'],
            ['TG','Togo','228'],['TO','Tonga','676'],['TT','Trinidad and Tobago','1868'],
            ['TN','Tunisia','216'],['TR','Turkey','90'],['TM','Turkmenistan','993'],
            ['TV','Tuvalu','688'],['UG','Uganda','256'],['UA','Ukraine','380'],
            ['AE','United Arab Emirates','971'],['GB','United Kingdom','44'],
            ['US','United States','1'],['UY','Uruguay','598'],['UZ','Uzbekistan','998'],
            ['VU','Vanuatu','678'],['VE','Venezuela','58'],['VN','Vietnam','84'],
            ['YE','Yemen','967'],['ZM','Zambia','260'],['ZW','Zimbabwe','263'],
        ];

        const dialCode = (initCode || '').replace(/^\+/, '');
        const initC = (dialCode ? COUNTRIES.find(c => c[2] === dialCode) : null)
                   || COUNTRIES.find(c => c[0] === 'LB');

        return {
            open: false,
            search: '',
            selected: { iso: initC[0], name: initC[1], dial: initC[2] },
            number: initPhone || '',
            countries: COUNTRIES.map(([iso, name, dial]) => ({ iso, name, dial })),
            get filtered() {
                if (!this.search) return this.countries;
                const q = this.search.toLowerCase();
                return this.countries.filter(c =>
                    c.name.toLowerCase().includes(q) || c.dial.includes(q)
                );
            },
            flag(iso) {
                return [...iso.toUpperCase()].map(c =>
                    String.fromCodePoint(c.charCodeAt(0) + 127397)
                ).join('');
            },
            pick(c) {
                this.selected = c;
                this.open = false;
                this.search = '';
                this.$nextTick(() => this.$refs.numInput.focus());
            },
        };
    }
    </script>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ __('menu_owner.nav.restaurant') }}</h1>
        </div>
        <p class="qf-desc">{{ $restaurant ? __('menu_owner.menus.edit_desc') : __('menu_owner.menus.create_desc') }}</p>

        <div class="qf-wrap">
            <form method="POST" action="{{ route('menu-owner.restaurant.store-or-update') }}"
                  x-data="{
                      errors: {},
                      hasExistingLogo: @js($restaurant?->hasMedia('logo') ?? false),

                      submit(e) {
                          this.errors = {};
                          let ok = true;

                          const phone = this.$el.querySelector('input[name=phone]');
                          if (!phone || !phone.value.trim()) {
                              this.errors.phone = '{{ __('menu_owner.restaurant.validation.phone_required') }}';
                              ok = false;
                          }

                          const currency = this.$el.querySelector('input[name=currency]');
                          if (!currency || !currency.value) {
                              this.errors.currency = '{{ __('menu_owner.restaurant.validation.currency_required') }}';
                              ok = false;
                          }

                          const lang = this.$el.querySelector('input[name=default_locale]');
                          if (!lang || !lang.value) {
                              this.errors.language = '{{ __('menu_owner.restaurant.validation.language_required') }}';
                              ok = false;
                          }

                          if (!this.hasExistingLogo) {
                              const logoKey = this.$el.querySelector('input[name=logo_key]');
                              if (!logoKey || !logoKey.value) {
                                  this.errors.logo = '{{ __('menu_owner.restaurant.validation.logo_required') }}';
                                  ok = false;
                              }
                          }

                          if (!ok) {
                              e.preventDefault();
                              this.$nextTick(() => {
                                  const first = this.$el.querySelector('.ui-fe-error:not([style*=\'display: none\'])');
                                  first?.closest('.qf-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                              });
                          }
                      },
                  }"
                  @submit="submit($event)">
                @csrf

                <div class="qf-section">
                    <div class="qf-section-title">{{ __('menu_owner.menus.section_info') }}</div>
                    <div class="space-y-5">
                        <x-ui.field name="name"
                                    label="{{ __('menu_owner.restaurant.name') }}"
                                    help="{{ __('menu_owner.menus.menu_name_desc') }}"
                                    required>
                            <x-ui.input name="name" type="text"
                                        :value="old('name', $restaurant?->name)"
                                        placeholder="{{ __('menu_owner.menus.placeholder_name') }}"
                                        minlength="3" maxlength="255" required />
                        </x-ui.field>

                        <x-ui.field name="slug"
                                    label="{{ __('menu_owner.menus.url_slug') }}"
                                    help="{{ __('menu_owner.menus.url_slug_desc') }}">
                            <x-ui.input name="slug" type="text"
                                        :value="old('slug', $restaurant?->slug)"
                                        placeholder="{{ __('menu_owner.menus.placeholder_slug') }}"
                                        pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                        state="disabled" disabled readonly />
                        </x-ui.field>

                        <x-ui.field name="description"
                                    label="{{ __('menu_owner.menus.description') }}"
                                    help="{{ __('menu_owner.menus.description_desc') }}">
                            <x-ui.textarea name="description" rows="3"
                                           :value="old('description', $restaurant?->description)" />
                        </x-ui.field>

                        {{-- Combined phone picker --}}
                        <div class="pp-root" x-data="phonePicker(
                                 '{{ old('country_code', $restaurant?->country_code ?? '+961') }}',
                                 '{{ old('phone', $restaurant?->phone ?? '') }}'
                             )">
                            <label class="ui-label">{{ __('menu_owner.menus.phone') }} <span class="req">*</span></label>

                            <div class="pp-wrap" :class="{ 'pp-open': open }">
                                {{-- Country trigger --}}
                                <button type="button" class="pp-trigger"
                                        @click="open = !open"
                                        @click.outside="open = false; search = ''">
                                    <span class="pp-flag" x-text="flag(selected.iso)"></span>
                                    <span class="pp-dial" x-text="'+' + selected.dial"></span>
                                    <svg class="pp-chev" :class="{ 'rot': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </button>

                                <div class="pp-sep"></div>

                                {{-- Number input --}}
                                <input type="tel" x-model="number" x-ref="numInput"
                                       class="pp-number"
                                       placeholder="{{ __('menu_owner.menus.phone_placeholder') }}"
                                       dir="ltr" autocomplete="tel-national" />

                                {{-- Hidden fields for form submission --}}
                                <input type="hidden" name="country_code" :value="'+' + selected.dial">
                                <input type="hidden" name="phone" :value="number">
                            </div>

                            {{-- Dropdown --}}
                            <div x-show="open" x-cloak
                                 x-transition:enter="pp-drop-enter"
                                 x-transition:enter-start="pp-drop-from"
                                 x-transition:enter-end="pp-drop-to"
                                 x-transition:leave="pp-drop-enter"
                                 x-transition:leave-start="pp-drop-to"
                                 x-transition:leave-end="pp-drop-from"
                                 class="pp-drop">
                                <div class="pp-search-wrap">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    <input type="text" x-model="search" x-ref="searchInput"
                                           class="pp-search"
                                           placeholder="{{ __('menu_owner.menus.phone_search') }}"
                                           @keydown.escape="open = false; search = ''"
                                           x-init="$watch('open', v => v && $nextTick(() => $refs.searchInput?.focus()))">
                                </div>
                                <div class="pp-list">
                                    <template x-if="filtered.length === 0">
                                        <div class="pp-empty">{{ __('menu_owner.menus.phone_no_results') }}</div>
                                    </template>
                                    <template x-for="c in filtered" :key="c.iso">
                                        <button type="button" class="pp-opt"
                                                :class="{ 'pp-opt-active': selected.iso === c.iso }"
                                                @click="pick(c)">
                                            <span class="pp-opt-flag" x-text="flag(c.iso)"></span>
                                            <span class="pp-opt-name" x-text="c.name"></span>
                                            <span class="pp-opt-code" x-text="'+' + c.dial"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <p class="ui-fe-error" x-show="errors.phone" x-text="errors.phone"></p>
                        @error('phone')<p class="ui-help error" style="margin-top:4px">{{ $message }}</p>@enderror

                        {{-- Currency --}}
                        @php
                            $currencyOptions = collect(config('currencies'))
                                ->map(fn($c, $code) => [
                                    'value' => $code,
                                    'label' => $code,
                                    'flag'  => $c['symbol'],
                                    'meta'  => $c['name'],
                                ])->values()->all();
                        @endphp
                        <div>
                            <x-ui.field name="currency"
                                        label="{{ __('menu_owner.restaurant.currency') }}"
                                        help="{{ __('menu_owner.restaurant.currency_desc') }}"
                                        required>
                                <x-ui.combo
                                    name="currency"
                                    :options="$currencyOptions"
                                    :value="old('currency', $restaurant?->currency ?? 'USD')"
                                    placeholder="{{ __('menu_owner.restaurant.currency_placeholder') }}" />
                            </x-ui.field>
                            <p class="ui-fe-error" x-show="errors.currency" x-text="errors.currency"></p>
                            @error('currency')<p class="ui-help error" style="margin-top:4px">{{ $message }}</p>@enderror
                        </div>

                        {{-- Location picker (address + Google Maps URL combined) --}}
                        <div>
                            <label class="ui-label" style="margin-bottom:6px">
                                {{ __('menu_owner.restaurant.location') }}
                            </label>
                            <p class="qf-help">{{ __('menu_owner.restaurant.location_desc') }}</p>

                            <div class="loc-root"
                                 x-data="locationPicker(
                                     @js(old('address', $restaurant?->address ?? '')),
                                     @js(old('google_maps_url', $restaurant?->google_maps_url ?? ''))
                                 )"
                                 @click.outside="showSuggestions = false">

                                {{-- Search bar --}}
                                <div class="loc-bar">
                                    <svg class="loc-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    <input type="text"
                                           class="loc-input"
                                           x-model="query"
                                           @input.debounce.450ms="search()"
                                           @keydown.escape="showSuggestions = false"
                                           @keydown.enter.prevent="results.length && pick(results[0])"
                                           placeholder="{{ __('menu_owner.restaurant.location_placeholder') }}"
                                           autocomplete="off" />
                                    <svg x-show="loading" class="loc-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                    <button x-show="query && !loading" type="button" class="loc-clear-btn"
                                            @click="clear()" x-cloak>
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                {{-- Suggestions --}}
                                <div class="loc-suggestions" x-show="showSuggestions && results.length" x-cloak>
                                    <template x-for="r in results" :key="r.place_id">
                                        <button type="button" class="loc-suggestion" @click="pick(r)">
                                            <svg class="loc-pin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/>
                                            </svg>
                                            <span x-text="r.display_name"></span>
                                        </button>
                                    </template>
                                </div>

                                {{-- Map preview --}}
                                <div class="loc-map-wrap" x-show="picked" x-cloak>
                                    <iframe class="loc-map"
                                            :src="mapSrc"
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                                    <a :href="mapsUrl" target="_blank" rel="noopener" class="loc-open-maps" x-show="mapsUrl">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        {{ __('menu_owner.restaurant.open_in_maps') }}
                                    </a>
                                </div>

                                {{-- Hidden form fields --}}
                                <input type="hidden" name="address" :value="address">
                                <input type="hidden" name="google_maps_url" :value="mapsUrl">
                            </div>
                        </div>

                        <x-ui.checkbox name="is_active" value="1"
                                       :checked="old('is_active', $restaurant?->is_active ?? true)"
                                       label="{{ __('menu_owner.menus.is_active') }}"
                                       description="{{ __('menu_owner.menus.is_active_desc') }}" />
                    </div>
                </div>

                {{-- Branding section --}}
                <div class="qf-section">
                    <div class="qf-section-title">{{ __('menu_owner.restaurant.section_branding') }}</div>
                    <div class="space-y-6">

                        {{-- Logo --}}
                        <div>
                            <x-ui.field name="logo"
                                        label="{{ __('menu_owner.restaurant.logo') }}"
                                        help="{{ __('menu_owner.restaurant.logo_desc') }}"
                                        required>
                                <x-ui.dropzone
                                    name="logo"
                                    context="logo"
                                    hint="{{ __('menu_owner.restaurant.logo_hint') }}"
                                    :value="$restaurant?->getFirstMediaUrl('logo')"
                                    :file-name="$restaurant?->getFirstMedia('logo')?->file_name"
                                    delete-name="delete_logo" />
                            </x-ui.field>
                            <p class="ui-fe-error" x-show="errors.logo" x-text="errors.logo"></p>
                            @error('logo_key')
                                <p class="ui-help error" style="margin-top:6px">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cover Image --}}
                        <x-ui.field name="cover_image"
                                    label="{{ __('menu_owner.restaurant.cover_image') }}"
                                    help="{{ __('menu_owner.restaurant.cover_image_desc') }}">
                            <x-ui.dropzone
                                name="cover_image"
                                context="cover_image"
                                hint="{{ __('menu_owner.restaurant.cover_hint') }}"
                                :value="$restaurant?->getFirstMediaUrl('cover_image')"
                                :file-name="$restaurant?->getFirstMedia('cover_image')?->file_name"
                                delete-name="delete_cover_image" />
                        </x-ui.field>

                        {{-- Preferred Language --}}
                        @php
                            $langOptions = collect(config('locales.locales'))
                                ->only(['ar', 'en'])
                                ->map(fn ($data, $code) => [
                                    'value' => $code,
                                    'label' => $data['name'],
                                    'flag'  => $data['flag'],
                                ])->values()->all();
                        @endphp
                        <div>
                            <x-ui.field name="default_locale"
                                        label="{{ __('menu_owner.restaurant.preferred_language') }}"
                                        help="{{ __('menu_owner.restaurant.preferred_language_desc') }}"
                                        required>
                                <x-ui.combo
                                    name="default_locale"
                                    :options="$langOptions"
                                    :value="old('default_locale', $restaurant?->default_locale ?? 'ar')"
                                    :searchable="false" />
                            </x-ui.field>
                            <p class="ui-fe-error" x-show="errors.language" x-text="errors.language"></p>
                        </div>

                    </div>
                </div>

                <div class="qf-actions">
                    <button type="submit" class="ui-btn ui-btn-primary">
                        {{ $restaurant ? __('menu_owner.menus.update_menu') : __('menu_owner.menus.create_menu_button') }}
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
    function locationPicker(initAddress, initMapsUrl) {
        // Try to extract lat/lon from an existing Google Maps URL (format: ?q=lat,lon)
        let initLat = null, initLon = null;
        if (initMapsUrl) {
            const m = initMapsUrl.match(/[?&]q=([-\d.]+),([-\d.]+)/);
            if (m) { initLat = parseFloat(m[1]); initLon = parseFloat(m[2]); }
        }

        return {
            query: initAddress || '',
            results: [],
            loading: false,
            showSuggestions: false,
            picked: !!(initMapsUrl || initAddress),
            address: initAddress || '',
            mapsUrl: initMapsUrl || '',
            lat: initLat,
            lon: initLon,

            get mapSrc() {
                if (!this.lat || !this.lon) return '';
                const d = 0.008;
                return `https://www.openstreetmap.org/export/embed.html?bbox=${this.lon-d},${this.lat-d},${this.lon+d},${this.lat+d}&layer=mapnik&marker=${this.lat},${this.lon}`;
            },

            async search() {
                const q = this.query.trim();
                if (q.length < 3) { this.results = []; this.showSuggestions = false; return; }
                this.loading = true;
                try {
                    const res = await fetch(
                        `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=6&addressdetails=0`,
                        { headers: { 'Accept-Language': document.documentElement.lang || 'en' } }
                    );
                    this.results = await res.json();
                    this.showSuggestions = this.results.length > 0;
                } catch { this.results = []; }
                finally { this.loading = false; }
            },

            pick(r) {
                this.lat = parseFloat(r.lat);
                this.lon = parseFloat(r.lon);
                this.address = r.display_name;
                this.mapsUrl = `https://www.google.com/maps?q=${r.lat},${r.lon}`;
                this.query = r.display_name;
                this.showSuggestions = false;
                this.picked = true;
            },

            clear() {
                this.query = '';
                this.results = [];
                this.showSuggestions = false;
                this.picked = false;
                this.address = '';
                this.mapsUrl = '';
                this.lat = null;
                this.lon = null;
            },
        };
    }
    </script>

    <script>
        document.getElementById('name')?.addEventListener('input', function () {
            const slugInput = document.getElementById('slug');
            if (slugInput) {
                slugInput.value = this.value.toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    </script>
</x-sidebar-layout>
