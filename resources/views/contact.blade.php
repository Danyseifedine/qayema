<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | Qayema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&family=Noto+Kufi+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/welcome.css', 'resources/js/app.js'])
    <style>
        body { background: var(--ink-2); }
        .ctc-page { background: var(--ink-2); min-height: 100vh; color: var(--paper); }
        .ctc-body { padding: 72px 0 100px; }
        .ctc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 72px;
            align-items: start;
        }
        /* Left */
        .ctc-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 11px; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: var(--accent); margin-bottom: 24px;
        }
        .ctc-eyebrow .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--accent); }
        .ctc-headline {
            font-size: clamp(34px, 4.5vw, 56px); font-weight: 500;
            letter-spacing: -0.03em; line-height: 1.08; color: var(--paper); margin: 0 0 20px;
        }
        .ctc-headline .it { font-family: var(--font-display); font-style: italic; font-weight: 400; }
        .ctc-sub {
            font-size: 15.5px; line-height: 1.65; color: rgba(246,241,232,.5);
            margin: 0 0 48px; max-width: 38ch;
        }
        .ctc-info { display: flex; flex-direction: column; gap: 14px; }
        .ctc-info-item {
            display: flex; align-items: flex-start; gap: 16px;
            padding: 20px; border-radius: 14px;
            border: 1px solid rgba(246,241,232,.07);
            background: rgba(246,241,232,.03);
            transition: border-color .2s, background .2s;
        }
        .ctc-info-item:hover { border-color: rgba(200,168,90,.18); background: rgba(200,168,90,.04); }
        .ctc-info-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(200,168,90,.1); display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
            color: var(--accent);
        }
        .ctc-info-label {
            font-size: 11px; font-weight: 700; letter-spacing: .09em;
            text-transform: uppercase; color: rgba(246,241,232,.35); margin: 0 0 4px;
        }
        .ctc-info-value { font-size: 14px; color: rgba(246,241,232,.8); margin: 0; line-height: 1.5; }
        .ctc-info-value a { color: rgba(246,241,232,.8); text-decoration: none; transition: color .15s; }
        .ctc-info-value a:hover { color: var(--accent); }
        /* Right */
        .ctc-form-wrap { position: sticky; top: 100px; }
        .ctc-form-card {
            background: rgba(246,241,232,.04);
            border: 1px solid rgba(246,241,232,.09);
            border-radius: 20px; padding: 40px;
            backdrop-filter: blur(12px);
        }
        .ctc-form-title { font-size: 18px; font-weight: 600; color: var(--paper); margin: 0 0 6px; }
        .ctc-form-sub { font-size: 13.5px; color: rgba(246,241,232,.4); margin: 0 0 32px; }
        .ctc-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .ctc-field { margin-bottom: 18px; }
        .ctc-label {
            display: block; font-size: 12px; font-weight: 600; letter-spacing: .06em;
            text-transform: uppercase; color: rgba(246,241,232,.45); margin-bottom: 8px;
        }
        .ctc-input, .ctc-textarea {
            width: 100%; background: rgba(246,241,232,.055);
            border: 1px solid rgba(246,241,232,.1); border-radius: 10px;
            padding: 12px 16px; font-size: 14.5px; font-family: inherit;
            color: rgba(246,241,232,.9); outline: none;
            transition: border-color .18s, box-shadow .18s, background .18s;
            box-sizing: border-box;
        }
        .ctc-input::placeholder, .ctc-textarea::placeholder { color: rgba(246,241,232,.22); }
        .ctc-input:focus, .ctc-textarea:focus {
            border-color: rgba(200,168,90,.55);
            background: rgba(200,168,90,.05);
            box-shadow: 0 0 0 3px rgba(200,168,90,.1);
        }
        .ctc-input.is-error, .ctc-textarea.is-error {
            border-color: rgba(231,76,60,.55);
            box-shadow: 0 0 0 3px rgba(231,76,60,.08);
        }
        .ctc-textarea { resize: none; height: 140px; line-height: 1.65; }
        .ctc-field-foot {
            display: flex; align-items: center;
            justify-content: space-between; margin-top: 5px; min-height: 18px;
        }
        .ctc-error { font-size: 12px; color: #e87e74; margin: 5px 0 0; line-height: 1.4; }
        .ctc-char-count { font-size: 11.5px; color: rgba(246,241,232,.25); margin-left: auto; flex-shrink: 0; }
        .ctc-char-count.over { color: #e87e74; }
        .ctc-rate-banner {
            display: flex; align-items: flex-start; gap: 12px;
            background: rgba(231,76,60,.08); border: 1px solid rgba(231,76,60,.2);
            border-radius: 10px; padding: 14px 16px; margin-bottom: 22px;
            font-size: 13.5px; color: #e87e74; line-height: 1.5;
        }
        .ctc-rate-banner svg { flex-shrink: 0; margin-top: 2px; }
        .ctc-btn {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 28px; background: var(--accent); color: var(--ink-2);
            border: none; border-radius: 10px; font-size: 14.5px; font-weight: 700;
            font-family: inherit; cursor: pointer; letter-spacing: .02em;
            transition: opacity .15s, transform .1s; margin-top: 8px;
        }
        .ctc-btn:hover { opacity: .88; transform: translateY(-1px); }
        .ctc-btn:active { opacity: .78; transform: translateY(0); }
        /* Success */
        .ctc-success {
            display: flex; flex-direction: column; align-items: center;
            text-align: center; padding: 48px 16px 40px;
        }
        .ctc-success-ring {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg,rgba(200,168,90,.2),rgba(200,168,90,.06));
            border: 1px solid rgba(200,168,90,.25);
            display: flex; align-items: center; justify-content: center; margin-bottom: 24px;
        }
        .ctc-success h3 { font-size: 22px; font-weight: 600; color: var(--paper); margin: 0 0 10px; }
        .ctc-success p { font-size: 14.5px; color: rgba(246,241,232,.45); margin: 0 0 28px; line-height: 1.6; max-width: 30ch; }
        .ctc-success-back {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13.5px; font-weight: 600; color: var(--accent);
            text-decoration: none; transition: gap .15s;
        }
        .ctc-success-back:hover { gap: 10px; }
        /* Responsive */
        @media (max-width: 900px) {
            .ctc-grid { grid-template-columns: 1fr; gap: 48px; }
            .ctc-form-wrap { position: static; }
            .ctc-sub { max-width: 100%; }
        }
        @media (max-width: 600px) {
            .ctc-row { grid-template-columns: 1fr; gap: 0; }
            .ctc-form-card { padding: 28px 22px; border-radius: 16px; }
            .ctc-body { padding: 48px 0 72px; }
        }
    </style>
</head>
<body>

<div id="page" class="ctc-page" x-data="QayemaApp()" x-init="document.documentElement.lang = lang; document.documentElement.dir = isAr ? 'rtl' : 'ltr';">

    {{-- Nav --}}
    <nav class="nav">
        <div class="nav-inner wrap">
            <a class="brand" href="/"><img src="{{ asset('images/logo/logo.png') }}" alt="Qayema" class="brand-logo"></a>
            <div class="nav-links">
                <a href="/#features" x-text="t.nav.features">Features</a>
                <a href="/#how" x-text="t.nav.how">How it works</a>
                <a href="/#stories" x-text="t.nav.stories">Stories</a>
            </div>
            <div class="nav-end">
                <div class="lang-switch nav-lang">
                    <button :class="lang === 'en' ? 'on' : ''" @click="setLang('en')">EN</button>
                    <button :class="lang === 'ar' ? 'on' : ''" @click="setLang('ar')">عربي</button>
                </div>
                @auth
                    <a class="btn btn-ink btn-sm nav-cta" href="{{ route('dashboard') }}">
                        <span x-text="t.nav.dashboard">Dashboard</span>
                        <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @else
                    <a class="btn btn-ink btn-sm nav-cta" href="{{ route('login') }}">
                        <span x-text="t.nav.cta">Get started free</span>
                        <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endauth
                <button class="nav-burger" @click="mobileOpen = !mobileOpen" aria-label="Menu">
                    <svg x-show="!mobileOpen" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M2 4.5h14M2 9h14M2 13.5h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <svg x-show="mobileOpen" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>
        <div class="nav-mobile" :class="mobileOpen ? 'nav-mobile--open' : ''">
            <div class="wrap nav-mobile-inner">
                <div class="nav-mobile-links">
                    <a href="/#features" @click="mobileOpen=false" x-text="t.nav.features">Features</a>
                    <a href="/#how" @click="mobileOpen=false" x-text="t.nav.how">How it works</a>
                    <a href="/#stories" @click="mobileOpen=false" x-text="t.nav.stories">Stories</a>
                </div>
                <div class="nav-mobile-foot">
                    <div class="lang-switch">
                        <button :class="lang === 'en' ? 'on' : ''" @click="setLang('en')">EN</button>
                        <button :class="lang === 'ar' ? 'on' : ''" @click="setLang('ar')">عربي</button>
                    </div>
                    @auth
                        <a class="btn btn-ink btn-sm nav-mobile-cta" href="{{ route('dashboard') }}" @click="mobileOpen=false">
                            <span x-text="t.nav.dashboard">Dashboard</span>
                        </a>
                    @else
                        <a class="btn btn-ink btn-sm nav-mobile-cta" href="{{ route('login') }}" @click="mobileOpen=false">
                            <span x-text="t.nav.cta">Get started free</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Body --}}
    <div class="ctc-body">
        <div class="wrap">
            <div class="ctc-grid">

                {{-- Left: info --}}
                <div>
                    <div class="ctc-eyebrow">
                        <span class="dot"></span>
                        <span x-show="!isAr">Company</span>
                        <span x-show="isAr">الشركة</span>
                    </div>
                    <h1 class="ctc-headline">
                        <span x-show="!isAr">Get in <span class="it">Touch</span></span>
                        <span x-show="isAr">تواصل <span class="it">معنا</span></span>
                    </h1>
                    <p class="ctc-sub">
                        <span x-show="!isAr">Have a question, a partnership idea, or just want to say hello? Drop us a message and we'll get back to you.</span>
                        <span x-show="isAr">هل لديك سؤال أو فكرة شراكة أو تريد فقط أن تقول مرحباً؟ أرسل لنا رسالة وسنردّ عليك.</span>
                    </p>

                    <div class="ctc-info">
                        <div class="ctc-info-item">
                            <div class="ctc-info-icon">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1.5" y="3.5" width="13" height="9" rx="1.5"/><path d="M1.5 5l6.5 4.5L14.5 5"/></svg>
                            </div>
                            <div>
                                <p class="ctc-info-label"><span x-show="!isAr">Email</span><span x-show="isAr">البريد الإلكتروني</span></p>
                                <p class="ctc-info-value"><a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a></p>
                            </div>
                        </div>
                        <div class="ctc-info-item">
                            <div class="ctc-info-icon">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 1.5a4.5 4.5 0 0 1 4.5 4.5c0 3-4.5 8.5-4.5 8.5S3.5 9 3.5 6A4.5 4.5 0 0 1 8 1.5Z"/><circle cx="8" cy="6" r="1.5"/></svg>
                            </div>
                            <div>
                                <p class="ctc-info-label"><span x-show="!isAr">Location</span><span x-show="isAr">الموقع</span></p>
                                <p class="ctc-info-value"><span x-show="!isAr">Barja, Lebanon</span><span x-show="isAr">برجا، لبنان</span></p>
                            </div>
                        </div>
                        <div class="ctc-info-item">
                            <div class="ctc-info-icon">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5v3.75l2.5 1.5"/></svg>
                            </div>
                            <div>
                                <p class="ctc-info-label"><span x-show="!isAr">Response time</span><span x-show="isAr">وقت الرد</span></p>
                                <p class="ctc-info-value"><span x-show="!isAr">Usually within 24 hours</span><span x-show="isAr">عادةً خلال 24 ساعة</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: form --}}
                <div class="ctc-form-wrap">
                    <div class="ctc-form-card">

                        @if(session('success'))
                            <div class="ctc-success">
                                <div class="ctc-success-ring">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M6 14l5.5 5.5L22 9" stroke="#C8A85A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <h3 x-show="!isAr">Message sent!</h3>
                                <h3 x-show="isAr">تم الإرسال!</h3>
                                <p x-show="!isAr">Thanks for reaching out. We'll get back to you as soon as possible.</p>
                                <p x-show="isAr">شكرًا للتواصل معنا. سنردّ عليك في أقرب وقت ممكن.</p>
                                <a href="{{ route('contact') }}" class="ctc-success-back">
                                    <span x-show="!isAr">Send another message</span>
                                    <span x-show="isAr">إرسال رسالة أخرى</span>
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        @else

                            <p class="ctc-form-title">
                                <span x-show="!isAr">Send us a message</span>
                                <span x-show="isAr">أرسل لنا رسالة</span>
                            </p>
                            <p class="ctc-form-sub">
                                <span x-show="!isAr">We read every message personally.</span>
                                <span x-show="isAr">نقرأ كل رسالة شخصياً.</span>
                            </p>

                            @if($errors->has('rate_limit'))
                                <div class="ctc-rate-banner">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6.5"/><path d="M8 5v3.5M8 11h.01"/></svg>
                                    {{ $errors->first('rate_limit') }}
                                </div>
                            @endif

                            {{--
                                x-data initialises field values from server old() so the form
                                repopulates after a failed server-side submission.
                                Textarea uses x-model bound to f.message (a string) — NOT to a
                                counter variable, which was the bug causing text to be erased.
                            --}}
                            <form
                                method="POST"
                                action="{{ route('contact.store') }}"
                                x-data="{
                                    f: {
                                        name:    {{ json_encode(old('name',    '')) }},
                                        email:   {{ json_encode(old('email',   '')) }},
                                        message: {{ json_encode(old('message', '')) }}
                                    },
                                    errs: { name: null, email: null, message: null },
                                    touched: { name: false, email: false, message: false },

                                    check(field) {
                                        const v = (this.f[field] || '').trim();
                                        if (field === 'name') {
                                            if (!v)           return { en: 'Name is required.',            ar: 'الاسم مطلوب.' };
                                            if (v.length > 100) return { en: 'Name must be under 100 characters.', ar: 'الاسم يجب أن يكون أقل من 100 حرف.' };
                                        }
                                        if (field === 'email') {
                                            if (!v)           return { en: 'Email is required.',           ar: 'البريد الإلكتروني مطلوب.' };
                                            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v))
                                                              return { en: 'Please enter a valid email address.', ar: 'يرجى إدخال بريد إلكتروني صحيح.' };
                                        }
                                        if (field === 'message') {
                                            if (!v)           return { en: 'Message is required.',         ar: 'الرسالة مطلوبة.' };
                                            if (v.length < 10)  return { en: 'At least 10 characters required.', ar: 'يجب أن تكون الرسالة 10 أحرف على الأقل.' };
                                            if (v.length > 2000) return { en: 'Message is too long.',     ar: 'الرسالة طويلة جداً.' };
                                        }
                                        return null;
                                    },

                                    blur(field) {
                                        this.touched[field] = true;
                                        this.errs[field] = this.check(field);
                                    },

                                    submit(e) {
                                        ['name', 'email', 'message'].forEach(field => {
                                            this.touched[field] = true;
                                            this.errs[field] = this.check(field);
                                        });
                                        if (Object.values(this.errs).some(v => v !== null)) {
                                            e.preventDefault();
                                        }
                                    }
                                }"
                                @submit="submit($event)"
                                novalidate
                            >
                                @csrf

                                <div class="ctc-row">
                                    {{-- Name --}}
                                    <div class="ctc-field">
                                        <label class="ctc-label" for="name">
                                            <span x-show="!isAr">Name</span>
                                            <span x-show="isAr">الاسم</span>
                                        </label>
                                        <input
                                            id="name" name="name" type="text"
                                            x-model="f.name"
                                            @blur="blur('name')"
                                            :class="(errs.name || {{ $errors->has('name') ? 'true' : 'false' }}) ? 'ctc-input is-error' : 'ctc-input'"
                                            placeholder="John Smith"
                                            autocomplete="name"
                                        >
                                        <template x-if="errs.name">
                                            <p class="ctc-error">
                                                <span x-show="!isAr" x-text="errs.name.en"></span>
                                                <span x-show="isAr"  x-text="errs.name.ar"></span>
                                            </p>
                                        </template>
                                        @error('name')<p class="ctc-error">{{ $message }}</p>@enderror
                                    </div>

                                    {{-- Email --}}
                                    <div class="ctc-field">
                                        <label class="ctc-label" for="email">
                                            <span x-show="!isAr">Email</span>
                                            <span x-show="isAr">البريد الإلكتروني</span>
                                        </label>
                                        <input
                                            id="email" name="email" type="email"
                                            x-model="f.email"
                                            @blur="blur('email')"
                                            :class="(errs.email || {{ $errors->has('email') ? 'true' : 'false' }}) ? 'ctc-input is-error' : 'ctc-input'"
                                            placeholder="you@example.com"
                                            autocomplete="email"
                                        >
                                        <template x-if="errs.email">
                                            <p class="ctc-error">
                                                <span x-show="!isAr" x-text="errs.email.en"></span>
                                                <span x-show="isAr"  x-text="errs.email.ar"></span>
                                            </p>
                                        </template>
                                        @error('email')<p class="ctc-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                {{-- Message --}}
                                <div class="ctc-field">
                                    <label class="ctc-label" for="message">
                                        <span x-show="!isAr">Message</span>
                                        <span x-show="isAr">الرسالة</span>
                                    </label>
                                    <textarea
                                        id="message" name="message"
                                        x-model="f.message"
                                        @blur="blur('message')"
                                        :class="(errs.message || {{ $errors->has('message') ? 'true' : 'false' }}) ? 'ctc-textarea is-error' : 'ctc-textarea'"
                                        maxlength="2000"
                                        placeholder="{{ __('Tell us what\'s on your mind...') }}"
                                    ></textarea>
                                    <div class="ctc-field-foot">
                                        <template x-if="errs.message">
                                            <p class="ctc-error" style="margin:0">
                                                <span x-show="!isAr" x-text="errs.message.en"></span>
                                                <span x-show="isAr"  x-text="errs.message.ar"></span>
                                            </p>
                                        </template>
                                        @error('message')<p class="ctc-error" style="margin:0">{{ $message }}</p>@enderror
                                        <span
                                            class="ctc-char-count"
                                            :class="f.message.length > 2000 ? 'over' : ''"
                                            x-text="f.message.length + ' / 2000'"
                                        >0 / 2000</span>
                                    </div>
                                </div>

                                <button type="submit" class="ctc-btn">
                                    <span x-show="!isAr">Send message</span>
                                    <span x-show="isAr">إرسال الرسالة</span>
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2 7.5h11M9 3.5l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer>
        <div class="wrap">
            <div class="foot-grid">
                <div>
                    <a href="/" style="display:inline-block;margin-bottom:16px">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="Qayema" class="brand-logo-footer">
                    </a>
                    <p style="font-size:13.5px;line-height:1.6;max-width:28ch;margin:0" x-text="t.footer.tagline">The digital menu your restaurant deserves.</p>
                </div>
                <template x-for="(col, i) in t.footer.cols" :key="i">
                    <div>
                        <h4 x-text="col.title"></h4>
                        <ul>
                            <template x-for="(link, j) in col.links" :key="j">
                                <li>
                                    <a
                                        :href="link.disabled ? null : link.href"
                                        :target="link.target || null"
                                        :style="link.disabled ? 'opacity:0.35;cursor:not-allowed;pointer-events:none' : ''"
                                        x-text="link.label"
                                    ></a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
            <div class="foot-bottom">
                <span x-text="t.footer.copy">© 2025 Lebify Group. All rights reserved.</span>
                <span style="display:inline-flex;align-items:center;gap:5px">
                    <span x-text="t.footer.made">Made with care</span>
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="flex-shrink:0"><path d="M7 12.25S1.75 9.1 1.75 5.25a3.25 3.25 0 0 1 5.25-2.56A3.25 3.25 0 0 1 12.25 5.25C12.25 9.1 7 12.25 7 12.25Z" fill="#e74c3c"/></svg>
                </span>
            </div>
        </div>
    </footer>

</div>

<script>
@include('legal._script')
</script>

</body>
</html>
