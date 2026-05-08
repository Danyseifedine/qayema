<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">Scan Menu</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">Scan Menu</h1>
            <p style="font-size:13px;color:var(--muted);margin-top:2px">
                Upload a photo of your paper menu — AI will extract all categories and dishes automatically.
            </p>
        </div>

        @if (!$restaurant)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                Please create your restaurant first before scanning a menu.
                <a href="{{ route('menu-owner.restaurant.index') }}" class="underline ms-2">Set up restaurant</a>
            </div>
        @else
            <div x-data="menuScanner()" x-init="init()">

                {{-- ── Step 1: Upload ─────────────────────────────────────── --}}
                <template x-if="state === 'idle'">
                    <div class="qp-card" style="max-width:560px">
                        <div class="qp-card-body" style="padding:32px">

                            {{-- Drop zone --}}
                            <div
                                class="ui-uploader"
                                :class="{ 'drag': dragging }"
                                style="cursor:pointer;min-height:180px"
                                @click="$refs.fileInput.click()"
                                @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false"
                                @drop.prevent="dragging = false; pickFile($event.dataTransfer.files[0])"
                            >
                                <template x-if="!preview">
                                    <div style="display:contents">
                                        <div class="up-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                                <circle cx="12" cy="13" r="4"/>
                                            </svg>
                                        </div>
                                        <div class="up-title">Drop your menu photo here</div>
                                        <div class="up-meta">JPG, PNG or WebP · max 10 MB</div>
                                    </div>
                                </template>

                                <template x-if="preview">
                                    <div style="display:contents">
                                        <img :src="preview" style="max-height:220px;border-radius:8px;object-fit:contain">
                                        <div class="up-meta" style="margin-top:8px" x-text="fileName"></div>
                                    </div>
                                </template>

                                <input x-ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" style="display:none"
                                       @change="pickFile($event.target.files[0])">
                            </div>

                            <div style="display:flex;gap:10px;margin-top:16px">
                                <x-btn type="button" variant="primary" size="sm"
                                       ::disabled="!file"
                                       @click="startScan()"
                                       style="flex:1">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                                    </svg>
                                    Scan with AI
                                </x-btn>
                                <template x-if="preview">
                                    <x-btn type="button" variant="secondary" size="sm" @click="reset()">
                                        Clear
                                    </x-btn>
                                </template>
                            </div>

                        </div>
                    </div>
                </template>

                {{-- ── Step 2: Scanning (polling) ─────────────────────────── --}}
                <template x-if="state === 'scanning'">
                    <div class="qp-card" style="max-width:560px">
                        <div class="qp-card-body" style="padding:48px 32px;text-align:center">
                            <div style="display:flex;justify-content:center;margin-bottom:20px">
                                <div style="width:56px;height:56px;border-radius:50%;background:var(--olive-pale);display:flex;align-items:center;justify-content:center">
                                    <svg style="width:28px;height:28px;color:var(--olive-deep);animation:spin 1.2s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                    </svg>
                                </div>
                            </div>
                            <p style="font-size:15px;font-weight:600;color:var(--ink);margin:0 0 6px" x-text="scanStatusText"></p>
                            <p style="font-size:13px;color:var(--muted);margin:0">This usually takes 5–15 seconds.</p>

                            <button type="button" @click="cancel()"
                                    style="margin-top:24px;font-size:12px;color:var(--muted);background:none;border:none;cursor:pointer;text-decoration:underline">
                                Cancel
                            </button>
                        </div>
                    </div>
                </template>

                {{-- ── Step 3: Preview results ──────────────────────────── --}}
                <template x-if="state === 'preview'">
                    <div>
                        {{-- Summary bar --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
                            <div>
                                <p style="font-size:15px;font-weight:600;color:var(--ink);margin:0">
                                    Found <span x-text="result.categories.length" style="color:var(--olive-deep)"></span>
                                    categories and <span x-text="totalDishes" style="color:var(--olive-deep)"></span> dishes
                                </p>
                                <p style="font-size:13px;color:var(--muted);margin:4px 0 0">
                                    Review the results below, then click Import to add them to your menu.
                                </p>
                            </div>
                            <div style="display:flex;gap:8px">
                                <x-btn type="button" variant="secondary" size="sm" @click="reset()">
                                    Start over
                                </x-btn>
                                <x-btn type="button" variant="primary" size="sm" @click="importMenu()" ::disabled="importing">
                                    <template x-if="!importing">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </template>
                                    <template x-if="importing">
                                        <svg class="w-4 h-4" style="animation:spin 1s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                        </svg>
                                    </template>
                                    <span x-text="importing ? 'Importing…' : 'Import to My Menu'"></span>
                                </x-btn>
                            </div>
                        </div>

                        {{-- Categories + dishes --}}
                        <div style="display:grid;gap:16px">
                            <template x-for="(category, ci) in result.categories" :key="ci">
                                <div class="qp-card">
                                    <div class="qp-card-body">
                                        {{-- Category header --}}
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                                            <span style="width:28px;height:28px;border-radius:6px;background:var(--olive-pale);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                                <svg style="width:14px;height:14px;color:var(--olive-deep)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                                                </svg>
                                            </span>
                                            <span style="font-size:14px;font-weight:600;color:var(--ink)" x-text="category.name"></span>
                                            <span style="font-size:12px;color:var(--muted);margin-left:auto" x-text="category.dishes.length + ' dishes'"></span>
                                        </div>

                                        {{-- Dishes grid --}}
                                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
                                            <template x-for="(dish, di) in category.dishes" :key="di">
                                                <div style="padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface)">
                                                    <div style="display:flex;justify-content:space-between;align-items:start;gap:8px">
                                                        <span style="font-size:13px;font-weight:600;color:var(--ink)" x-text="dish.name"></span>
                                                        <span x-show="dish.price" style="font-size:12px;font-weight:600;color:var(--olive-deep);flex-shrink:0" x-text="'$' + dish.price"></span>
                                                    </div>
                                                    <p x-show="dish.ingredients" style="font-size:11px;color:var(--muted);margin:4px 0 0;line-height:1.4" x-text="dish.ingredients"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- ── Done ─────────────────────────────────────────────── --}}
                <template x-if="state === 'done'">
                    <div class="qp-card" style="max-width:560px">
                        <div class="qp-card-body" style="padding:48px 32px;text-align:center">
                            <div style="width:56px;height:56px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                                <svg style="width:28px;height:28px;color:#16a34a" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </div>
                            <p style="font-size:16px;font-weight:600;color:var(--ink);margin:0 0 6px" x-text="doneMessage"></p>
                            <p style="font-size:13px;color:var(--muted);margin:0 0 24px">Your menu has been imported successfully.</p>
                            <div style="display:flex;justify-content:center;gap:10px">
                                <x-btn href="{{ route('menu-owner.categories.index') }}" variant="primary" size="sm">
                                    View Categories
                                </x-btn>
                                <x-btn href="{{ route('menu-owner.dishes.index') }}" variant="secondary" size="sm">
                                    View Dishes
                                </x-btn>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ── Error ────────────────────────────────────────────── --}}
                <template x-if="state === 'error'">
                    <div class="qp-card" style="max-width:560px">
                        <div class="qp-card-body" style="padding:32px;text-align:center">
                            <div style="width:48px;height:48px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                                <svg style="width:24px;height:24px;color:#dc2626" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                                </svg>
                            </div>
                            <p style="font-size:14px;font-weight:600;color:var(--ink);margin:0 0 6px" x-text="errorMsg"></p>
                            <x-btn type="button" variant="secondary" size="sm" @click="reset()" style="margin-top:16px">
                                Try again
                            </x-btn>
                        </div>
                    </div>
                </template>

            </div>
        @endif
    </div>

    @push('scripts')
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
    <script>
        function menuScanner() {
            return {
                state: 'idle',
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
                scanStatusText: 'Sending to AI...',

                init() {},

                get totalDishes() {
                    return (this.result?.categories ?? []).reduce((sum, cat) => sum + (cat.dishes?.length ?? 0), 0);
                },

                pickFile(file) {
                    if (!file) return;
                    const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                    if (!allowed.includes(file.type)) {
                        this.errorMsg = 'Please upload a JPG, PNG or WebP image.';
                        this.state = 'error';
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        this.errorMsg = 'Image must be smaller than 10 MB.';
                        this.state = 'error';
                        return;
                    }
                    this.file = file;
                    this.fileName = file.name;
                    const reader = new FileReader();
                    reader.onload = e => { this.preview = e.target.result; };
                    reader.readAsDataURL(file);
                },

                async startScan() {
                    if (!this.file) return;
                    this.state = 'scanning';
                    this.scanStatusText = 'Uploading image...';

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
                        if (!res.ok) throw new Error(data.message ?? 'Upload failed.');

                        this.scanId = data.scan_id;
                        this.scanStatusText = 'Analysing your menu...';
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
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        });
                        const data = await res.json();

                        if (data.status === 'completed') {
                            clearInterval(this.pollTimer);
                            if (!data.result?.categories?.length) {
                                this.showError('No menu data could be extracted. Try a clearer photo.');
                                return;
                            }
                            this.result = data.result;
                            this.state = 'preview';
                        } else if (data.status === 'failed') {
                            clearInterval(this.pollTimer);
                            this.showError(data.error ?? 'Scan failed. Please try again.');
                        } else {
                            this.scanStatusText = data.status === 'processing'
                                ? 'AI is reading your menu...'
                                : 'Waiting in queue...';
                        }
                    } catch (e) {
                        // network hiccup — keep polling, don't abort
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
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
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
                    this.scanStatusText = 'Sending to AI...';
                },
            };
        }
    </script>
    @endpush

</x-sidebar-layout>
