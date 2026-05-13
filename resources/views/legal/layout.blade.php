<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | Qayema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&family=Noto+Kufi+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/welcome.css', 'resources/js/app.js'])
    <style>
        /* Legal page */
        .legal-hero {
            background: var(--ink-2);
            padding: 80px 0 60px;
            color: var(--paper);
        }
        .legal-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 20px;
        }
        .legal-hero-eyebrow .dot {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: var(--accent);
            display: inline-block;
        }
        .legal-hero h1 {
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 500;
            letter-spacing: -0.025em;
            line-height: 1.1;
            color: var(--paper);
            margin: 0 0 16px;
        }
        .legal-hero h1 em {
            font-family: var(--font-display);
            font-style: italic;
            font-weight: 400;
        }
        .legal-updated {
            font-size: 13px;
            color: rgba(246,241,232,.45);
            margin: 0;
        }
        .legal-body {
            background: var(--paper);
            padding: 64px 0 96px;
        }
        .legal-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 64px;
            align-items: start;
        }
        /* TOC sidebar */
        .legal-toc {
            position: sticky;
            top: 96px;
        }
        .legal-toc-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0 0 14px;
        }
        .legal-toc ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .legal-toc ul li a {
            display: block;
            font-size: 13px;
            color: var(--muted);
            padding: 5px 10px;
            border-radius: 6px;
            border-left: 2px solid transparent;
            transition: all .15s;
            line-height: 1.4;
        }
        .legal-toc ul li a:hover {
            color: var(--ink);
            background: rgba(15,15,16,.05);
            border-left-color: var(--accent);
        }
        /* Content */
        .legal-content {
            max-width: 680px;
        }
        .legal-content h2 {
            font-size: 22px;
            font-weight: 600;
            color: var(--ink);
            margin: 48px 0 14px;
            padding-top: 48px;
            border-top: 1px solid rgba(15,15,16,.08);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }
        .legal-content h2:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }
        .legal-content h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--ink);
            margin: 24px 0 8px;
        }
        .legal-content p {
            font-size: 14.5px;
            line-height: 1.75;
            color: rgba(15,15,16,.75);
            margin: 0 0 16px;
        }
        .legal-content ul, .legal-content ol {
            padding-left: 20px;
            margin: 0 0 16px;
        }
        .legal-content ul li, .legal-content ol li {
            font-size: 14.5px;
            line-height: 1.75;
            color: rgba(15,15,16,.75);
            margin-bottom: 6px;
        }
        .legal-content a {
            color: var(--accent-deep);
            text-decoration: underline;
            text-decoration-color: rgba(156,127,61,.3);
        }
        .legal-content a:hover { text-decoration-color: var(--accent-deep); }
        .legal-highlight {
            background: rgba(200,168,90,.08);
            border: 1px solid rgba(200,168,90,.2);
            border-radius: 10px;
            padding: 16px 20px;
            margin: 0 0 16px;
        }
        .legal-highlight p { margin: 0; color: rgba(15,15,16,.8); }
        /* Responsive */
        @media (max-width: 768px) {
            .legal-layout { grid-template-columns: 1fr; gap: 32px; }
            .legal-toc { position: static; }
            .legal-hero { padding: 56px 0 40px; }
        }
    </style>
</head>
<body>

<div id="page" x-data="QayemaApp()" x-init="document.documentElement.lang = lang; document.documentElement.dir = isAr ? 'rtl' : 'ltr';">

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

    {{-- Page hero --}}
    <div class="legal-hero">
        <div class="wrap">
            <div class="legal-hero-eyebrow"><span class="dot"></span>
                <span x-show="!isAr">@yield('eyebrow')</span>
                <span x-show="isAr">@yield('eyebrow_ar')</span>
            </div>
            <h1>
                <span x-show="!isAr">@yield('headline_plain') <span style="font-family:var(--font-display);font-style:italic;font-weight:400">@yield('headline_italic')</span></span>
                <span x-show="isAr">@yield('headline_ar')</span>
            </h1>
            <p class="legal-updated">
                <span x-show="!isAr">@yield('updated')</span>
                <span x-show="isAr">@yield('updated_ar')</span>
            </p>
        </div>
    </div>

    {{-- Content --}}
    <div class="legal-body">
        <div class="wrap">
            <div class="legal-layout">
                <aside class="legal-toc">
                    <div x-show="!isAr">
                        <p class="legal-toc-title">On this page</p>
                        <ul>@yield('toc')</ul>
                    </div>
                    <div x-show="isAr">
                        <p class="legal-toc-title">المحتويات</p>
                        <ul>@yield('toc_ar')</ul>
                    </div>
                </aside>
                <article class="legal-content">
                    @yield('content')
                </article>
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
