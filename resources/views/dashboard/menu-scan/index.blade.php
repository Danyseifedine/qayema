<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.menu_scan.breadcrumb') }}</span>
    </x-slot>

    <div class="sbl-content">

        {{-- ── AI Hero ─────────────────────────────────────── --}}
        <div class="ms-hero">
            <div class="ms-hero-glow"></div>
            <div class="ms-hero-content">
                <div class="ms-hero-left">
                    <div class="ms-hero-icon-wrap">
                        <svg class="ms-brain-icon" viewBox="0 0 52 52" fill="none">
                            <circle cx="26" cy="26" r="25" stroke="rgba(216,170,99,0.3)" stroke-width="1"/>
                            <circle cx="26" cy="26" r="18" stroke="rgba(216,170,99,0.2)" stroke-width="1"/>
                            <path d="M18 20c0-4.4 3.6-8 8-8s8 3.6 8 8c2.2 0 4 1.8 4 4s-1.8 4-4 4H18c-2.2 0-4-1.8-4-4s1.8-4 4-4z" fill="rgba(216,170,99,0.15)" stroke="#D8AA63" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M22 20v-2M26 18v-2M30 20v-2" stroke="#D8AA63" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M20 28v4M24 28v6M28 28v6M32 28v4" stroke="#D8AA63" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="26" cy="26" r="2" fill="#D8AA63"/>
                            <path d="M14 26h4M34 26h4" stroke="#D8AA63" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <div class="ms-hero-ring ms-hero-ring-1"></div>
                        <div class="ms-hero-ring ms-hero-ring-2"></div>
                    </div>
                    <div class="ms-hero-text">
                        <div class="ms-hero-badge">AI-Powered</div>
                        <h1 class="ms-hero-title">{{ __('menu_owner.menu_scan.title') }}</h1>
                        <p class="ms-hero-sub">{{ __('menu_owner.menu_scan.subtitle') }}</p>
                    </div>
                </div>

                @if ($restaurant)
                <div class="ms-scan-counter"
                     x-data="{ used: $store.scanCounter.used, limit: {{ $scanLimit }}, tpl: @js(__('menu_owner.menu_scan.scans_used', ['used' => '__N__', 'limit' => $scanLimit])) }"
                     x-init="$watch('$store.scanCounter.used', v => used = v)">
                    <div class="ms-counter-dots">
                        <template x-for="i in limit" :key="i">
                            <div class="ms-dot" :class="i <= used ? 'ms-dot--used' : 'ms-dot--free'"></div>
                        </template>
                    </div>
                    <p class="ms-counter-label" x-text="tpl.replace('__N__', used)"></p>
                    <p class="ms-counter-reset">{{ __('menu_owner.menu_scan.scans_reset') }}</p>
                </div>
                @endif
            </div>
        </div>

        @if (!$restaurant)
            <div class="ms-notice ms-notice--warn">
                <svg viewBox="0 0 20 20" fill="currentColor" class="ms-notice-icon"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                <span>
                    {{ __('menu_owner.menu_scan.no_restaurant') }}
                    <a href="{{ route('menu-owner.restaurant.index') }}" class="ms-notice-link">{{ __('menu_owner.menu_scan.setup_restaurant') }}</a>
                </span>
            </div>
        @else
            <div x-data="menuScanner()" x-init="init()">

                {{-- ── Limit reached (daily) ──────────────────────────── --}}
                <template x-if="state === 'limit-reached'">
                    <div class="ms-blocker">
                        <div class="ms-blocker-icon ms-blocker-icon--moon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </div>
                        <h2 class="ms-blocker-title">{{ __('menu_owner.menu_scan.scan_limit_reached') }}</h2>
                        <p class="ms-blocker-desc">{{ __('menu_owner.menu_scan.scan_limit_desc', ['limit' => $scanLimit]) }}</p>
                        <div class="ms-blocker-badge">{{ __('menu_owner.menu_scan.come_back_tomorrow') }}</div>
                    </div>
                </template>

                {{-- ── Rate limit (API exhausted) ─────────────────────── --}}
                <template x-if="state === 'rate-limited'">
                    <div class="ms-blocker">
                        <div class="ms-blocker-icon ms-blocker-icon--sleep">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                                <path d="M12 8h4l-4 4h4M8 14h3l-3 3h3" stroke-width="1.3"/>
                            </svg>
                        </div>
                        <h2 class="ms-blocker-title">{{ __('menu_owner.menu_scan.rate_limit_title') }}</h2>
                        <p class="ms-blocker-desc">{{ __('menu_owner.menu_scan.rate_limit_desc') }}</p>
                        <div class="ms-blocker-badge">{{ __('menu_owner.menu_scan.come_back_tomorrow') }}</div>
                    </div>
                </template>

                {{-- ── Idle: upload zone ───────────────────────────────── --}}
                <template x-if="state === 'idle'">
                    <div
                        class="ms-dropzone"
                        :class="{ 'ms-dropzone--drag': dragging, 'ms-dropzone--has-file': !!preview }"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dragging = false; pickFile($event.dataTransfer.files[0])"
                    >
                        {{-- Empty state --}}
                        <template x-if="!preview">
                            <div class="ms-dropzone-empty" @click="$refs.fileInput.click()">
                                <div class="ms-dz-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21 15 16 10 5 21"/>
                                    </svg>
                                </div>
                                <p class="ms-dz-title">{{ __('menu_owner.menu_scan.drop_title') }}</p>
                                <p class="ms-dz-meta">{{ __('menu_owner.menu_scan.drop_meta') }}</p>
                                <div class="ms-dz-ai-badge" style="margin-bottom:20px">
                                    <svg viewBox="0 0 16 16" fill="currentColor" style="width:12px;height:12px">
                                        <path d="M8 1a.5.5 0 0 1 .5.5v1.55a5.5 5.5 0 0 1 4.45 4.45h1.55a.5.5 0 0 1 0 1h-1.55A5.5 5.5 0 0 1 8.5 12.95V14.5a.5.5 0 0 1-1 0v-1.55A5.5 5.5 0 0 1 2.55 8.5H1a.5.5 0 0 1 0-1h1.55A5.5 5.5 0 0 1 7.5 3.05V1.5A.5.5 0 0 1 8 1zm0 3.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zM8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                    </svg>
                                    AI-ready
                                </div>
                                <button type="button"
                                        class="ms-btn ms-btn--primary ms-btn--disabled"
                                        disabled
                                        @click.stop>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                    </svg>
                                    {{ __('menu_owner.menu_scan.scan_btn') }}
                                </button>
                                <p class="ms-dz-browse-hint">{{ __('menu_owner.common.upload_click') }}</p>
                            </div>
                        </template>

                        {{-- File selected state --}}
                        <template x-if="preview">
                            <div class="ms-dz-selected">
                                <div class="ms-dz-selected-img" @click="$refs.fileInput.click()">
                                    <img :src="preview" class="ms-preview-img">
                                    <div class="ms-preview-overlay">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:white">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        <span style="font-size:12px;color:white;margin-top:6px">Click to change</span>
                                    </div>
                                </div>
                                <div class="ms-dz-selected-info">
                                    <div class="ms-dz-file-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                    </div>
                                    <p class="ms-dz-filename" x-text="fileName"></p>
                                    <p class="ms-dz-ready">Ready to scan</p>
                                    <div class="ms-dz-actions">
                                        <button type="button"
                                                class="ms-btn ms-btn--primary"
                                                @click.stop="startScan()">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                            </svg>
                                            {{ __('menu_owner.menu_scan.scan_btn') }}
                                        </button>
                                        <button type="button" class="ms-btn ms-btn--ghost" @click.stop="reset()">
                                            {{ __('menu_owner.menu_scan.clear_btn') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <input x-ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" style="display:none"
                               @change="pickFile($event.target.files[0])">
                    </div>
                </template>

                {{-- ── Scanning (polling) ──────────────────────────────── --}}
                <template x-if="state === 'scanning'">
                    <div class="ms-scanning">
                        <div class="ms-scan-anim">
                            <div class="ms-scan-ring ms-scan-ring-1"></div>
                            <div class="ms-scan-ring ms-scan-ring-2"></div>
                            <div class="ms-scan-ring ms-scan-ring-3"></div>
                            <div class="ms-scan-core">
                                <svg viewBox="0 0 52 52" fill="none">
                                    <path d="M18 20c0-4.4 3.6-8 8-8s8 3.6 8 8c2.2 0 4 1.8 4 4s-1.8 4-4 4H18c-2.2 0-4-1.8-4-4s1.8-4 4-4z" fill="rgba(216,170,99,0.15)" stroke="#D8AA63" stroke-width="1.5"/>
                                    <path d="M22 20v-2M26 18v-2M30 20v-2" stroke="#D8AA63" stroke-width="1.5" stroke-linecap="round"/>
                                    <path d="M20 28v4M24 28v6M28 28v6M32 28v4" stroke="#D8AA63" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="26" cy="26" r="2" fill="#D8AA63"/>
                                    <path d="M14 26h4M34 26h4" stroke="#D8AA63" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <p class="ms-scan-status" x-text="scanStatusText"></p>
                        <p class="ms-scan-hint">{{ __('menu_owner.menu_scan.seconds_hint') }}</p>
                        <div class="ms-scan-progress">
                            <div class="ms-scan-bar"></div>
                        </div>
                        <button type="button" class="ms-cancel-btn" @click="cancel()">
                            {{ __('menu_owner.menu_scan.cancel') }}
                        </button>
                    </div>
                </template>

                {{-- ── Preview results ─────────────────────────────────── --}}
                <template x-if="state === 'preview'">
                    <div>
                        <div class="ms-preview-bar">
                            <div class="ms-preview-summary">
                                <div class="ms-preview-summary-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="ms-preview-summary-title">
                                        {!! str_replace([':categories', ':dishes'], ['<span x-text="result.categories.length"></span>', '<span x-text="totalDishes"></span>'], e(__('menu_owner.menu_scan.found_items'))) !!}
                                    </p>
                                    <p class="ms-preview-summary-sub">{{ __('menu_owner.menu_scan.review_hint') }}</p>
                                </div>
                            </div>
                            <div class="ms-preview-actions">
                                <button type="button" class="ms-btn ms-btn--ghost" @click="reset()">
                                    {{ __('menu_owner.menu_scan.start_over') }}
                                </button>
                                <button type="button" class="ms-btn ms-btn--primary" @click="importMenu()" :disabled="importing">
                                    <template x-if="!importing">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </template>
                                    <template x-if="importing">
                                        <svg class="ms-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                        </svg>
                                    </template>
                                    <span x-text="importing ? '{{ __('menu_owner.menu_scan.importing') }}' : '{{ __('menu_owner.menu_scan.import_btn') }}'"></span>
                                </button>
                            </div>
                        </div>

                        <div class="ms-results-grid">
                            <template x-for="(category, ci) in result.categories" :key="ci">
                                <div class="ms-category-card">

                                    {{-- Category header with editable name --}}
                                    <div class="ms-category-header" x-data="{ editing: false }">
                                        <div class="ms-category-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                                <line x1="7" y1="7" x2="7.01" y2="7"/>
                                            </svg>
                                        </div>
                                        <div class="ms-editable-wrap" :class="{ 'ms-editable--active': editing }">
                                            <span class="ms-category-name" x-show="!editing" x-text="category.name"></span>
                                            <input class="ms-edit-input ms-edit-input--category"
                                                   x-show="editing"
                                                   x-model="category.name"
                                                   @blur="editing = false"
                                                   @keydown.enter.prevent="editing = false"
                                                   @keydown.escape.prevent="editing = false"
                                                   x-ref="catInput{{ '' }}">
                                            <button class="ms-edit-btn" type="button"
                                                    x-show="!editing"
                                                    @click="editing = true; $nextTick(() => $el.closest('.ms-editable-wrap').querySelector('input').focus())"
                                                    title="Edit">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <span class="ms-category-count" x-text="category.dishes.length + ' {{ __('menu_owner.menu_scan.dishes_count', ['count' => '']) }}'.trim()"></span>
                                    </div>

                                    {{-- Dishes --}}
                                    <div class="ms-dishes-grid">
                                        <template x-for="(dish, di) in category.dishes" :key="di">
                                            <div class="ms-dish-card" x-data="{ editName: false, editPrice: false, editIngr: false }">

                                                {{-- Name row --}}
                                                <div class="ms-dish-row">
                                                    <div class="ms-editable-wrap" :class="{ 'ms-editable--active': editName }">
                                                        <span class="ms-dish-name" x-show="!editName" x-text="dish.name"></span>
                                                        <input class="ms-edit-input ms-edit-input--name"
                                                               x-show="editName"
                                                               x-model="dish.name"
                                                               @blur="editName = false"
                                                               @keydown.enter.prevent="editName = false"
                                                               @keydown.escape.prevent="editName = false">
                                                        <button class="ms-edit-btn" type="button"
                                                                x-show="!editName"
                                                                @click="editName = true; $nextTick(() => $el.closest('.ms-editable-wrap').querySelector('input').focus())"
                                                                title="Edit">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    {{-- Price --}}
                                                    <div class="ms-editable-wrap ms-editable-wrap--price" :class="{ 'ms-editable--active': editPrice }">
                                                        <span class="ms-dish-price" x-show="!editPrice" x-text="dish.price ? '{{ $restaurant?->currency ?? 'USD' }} ' + dish.price : '—'"></span>
                                                        <input class="ms-edit-input ms-edit-input--price"
                                                               x-show="editPrice"
                                                               x-model="dish.price"
                                                               type="number"
                                                               step="0.01"
                                                               min="0"
                                                               placeholder="0.00"
                                                               @blur="editPrice = false"
                                                               @keydown.enter.prevent="editPrice = false"
                                                               @keydown.escape.prevent="editPrice = false">
                                                        <button class="ms-edit-btn" type="button"
                                                                x-show="!editPrice"
                                                                @click="editPrice = true; $nextTick(() => $el.closest('.ms-editable-wrap').querySelector('input').focus())"
                                                                title="Edit price">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Ingredients --}}
                                                <div class="ms-editable-wrap ms-editable-wrap--ingr" :class="{ 'ms-editable--active': editIngr }">
                                                    <p class="ms-dish-ingredients" x-show="!editIngr"
                                                       x-text="dish.ingredients || '—'"
                                                       :class="{ 'ms-dish-ingredients--empty': !dish.ingredients }"></p>
                                                    <input class="ms-edit-input ms-edit-input--ingr"
                                                           x-show="editIngr"
                                                           x-model="dish.ingredients"
                                                           placeholder="ingredients…"
                                                           @blur="editIngr = false"
                                                           @keydown.enter.prevent="editIngr = false"
                                                           @keydown.escape.prevent="editIngr = false">
                                                    <button class="ms-edit-btn ms-edit-btn--sm" type="button"
                                                            x-show="!editIngr"
                                                            @click="editIngr = true; $nextTick(() => $el.closest('.ms-editable-wrap').querySelector('input').focus())"
                                                            title="Edit ingredients">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                        </svg>
                                                    </button>
                                                </div>

                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- ── Done ──────────────────────────────────────────── --}}
                <template x-if="state === 'done'">
                    <div class="ms-done">
                        <div class="ms-done-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <h2 class="ms-done-title" x-text="doneMessage"></h2>
                        <p class="ms-done-sub">{{ __('menu_owner.menu_scan.imported_success') }}</p>
                        <div class="ms-done-actions">
                            <a href="{{ route('menu-owner.categories.index') }}" class="ms-btn ms-btn--primary">
                                {{ __('menu_owner.menu_scan.view_categories') }}
                            </a>
                            <a href="{{ route('menu-owner.dishes.index') }}" class="ms-btn ms-btn--ghost">
                                {{ __('menu_owner.menu_scan.view_dishes') }}
                            </a>
                            <button type="button" class="ms-btn ms-btn--ghost" @click="reset()">
                                {{ __('menu_owner.menu_scan.scan_again') }}
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ── Error ─────────────────────────────────────────── --}}
                <template x-if="state === 'error'">
                    <div class="ms-error">
                        <div class="ms-error-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <p class="ms-error-msg" x-text="errorMsg"></p>
                        <button type="button" class="ms-btn ms-btn--ghost" @click="reset()" style="margin-top:16px">
                            {{ __('menu_owner.menu_scan.try_again') }}
                        </button>
                    </div>
                </template>

            </div>
        @endif
    </div>

    @push('scripts')
    <style>
        /* ── AI Menu Scanner styles ─────────────────────── */

        /* Hero */
        .ms-hero {
            position: relative;
            background: linear-gradient(135deg, #1a1206 0%, #2c1f08 40%, #1a1510 100%);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .ms-hero-glow {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 80% 50%, rgba(216,170,99,0.18) 0%, transparent 60%);
            pointer-events: none;
        }
        .ms-hero-content {
            position: relative;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .ms-hero-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            min-width: 0;
        }
        .ms-hero-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }
        .ms-hero-icon-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .ms-brain-icon {
            width: 56px;
            height: 56px;
            animation: ms-float 4s ease-in-out infinite;
        }
        .ms-hero-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 1px solid rgba(216,170,99,0.2);
            animation: ms-ring-expand 3s ease-out infinite;
        }
        .ms-hero-ring-1 { animation-delay: 0s; }
        .ms-hero-ring-2 { animation-delay: 1.5s; }
        .ms-hero-badge {
            display: inline-flex;
            align-self: flex-start;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #D8AA63;
            background: rgba(216,170,99,0.15);
            border: 1px solid rgba(216,170,99,0.3);
            border-radius: 20px;
            padding: 2px 10px;
        }
        .ms-hero-title {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }
        .ms-hero-sub {
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            margin: 0;
            max-width: 480px;
            line-height: 1.6;
        }

        /* Scan counter */
        .ms-scan-counter {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(216,170,99,0.2);
            border-radius: 12px;
            padding: 12px 20px;
            white-space: nowrap;
        }
        .ms-counter-dots {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .ms-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            transition: all .2s;
        }
        .ms-dot--used {
            background: #D8AA63;
            box-shadow: 0 0 6px rgba(216,170,99,0.6);
        }
        .ms-dot--free {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .ms-counter-label {
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            margin: 0;
        }
        .ms-counter-reset {
            font-size: 10px;
            color: rgba(255,255,255,0.35);
            margin: 0;
        }

        /* Notice */
        .ms-notice {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
        }
        .ms-notice--warn {
            background: #fefce8;
            border: 1px solid #fde047;
            color: #854d0e;
        }
        .ms-notice-icon { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
        .ms-notice-link { text-decoration: underline; margin-inline-start: 6px; font-weight: 600; }

        /* Blocker (limit-reached / rate-limited) */
        .ms-blocker {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 56px 32px;
            background: var(--surface, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 16px;
            max-width: 480px;
            margin: 0 auto;
        }
        .ms-blocker-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .ms-blocker-icon svg { width: 32px; height: 32px; }
        .ms-blocker-icon--moon {
            background: linear-gradient(135deg, #1a1206, #2c1f08);
            color: #D8AA63;
            box-shadow: 0 0 24px rgba(216,170,99,0.35);
        }
        .ms-blocker-icon--sleep {
            background: linear-gradient(135deg, #1a1206, #2c1f08);
            color: #D8AA63;
            box-shadow: 0 0 24px rgba(216,170,99,0.35);
        }
        .ms-blocker-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--ink, #111);
            margin: 0 0 8px;
        }
        .ms-blocker-desc {
            font-size: 13px;
            color: var(--muted, #6b7280);
            margin: 0 0 20px;
            line-height: 1.6;
            max-width: 360px;
        }
        .ms-blocker-badge {
            font-size: 12px;
            font-weight: 600;
            color: #B8893F;
            background: rgba(216,170,99,0.1);
            border: 1px solid rgba(216,170,99,0.3);
            border-radius: 20px;
            padding: 6px 16px;
        }

        /* Upload zone */
        .ms-dropzone {
            border: 2px dashed var(--border, #e5e7eb);
            border-radius: 16px;
            background: var(--surface, #fafafa);
            transition: border-color .2s, background .2s, box-shadow .2s;
            overflow: hidden;
            width: 100%;
        }
        .ms-dropzone:not(.ms-dropzone--has-file):hover,
        .ms-dropzone--drag {
            border-color: #D8AA63;
            background: rgba(216,170,99,0.04);
            box-shadow: 0 0 0 4px rgba(216,170,99,0.1);
        }
        .ms-dropzone--has-file { border-style: solid; border-color: #D8AA63; cursor: default; }

        /* Empty state */
        .ms-dropzone-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 52px 24px 40px;
            text-align: center;
            cursor: pointer;
            min-height: 280px;
            justify-content: center;
        }
        .ms-dz-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, #fdf3dc, #f5e0a8);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: #B8893F;
        }
        .ms-dz-icon svg { width: 26px; height: 26px; }
        .ms-dz-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--ink, #111);
            margin: 0 0 6px;
        }
        .ms-dz-meta {
            font-size: 12px;
            color: var(--muted, #9ca3af);
            margin: 0 0 14px;
        }
        .ms-dz-ai-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #B8893F;
            background: rgba(216,170,99,0.1);
            border: 1px solid rgba(216,170,99,0.3);
            border-radius: 20px;
            padding: 3px 10px;
        }
        .ms-dz-browse-hint {
            font-size: 11px;
            color: var(--muted, #9ca3af);
            margin: 10px 0 0;
        }

        /* File selected state */
        .ms-dz-selected {
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 24px 28px;
            gap: 24px;
            min-height: 160px;
        }
        .ms-dz-selected-img {
            flex-shrink: 0;
            width: 110px;
            height: 110px;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--border, #e5e7eb);
            background: #f3f4f6;
        }
        .ms-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: opacity .2s;
        }
        .ms-dz-selected-img:hover .ms-preview-img { opacity: .65; }
        .ms-preview-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s;
        }
        .ms-dz-selected-img:hover .ms-preview-overlay { opacity: 1; }
        .ms-dz-selected-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            gap: 6px;
        }
        .ms-dz-file-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fdf3dc, #f5e0a8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #B8893F;
            margin-bottom: 4px;
        }
        .ms-dz-file-icon svg { width: 20px; height: 20px; }
        .ms-dz-filename {
            font-size: 14px;
            font-weight: 600;
            color: var(--ink, #111);
            margin: 0;
            word-break: break-all;
        }
        .ms-dz-ready {
            font-size: 12px;
            color: var(--muted, #9ca3af);
            margin: 0 0 8px;
        }
        .ms-dz-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        /* Responsive — stack vertically on small screens */
        @media (max-width: 640px) {
            .ms-hero { padding: 24px 16px; }
            .ms-hero-content { flex-direction: column; align-items: center; text-align: center; }
            .ms-hero-left { flex-direction: column; align-items: center; }
            .ms-hero-text { align-items: center; }
            .ms-hero-badge { align-self: center; }
            .ms-brain-icon { width: 44px; height: 44px; }
            .ms-hero-title { font-size: 18px; }
            .ms-hero-sub { font-size: 12px; }
            .ms-dz-selected { flex-direction: column; align-items: center; text-align: center; padding: 20px; gap: 16px; }
            .ms-dz-selected-img { width: 90px; height: 90px; }
            .ms-dz-selected-info { align-items: center; }
            .ms-dz-actions { justify-content: center; }
        }

        /* Buttons */
        .ms-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
        }
        .ms-btn svg { width: 15px; height: 15px; flex-shrink: 0; }
        .ms-btn--primary {
            background: linear-gradient(135deg, #D8AA63, #B8893F);
            color: #fff;
            box-shadow: 0 2px 8px rgba(216,170,99,0.4);
        }
        .ms-btn--primary:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(216,170,99,0.5); }
        .ms-btn--primary:active { transform: translateY(0); }
        .ms-btn--primary.ms-btn--disabled,
        .ms-btn--primary:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }
        .ms-btn--ghost {
            background: var(--surface, #f9fafb);
            color: var(--ink, #374151);
            border: 1px solid var(--border, #e5e7eb);
        }
        .ms-btn--ghost:hover { background: var(--bg, #f3f4f6); }

        /* Scanning animation */
        .ms-scanning {
            max-width: 400px;
            margin: 0 auto;
            text-align: center;
            padding: 40px 24px;
        }
        .ms-scan-anim {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
        }
        .ms-scan-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 1.5px solid rgba(216,170,99,0.4);
            animation: ms-ring-expand 2.4s ease-out infinite;
        }
        .ms-scan-ring-1 { animation-delay: 0s; }
        .ms-scan-ring-2 { animation-delay: .8s; }
        .ms-scan-ring-3 { animation-delay: 1.6s; }
        .ms-scan-core {
            position: absolute;
            inset: 18px;
            background: linear-gradient(135deg, #1a1206, #2c1f08);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(216,170,99,0.4);
        }
        .ms-scan-core svg { width: 36px; height: 36px; }
        .ms-scan-status {
            font-size: 15px;
            font-weight: 600;
            color: var(--ink, #111);
            margin: 0 0 6px;
        }
        .ms-scan-hint {
            font-size: 12px;
            color: var(--muted, #9ca3af);
            margin: 0 0 20px;
        }
        .ms-scan-progress {
            width: 100%;
            height: 3px;
            background: var(--border, #e5e7eb);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .ms-scan-bar {
            height: 100%;
            background: linear-gradient(90deg, #B8893F, #D8AA63, #B8893F);
            background-size: 200% 100%;
            animation: ms-bar-slide 1.8s linear infinite;
            width: 60%;
        }
        .ms-cancel-btn {
            font-size: 12px;
            color: var(--muted, #9ca3af);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
        }

        /* Preview bar */
        .ms-preview-bar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: var(--surface, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 12px;
        }
        .ms-preview-summary { display: flex; align-items: flex-start; gap: 12px; }
        .ms-preview-summary-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #fdf3dc, #f5e0a8);
            color: #B8893F;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .ms-preview-summary-icon svg { width: 18px; height: 18px; }
        .ms-preview-summary-title {
            font-size: 14px; font-weight: 600; color: var(--ink, #111); margin: 0 0 4px;
        }
        .ms-preview-summary-sub { font-size: 12px; color: var(--muted); margin: 0; }
        .ms-preview-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }

        /* Results grid */
        .ms-results-grid { display: grid; gap: 14px; }
        .ms-category-card {
            background: var(--surface, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 12px;
            overflow: hidden;
        }
        .ms-category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: linear-gradient(to right, rgba(216,170,99,0.06), transparent);
            border-bottom: 1px solid var(--border, #e5e7eb);
        }
        .ms-category-icon {
            width: 28px; height: 28px;
            border-radius: 7px;
            background: rgba(216,170,99,0.12);
            color: #B8893F;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .ms-category-icon svg { width: 13px; height: 13px; }
        .ms-category-name { font-size: 14px; font-weight: 600; color: var(--ink, #111); }
        .ms-category-count { font-size: 11px; color: var(--muted); margin-inline-start: auto; }
        .ms-dishes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
            padding: 12px;
        }
        .ms-dish-card {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--border, #e5e7eb);
            background: var(--bg, #fafafa);
        }
        .ms-dish-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
        .ms-dish-name { font-size: 13px; font-weight: 600; color: var(--ink, #111); }
        .ms-dish-price { font-size: 12px; font-weight: 600; color: #B8893F; flex-shrink: 0; }
        .ms-dish-ingredients { font-size: 11px; color: var(--muted); margin: 4px 0 0; line-height: 1.4; }
        .ms-dish-ingredients--empty { color: var(--border, #d1d5db); font-style: italic; }

        /* Inline editing */
        .ms-editable-wrap {
            display: flex;
            align-items: center;
            gap: 4px;
            min-width: 0;
            flex: 1;
        }
        .ms-editable-wrap--price {
            flex: 0 0 auto;
            justify-content: flex-end;
        }
        .ms-editable-wrap--ingr {
            margin-top: 4px;
            flex: unset;
            width: 100%;
        }
        .ms-edit-btn {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border: none;
            background: none;
            color: var(--muted, #9ca3af);
            cursor: pointer;
            border-radius: 5px;
            opacity: 0;
            transition: opacity .15s, background .15s, color .15s;
            padding: 0;
        }
        .ms-edit-btn svg { width: 12px; height: 12px; }
        .ms-edit-btn--sm svg { width: 11px; height: 11px; }
        .ms-editable-wrap:hover .ms-edit-btn,
        .ms-editable--active .ms-edit-btn { opacity: 1; }
        .ms-edit-btn:hover { background: rgba(216,170,99,0.12); color: #B8893F; }

        .ms-edit-input {
            border: none;
            outline: none;
            background: rgba(216,170,99,0.06);
            border-bottom: 1.5px solid #D8AA63;
            border-radius: 4px 4px 0 0;
            color: var(--ink, #111);
            padding: 2px 6px;
            min-width: 0;
            width: 100%;
            font-family: inherit;
        }
        .ms-edit-input--category { font-size: 14px; font-weight: 600; }
        .ms-edit-input--name { font-size: 13px; font-weight: 600; }
        .ms-edit-input--price { font-size: 12px; font-weight: 600; color: #B8893F; width: 72px; text-align: right; }
        .ms-edit-input--ingr { font-size: 11px; color: var(--muted); }

        /* Done state */
        .ms-done {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 52px 32px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            max-width: 480px;
            margin: 0 auto;
        }
        .ms-done-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #16a34a;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .ms-done-icon svg { width: 28px; height: 28px; }
        .ms-done-title { font-size: 16px; font-weight: 700; color: var(--ink); margin: 0 0 6px; }
        .ms-done-sub { font-size: 13px; color: var(--muted); margin: 0 0 24px; }
        .ms-done-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }

        /* Error state */
        .ms-error {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 40px 32px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            max-width: 480px;
            margin: 0 auto;
        }
        .ms-error-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
        }
        .ms-error-icon svg { width: 24px; height: 24px; }
        .ms-error-msg { font-size: 14px; font-weight: 500; color: var(--ink); margin: 0; }

        /* Spinner */
        .ms-spin { animation: ms-rotate 1s linear infinite; }

        /* Keyframes */
        @keyframes ms-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        @keyframes ms-ring-expand {
            0% { transform: scale(1); opacity: .6; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        @keyframes ms-bar-slide {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @keyframes ms-rotate {
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('scanCounter', {
                used: {{ $scansToday }},
                limit: {{ $scanLimit }},
                increment() {
                    if (this.used < this.limit) this.used++;
                },
            });
        });

        function menuScanner() {
            return {
                state: '{{ $scansToday >= $scanLimit ? 'limit-reached' : 'idle' }}',
                file: null,
                preview: null,
                fileName: null,
                dragging: false,
                scanId: null,
                result: null,
                errorMsg: null,
                importing: false,
                doneMessage: '',
                pollTimer: null,
                scanStatusText: '{{ __('menu_owner.menu_scan.uploading') }}',

                init() {},

                get totalDishes() {
                    return (this.result?.categories ?? []).reduce((sum, cat) => sum + (cat.dishes?.length ?? 0), 0);
                },

                pickFile(file) {
                    if (!file) return;
                    const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                    if (!allowed.includes(file.type)) {
                        this.showError('{{ __('menu_owner.menu_scan.error_invalid_type') }}');
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        this.showError('{{ __('menu_owner.menu_scan.error_too_large') }}');
                        return;
                    }
                    this.file = file;
                    this.fileName = file.name;
                    this.state = 'idle';
                    const reader = new FileReader();
                    reader.onload = e => { this.preview = e.target.result; };
                    reader.readAsDataURL(file);
                },

                async startScan() {
                    if (!this.file) return;
                    this.state = 'scanning';
                    this.scanStatusText = '{{ __('menu_owner.menu_scan.uploading') }}';

                    const fd = new FormData();
                    fd.append('image', this.file);
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

                    try {
                        const res = await fetch('{{ route('menu-owner.menu-scan.scan') }}', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: fd,
                        });
                        const data = await res.json();

                        if (res.status === 429 && data.message === 'daily_limit') {
                            this.state = 'limit-reached';
                            return;
                        }

                        if (!res.ok) throw new Error(data.message ?? 'Upload failed.');

                        this.scanId = data.scan_id;
                        this.scanStatusText = '{{ __('menu_owner.menu_scan.ai_reading') }}';
                        Alpine.store('scanCounter').increment();
                        this.startPolling();
                    } catch (e) {
                        this.showError(e.message);
                    }
                },

                startPolling() {
                    this.pollTimer = setInterval(() => this.poll(), 2500);
                },

                async poll() {
                    if (!this.scanId) return;
                    try {
                        const res = await fetch(`/menu-scan/${this.scanId}/status`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                        });
                        const data = await res.json();

                        if (data.status === 'completed') {
                            clearInterval(this.pollTimer);
                            if (!data.result?.categories?.length) {
                                this.showError('{{ __('menu_owner.menu_scan.error_no_data') }}');
                                return;
                            }
                            this.result = data.result;
                            this.state = 'preview';
                        } else if (data.status === 'failed') {
                            clearInterval(this.pollTimer);
                            const errMsg = data.error ?? '';
                            if (errMsg.toLowerCase().includes('rate limit')) {
                                this.state = 'rate-limited';
                            } else {
                                this.showError(errMsg || '{{ __('menu_owner.menu_scan.error_no_data') }}');
                            }
                        } else {
                            this.scanStatusText = data.status === 'processing'
                                ? '{{ __('menu_owner.menu_scan.ai_reading') }}'
                                : '{{ __('menu_owner.menu_scan.in_queue') }}';
                        }
                    } catch (e) {
                        // network hiccup — keep polling
                    }
                },

                async importMenu() {
                    if (this.importing) return;
                    this.importing = true;
                    try {
                        const res = await fetch(`/menu-scan/${this.scanId}/import`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ categories: this.result.categories }),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message ?? 'Import failed.');
                        this.doneMessage = data.message;
                        this.state = 'done';
                    } catch (e) {
                        this.importing = false;
                        this.showError(e.message);
                    }
                },

                cancel() {
                    clearInterval(this.pollTimer);
                    this.reset();
                },

                showError(msg) {
                    clearInterval(this.pollTimer);
                    this.errorMsg = msg;
                    this.state = 'error';
                },

                reset() {
                    clearInterval(this.pollTimer);
                    this.state = 'idle';
                    this.file = null;
                    this.preview = null;
                    this.fileName = null;
                    this.scanId = null;
                    this.result = null;
                    this.errorMsg = null;
                    this.importing = false;
                    this.scanStatusText = '{{ __('menu_owner.menu_scan.uploading') }}';
                },
            };
        }
    </script>
    @endpush

</x-sidebar-layout>
