@php
    $appName  = config('app.name', 'Qayema');
    $currencies = ['USD','EUR','GBP','AED','SAR','LBP','EGP','MAD','TND','JOD','QAR','KWD','BHD','OMR','TRY','CAD','AUD'];
    $languages  = ['en'=>'English','ar'=>'العربية','fr'=>'Français','de'=>'Deutsch','es'=>'Español','it'=>'Italiano','pt'=>'Português','ru'=>'Русский','tr'=>'Türkçe','hi'=>'हिन्दी','zh'=>'中文','ja'=>'日本語','ko'=>'한국어','nl'=>'Nederlands'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Get Started — {{ $appName }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
@vite(['resources/css/login.css', 'resources/js/app.js'])
<style>
/* ── Layout ─────────────────────────────────────────── */
.onb-wrap {
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 40px 16px 60px;
    background: radial-gradient(80% 60% at -10% -10%, rgba(200,168,90,.14), transparent 60%), var(--paper);
}
.onb-card {
    width: 100%;
    max-width: 580px;
    background: #fff;
    border-radius: 20px;
    border: 1px solid var(--line);
    box-shadow: 0 4px 32px rgba(15,15,16,.07);
    padding: 40px 44px 36px;
}
@media (max-width: 600px) { .onb-card { padding: 28px 20px 24px; } }

/* ── Logo ───────────────────────────────────────────── */
.onb-logo { margin-bottom: 32px; }
.onb-logo img { height: 44px; width: auto; }

/* ── Progress ───────────────────────────────────────── */
.onb-progress { margin-bottom: 32px; }
.onb-progress-meta { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); margin-bottom: 8px; }
.onb-progress-track { height: 4px; background: var(--sand); border-radius: 99px; overflow: hidden; }
.onb-progress-fill { height: 100%; background: var(--accent); border-radius: 99px; transition: width .4s cubic-bezier(.4,0,.2,1); }

/* ── Step header ────────────────────────────────────── */
.onb-eyebrow { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--accent-deep); margin-bottom: 10px; }
.onb-eyebrow span { width: 18px; height: 1px; background: var(--accent); display: block; }
.onb-title { font-family: var(--font-display); font-size: 24px; line-height: 1.2; color: var(--ink); margin: 0 0 6px; }
.onb-desc { font-size: 13.5px; color: var(--muted); line-height: 1.6; margin: 0 0 24px; }

/* ── Form fields ────────────────────────────────────── */
.onb-fields { display: flex; flex-direction: column; gap: 16px; }
.onb-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.onb-field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
.onb-field input,
.onb-field select {
    width: 100%; padding: 9px 12px; font-size: 14px; font-family: var(--font-sans);
    background: var(--paper); border: 1.5px solid var(--sand-2); border-radius: 9px;
    color: var(--ink); outline: none; transition: border-color .15s;
    appearance: none; -webkit-appearance: none;
}
.onb-field input:focus, .onb-field select:focus { border-color: var(--accent); }
.onb-field .onb-fe-error { font-size: 12px; color: #dc2626; margin-top: 4px; }

/* ── Upload areas (Step 2) ──────────────────────────── */
.onb-uploads { display: flex; flex-direction: column; gap: 16px; }
.onb-upload { border: 2px dashed var(--sand-2); border-radius: 12px; overflow: hidden; cursor: pointer; transition: border-color .2s; }
.onb-upload:hover { border-color: var(--accent); }
.onb-upload-inner { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 28px 20px; }
.onb-upload-label { font-size: 12.5px; font-weight: 600; color: var(--ink); }
.onb-upload-hint { font-size: 12px; color: var(--muted); }
.onb-upload-preview { width: 100%; height: 140px; object-fit: cover; display: block; }
.onb-upload-logo-preview { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
.onb-upload-replace { font-size: 11.5px; color: var(--accent-deep); margin-top: 6px; }
.onb-upload-uploading { font-size: 12px; color: var(--muted); animation: onb-pulse 1s ease infinite; }
@keyframes onb-pulse { 0%,100% { opacity:1 } 50% { opacity:.4 } }

/* ── Type grid (Step 3) ─────────────────────────────── */
.onb-type-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(115px, 1fr)); gap: 10px; margin-bottom: 24px; }
.onb-type-card {
    border: 2px solid var(--sand-2); border-radius: 12px; padding: 14px 10px;
    display: flex; flex-direction: column; align-items: center; gap: 7px;
    cursor: pointer; transition: border-color .15s, background .15s;
    text-align: center;
}
.onb-type-card:hover { border-color: var(--accent-soft); background: var(--paper); }
.onb-type-card.selected { border-color: var(--accent); background: rgba(200,168,90,.08); }
.onb-type-icon { font-size: 26px; line-height: 1; }
.onb-type-name { font-size: 12.5px; font-weight: 600; color: var(--ink); }

/* ── Tags (Step 3) ──────────────────────────────────── */
.onb-tag-section { margin-bottom: 20px; }
.onb-tag-category { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
.onb-tag-chips { display: flex; flex-wrap: wrap; gap: 7px; }
.onb-tag-chip {
    border: 1.5px solid var(--sand-2); border-radius: 99px; padding: 5px 13px;
    font-size: 12.5px; font-weight: 500; color: var(--ink); cursor: pointer;
    transition: border-color .15s, background .15s;
}
.onb-tag-chip:hover { border-color: var(--accent-soft); background: var(--paper); }
.onb-tag-chip.selected { border-color: var(--accent); background: rgba(200,168,90,.08); color: var(--accent-deep); }

/* ── Template grid (Step 4) ─────────────────────────── */
.onb-tpl-section-label { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
.onb-tpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
.onb-tpl-card {
    border: 2px solid var(--sand-2); border-radius: 12px; overflow: hidden;
    cursor: pointer; transition: border-color .15s;
}
.onb-tpl-card:hover { border-color: var(--accent-soft); }
.onb-tpl-card.selected { border-color: var(--accent); }
.onb-tpl-thumb { width: 100%; height: 90px; background: var(--paper); display: flex; align-items: center; justify-content: center; }
.onb-tpl-thumb img { width: 100%; height: 100%; object-fit: cover; }
.onb-tpl-no-thumb { font-size: 28px; }
.onb-tpl-name { font-size: 12.5px; font-weight: 600; color: var(--ink); padding: 8px 10px 9px; }
.onb-tpl-badge { display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; background: rgba(200,168,90,.15); color: var(--accent-deep); border-radius: 4px; padding: 1px 5px; margin: 0 0 2px 6px; vertical-align: middle; }

/* ── Actions ────────────────────────────────────────── */
.onb-actions { display: flex; align-items: center; justify-content: space-between; margin-top: 32px; gap: 12px; }
.onb-back { font-size: 13.5px; font-weight: 500; color: var(--muted); background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; gap: 6px; transition: color .18s; }
.onb-back:hover { color: var(--ink); }
.onb-back svg { width: 14px; height: 14px; }
.onb-next {
    display: inline-flex; align-items: center; gap: 8px; background: var(--ink); color: var(--paper);
    font-size: 14px; font-weight: 600; border: none; border-radius: 10px; padding: 11px 22px;
    cursor: pointer; transition: background .18s, opacity .18s; margin-left: auto;
}
.onb-next:hover { background: var(--ink-2); }
.onb-next:disabled { opacity: .55; cursor: not-allowed; }
.onb-next svg { width: 14px; height: 14px; }
@keyframes onb-spin { to { transform: rotate(360deg); } }
.onb-spin { animation: onb-spin .8s linear infinite; }

/* ── Error alert ────────────────────────────────────── */
.onb-alert { background: #fef2f2; border: 1px solid #fecaca; border-radius: 9px; padding: 10px 14px; font-size: 13px; color: #dc2626; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="onb-wrap">
<div class="onb-card"
     x-data="{
         step: {{ $step }},
         totalSteps: {{ $totalSteps }},
         loading: false,
         errors: {},
         globalError: '',

         /* Step 1 */
         s1: { name: '', country_code: '', phone: '', currency: 'USD', preferred_language: 'en' },

         /* Step 2 */
         logoKey: null, logoPreview: null, logoUploading: false,
         coverKey: null, coverPreview: null, coverUploading: false,

         /* Step 3 */
         selectedTypeId: null,
         selectedTagIds: [],
         selectedTagSlugs: [],

         toggleTag(id, slug) {
             const i = this.selectedTagIds.indexOf(id);
             if (i === -1) { this.selectedTagIds.push(id); this.selectedTagSlugs.push(slug); }
             else { this.selectedTagIds.splice(i, 1); this.selectedTagSlugs.splice(this.selectedTagSlugs.indexOf(slug), 1); }
         },

         /* Step 4 */
         selectedTemplateId: null,
         allTemplates: @json($templates->map(fn($t) => [
             'id'       => $t->id,
             'name'     => $t->name,
             'thumbnail'=> $t->getFirstMediaUrl('thumbnail'),
             'tagSlugs' => $t->tags->pluck('slug')->values(),
         ])),

         get recommendedTemplates() {
             if (!this.selectedTagSlugs.length) return [];
             return this.allTemplates.filter(t => t.tagSlugs.some(s => this.selectedTagSlugs.includes(s)));
         },
         get otherTemplates() {
             return this.allTemplates;
         },

         /* File upload */
         async uploadFile(file, type) {
             const isLogo = type === 'logo';
             if (isLogo) this.logoUploading = true; else this.coverUploading = true;
             try {
                 const fd = new FormData();
                 fd.append('file', file);
                 fd.append('context', isLogo ? 'logo' : 'cover_image');
                 const res = await fetch('{{ route('menu-owner.temp-upload') }}', {
                     method: 'POST',
                     headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                     body: fd,
                 });
                 const data = await res.json();
                 if (isLogo) { this.logoKey = data.key; this.logoPreview = URL.createObjectURL(file); }
                 else { this.coverKey = data.key; this.coverPreview = URL.createObjectURL(file); }
             } catch { this.globalError = 'Upload failed. Please try again.'; }
             finally { if (isLogo) this.logoUploading = false; else this.coverUploading = false; }
         },

         handleFileInput(event, type) {
             const file = event.target.files[0];
             if (file) this.uploadFile(file, type);
         },

         handleDrop(event, type) {
             const file = event.dataTransfer.files[0];
             if (file) this.uploadFile(file, type);
         },

         /* Progress */
         get progressPct() { return Math.round(((this.step - 1) / this.totalSteps) * 100); },

         /* Advance */
         async advance() {
             if (this.loading) return;
             this.errors = {};
             this.globalError = '';

             /* Client-side validation */
             if (this.step === 1 && !this.s1.name.trim()) {
                 this.errors.name = 'Restaurant name is required.';
                 return;
             }
             if (this.step === 4 && !this.selectedTemplateId) {
                 this.errors.template = 'Please select a template to continue.';
                 return;
             }

             this.loading = true;
             try {
                 let body = {};
                 if (this.step === 1) body = { ...this.s1 };
                 else if (this.step === 2) body = { logo_key: this.logoKey, cover_image_key: this.coverKey };
                 else if (this.step === 3) body = { restaurant_type_id: this.selectedTypeId, tag_ids: this.selectedTagIds };
                 else if (this.step === 4) body = { template_id: this.selectedTemplateId };

                 const res = await fetch('{{ route('onboarding.advance') }}', {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify(body),
                 });

                 if (res.status === 422) {
                     const data = await res.json();
                     this.errors = data.errors ?? {};
                     this.globalError = data.message ?? '';
                     return;
                 }

                 const data = await res.json();
                 if (data.completed) { window.location = data.redirect; return; }
                 this.step = data.step;
             } catch { this.globalError = 'Something went wrong. Please try again.'; }
             finally { this.loading = false; }
         },

         back() { if (this.step > 1) this.step--; },
     }">

    {{-- Logo --}}
    <div class="onb-logo">
        <a href="{{ url('/') }}">
            <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $appName }}">
        </a>
    </div>

    {{-- Progress --}}
    <div class="onb-progress">
        <div class="onb-progress-meta">
            <span x-text="`Step ${step} of ${totalSteps}`"></span>
            <span x-text="`${progressPct}% complete`"></span>
        </div>
        <div class="onb-progress-track">
            <div class="onb-progress-fill" :style="`width: ${progressPct}%`"></div>
        </div>
    </div>

    {{-- Global error --}}
    <div class="onb-alert" x-show="globalError" x-text="globalError" x-cloak></div>

    {{-- ── Step 1 — Basics ──────────────────────────────── --}}
    <div x-show="step === 1"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak>
        <div class="onb-eyebrow"><span></span>Step 1 of {{ $totalSteps }}</div>
        <h1 class="onb-title">Your restaurant</h1>
        <p class="onb-desc">Tell us the basics. You can update everything later in the dashboard.</p>

        <div class="onb-fields">
            <div class="onb-field">
                <label>Restaurant name <span style="color:#dc2626">*</span></label>
                <input type="text" x-model="s1.name" placeholder="e.g. Joe's Burgers" autofocus>
                <div class="onb-fe-error" x-show="errors.name" x-text="errors.name" x-cloak></div>
            </div>

            <div class="onb-row">
                <div class="onb-field">
                    <label>Country code</label>
                    <input type="text" x-model="s1.country_code" placeholder="+1">
                </div>
                <div class="onb-field">
                    <label>Phone number</label>
                    <input type="text" x-model="s1.phone" placeholder="555 000 1234">
                </div>
            </div>

            <div class="onb-row">
                <div class="onb-field">
                    <label>Currency</label>
                    <select x-model="s1.currency">
                        @foreach($currencies as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="onb-field">
                    <label>Menu language</label>
                    <select x-model="s1.preferred_language">
                        @foreach($languages as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Step 2 — Branding ────────────────────────────── --}}
    <div x-show="step === 2"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak>
        <div class="onb-eyebrow"><span></span>Step 2 of {{ $totalSteps }}</div>
        <h1 class="onb-title">Your branding</h1>
        <p class="onb-desc">Upload a logo and a cover image. These appear on your public menu.</p>

        <div class="onb-uploads">
            {{-- Logo --}}
            <div class="onb-upload"
                 @dragover.prevent @drop.prevent="handleDrop($event, 'logo')"
                 @click="$refs.logoInput.click()">
                <template x-if="!logoPreview && !logoUploading">
                    <div class="onb-upload-inner">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        <div class="onb-upload-label">Logo <span style="font-weight:400;color:var(--muted)">(recommended)</span></div>
                        <div class="onb-upload-hint">Square · JPG, PNG, WebP · max 10MB</div>
                    </div>
                </template>
                <template x-if="logoUploading">
                    <div class="onb-upload-inner">
                        <div class="onb-upload-uploading">Uploading…</div>
                    </div>
                </template>
                <template x-if="logoPreview && !logoUploading">
                    <div class="onb-upload-inner">
                        <img :src="logoPreview" class="onb-upload-logo-preview">
                        <div class="onb-upload-replace">Click to replace</div>
                    </div>
                </template>
                <input type="file" x-ref="logoInput" accept="image/jpeg,image/png,image/webp"
                       @change="handleFileInput($event, 'logo')" style="display:none">
            </div>

            {{-- Cover image --}}
            <div class="onb-upload"
                 @dragover.prevent @drop.prevent="handleDrop($event, 'cover_image')"
                 @click="$refs.coverInput.click()">
                <template x-if="!coverPreview && !coverUploading">
                    <div class="onb-upload-inner">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        <div class="onb-upload-label">Cover image <span style="font-weight:400;color:var(--muted)">(optional)</span></div>
                        <div class="onb-upload-hint">Wide banner · 1920×600 recommended · max 10MB</div>
                    </div>
                </template>
                <template x-if="coverUploading">
                    <div class="onb-upload-inner">
                        <div class="onb-upload-uploading">Uploading…</div>
                    </div>
                </template>
                <template x-if="coverPreview && !coverUploading">
                    <img :src="coverPreview" class="onb-upload-preview">
                </template>
                <input type="file" x-ref="coverInput" accept="image/jpeg,image/png,image/webp"
                       @change="handleFileInput($event, 'cover_image')" style="display:none">
            </div>
        </div>
    </div>

    {{-- ── Step 3 — Profile ─────────────────────────────── --}}
    <div x-show="step === 3"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak>
        <div class="onb-eyebrow"><span></span>Step 3 of {{ $totalSteps }}</div>
        <h1 class="onb-title">Your restaurant's style</h1>
        <p class="onb-desc">Pick your type and a few tags. We'll use these to recommend the best template for you.</p>

        {{-- Restaurant type --}}
        <div class="onb-type-grid">
            @foreach($restaurantTypes as $type)
            <div class="onb-type-card"
                 :class="{ selected: selectedTypeId === {{ $type->id }} }"
                 @click="selectedTypeId = (selectedTypeId === {{ $type->id }} ? null : {{ $type->id }})">
                <span class="onb-type-icon">{{ $type->icon }}</span>
                <span class="onb-type-name">{{ $type->name }}</span>
            </div>
            @endforeach
        </div>

        {{-- Tags grouped by category --}}
        @foreach($tags as $category => $categoryTags)
        <div class="onb-tag-section">
            <div class="onb-tag-category">{{ ucfirst($category) }}</div>
            <div class="onb-tag-chips">
                @foreach($categoryTags as $tag)
                <div class="onb-tag-chip"
                     :class="{ selected: selectedTagIds.includes({{ $tag->id }}) }"
                     @click="toggleTag({{ $tag->id }}, '{{ $tag->slug }}')">
                    {{ $tag->name }}
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Step 4 — Template ────────────────────────────── --}}
    <div x-show="step === 4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak>
        <div class="onb-eyebrow"><span></span>Step 4 of {{ $totalSteps }}</div>
        <h1 class="onb-title">Pick a template</h1>
        <p class="onb-desc">Choose how your public menu will look. You can change this anytime.</p>

        <div class="onb-fe-error" x-show="errors.template" x-text="errors.template" x-cloak style="margin-bottom:14px"></div>

        {{-- Recommended --}}
        <template x-if="recommendedTemplates.length > 0">
            <div>
                <div class="onb-tpl-section-label">Recommended for you</div>
                <div class="onb-tpl-grid">
                    <template x-for="t in recommendedTemplates" :key="t.id">
                        <div class="onb-tpl-card"
                             :class="{ selected: selectedTemplateId === t.id }"
                             @click="selectedTemplateId = t.id">
                            <div class="onb-tpl-thumb">
                                <template x-if="t.thumbnail">
                                    <img :src="t.thumbnail" :alt="t.name">
                                </template>
                                <template x-if="!t.thumbnail">
                                    <span class="onb-tpl-no-thumb">🎨</span>
                                </template>
                            </div>
                            <div class="onb-tpl-name" x-text="t.name"></div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- All / remaining --}}
        <template x-if="otherTemplates.length > 0">
            <div>
                <div class="onb-tpl-section-label">All templates</div>
                <div class="onb-tpl-grid">
                    <template x-for="t in otherTemplates" :key="t.id">
                        <div class="onb-tpl-card"
                             :class="{ selected: selectedTemplateId === t.id }"
                             @click="selectedTemplateId = t.id">
                            <div class="onb-tpl-thumb">
                                <template x-if="t.thumbnail">
                                    <img :src="t.thumbnail" :alt="t.name">
                                </template>
                                <template x-if="!t.thumbnail">
                                    <span class="onb-tpl-no-thumb">🎨</span>
                                </template>
                            </div>
                            <div class="onb-tpl-name" x-text="t.name"></div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- No templates at all --}}
        <template x-if="allTemplates.length === 0">
            <div style="text-align:center;padding:32px;color:var(--muted);font-size:13.5px">
                No templates available yet.
            </div>
        </template>
    </div>

    {{-- ── Actions ──────────────────────────────────────── --}}
    <div class="onb-actions">
        <button type="button" class="onb-back" x-show="step > 1" @click="back()" x-cloak>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
            Back
        </button>

        <button type="button" class="onb-next" @click="advance()" :disabled="loading || logoUploading || coverUploading">
            <template x-if="!loading">
                <span x-text="step === totalSteps ? 'Finish' : 'Continue'"></span>
            </template>
            <template x-if="loading">
                <span>Please wait…</span>
            </template>
            <template x-if="!loading">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </template>
            <template x-if="loading">
                <svg class="onb-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
            </template>
        </button>
    </div>

</div>
</div>
</body>
</html>
