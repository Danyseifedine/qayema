<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Qayema, قايمة | Digital menus for restaurants</title>

    <x-seo
        title="Qayema, The digital menu your restaurant deserves"
        description="Qayema, قائمة, Arabic for 'menu'. One QR code, a beautifully designed digital menu, AI menu scanner, and a dashboard in 14 languages with full Arabic RTL. Free to start."
        keywords="digital menu, restaurant menu, QR menu, online menu, Arabic menu, RTL menu, Qayema, قايمة, Lebify"
        author="Lebify Group"
        :url="url('/')"
        :image="asset('images/logo/logo.png')"
        imageAlt="Qayema, Digital menus"
        type="website"
        :siteName="config('seo.organization.name', 'Lebify Group')"
    />

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&family=Noto+Kufi+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Welcome page CSS + Alpine.js --}}
    @vite(['resources/css/welcome.css', 'resources/js/app.js'])
</head>
<body>

<div id="page" x-data="QayemaApp()" x-init="
    document.documentElement.lang = lang;
    document.documentElement.dir  = isAr ? 'rtl' : 'ltr';
">

    {{-- ─────────────────────────────────────────────────────
         NAV
         ───────────────────────────────────────────────────── --}}
    <nav class="nav">
        <div class="nav-inner wrap">

            {{-- Brand --}}
            <a class="brand" href="/">
                <img src="{{ asset('images/logo/logo.png') }}" alt="Qayema" class="brand-logo">
            </a>

            {{-- Desktop links --}}
            <div class="nav-links">
                <a href="#features" @click="mobileOpen = false" x-text="t.nav.features">Features</a>
                <a href="#how"      @click="mobileOpen = false" x-text="t.nav.how">How it works</a>
                <a href="#stories"  @click="mobileOpen = false" x-text="t.nav.stories">Stories</a>
            </div>

            {{-- Right side --}}
            <div class="nav-end">
                {{-- Language toggle (desktop only) --}}
                <div class="lang-switch nav-lang">
                    <button :class="lang === 'en' ? 'on' : ''" @click="setLang('en')">EN</button>
                    <button :class="lang === 'ar' ? 'on' : ''" @click="setLang('ar')">عربي</button>
                </div>

                {{-- CTA (desktop only) --}}
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

                {{-- Hamburger --}}
                <button class="nav-burger" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-label="Menu">
                    <svg x-show="!mobileOpen" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M2 4.5h14M2 9h14M2 13.5h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <svg x-show="mobileOpen" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

        </div>

        {{-- Mobile panel --}}
        <div class="nav-mobile" :class="mobileOpen ? 'nav-mobile--open' : ''">
            <div class="wrap nav-mobile-inner">

                <div class="nav-mobile-links">
                    <a href="#features" @click="mobileOpen = false" x-text="t.nav.features">Features</a>
                    <a href="#how"      @click="mobileOpen = false" x-text="t.nav.how">How it works</a>
                    <a href="#stories"  @click="mobileOpen = false" x-text="t.nav.stories">Stories</a>
                </div>

                <div class="nav-mobile-foot">
                    <div class="lang-switch">
                        <button :class="lang === 'en' ? 'on' : ''" @click="setLang('en')">EN</button>
                        <button :class="lang === 'ar' ? 'on' : ''" @click="setLang('ar')">عربي</button>
                    </div>
                    @auth
                        <a class="btn btn-ink btn-sm nav-mobile-cta" href="{{ route('dashboard') }}" @click="mobileOpen = false">
                            <span x-text="t.nav.dashboard">Dashboard</span>
                            <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    @else
                        <a class="btn btn-ink btn-sm nav-mobile-cta" href="{{ route('login') }}" @click="mobileOpen = false">
                            <span x-text="t.nav.cta">Get started free</span>
                            <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </nav>

    {{-- ─────────────────────────────────────────────────────
         HERO
         ───────────────────────────────────────────────────── --}}
    <section class="hero">
        <div class="wrap">

            {{-- Headline --}}
            <h1 class="hero-headline" x-html="t.hero.headline">
                Menus, <em class="it">reimagined</em> <span class="soft">for your restaurant.</span>
            </h1>

            {{-- Subhead + CTA row --}}
            <div class="hero-foot">
                <div>
                    <div class="meta">
                        <span class="dash"></span>
                        <span x-text="t.hero.meta">The digital menu, refined</span>
                    </div>
                    <p class="hero-sub" x-text="t.hero.sub">
                        Qayema turns every table into a frictionless dining experience, a single QR code, a beautifully designed digital menu, and a dashboard your staff will love.
                    </p>
                </div>
                <div class="hero-actions">
                    @auth
                        <a class="btn btn-ink" href="{{ route('dashboard') }}">
                            <span x-text="t.hero.ctaAuth">Go to dashboard</span>
                            <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    @else
                        <a class="btn btn-ink" href="{{ route('login') }}">
                            <span x-text="t.hero.cta1">Start for free</span>
                            <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a class="btn btn-outline" href="#features">
                            <svg width="11" height="11" viewBox="0 0 11 11" fill="currentColor"><path d="M2 1.5v8L9 5.5z"/></svg>
                            <span x-text="t.hero.cta2">See how it works</span>
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Stats strip --}}
            <div class="hero-strip">
                <div>
                    <div class="k" x-text="t.hero.stats[0].k">No credit card</div>
                    <div class="v" x-text="t.hero.stats[0].v">Free</div>
                </div>
                <div>
                    <div class="k" x-text="t.hero.stats[1].k">Languages + RTL</div>
                    <div class="v">10</div>
                </div>
                <div>
                    <div class="k" x-text="t.hero.stats[2].k">Menu load time</div>
                    <div class="v"><span class="it">‹</span>800<span class="u">ms</span></div>
                </div>
                <div>
                    <div class="k" x-text="t.hero.stats[3].k">Menu updates</div>
                    <div class="v"><span class="it" style="font-size:.75em;vertical-align:baseline" x-text="t.hero.stats[3].v">Live</span></div>
                </div>
            </div>
        </div>

        {{-- Showcase: phone + QR card + annotations --}}
        <div class="hero-showcase">
            <div class="wrap">
                <div class="hero-stage">

                    {{-- Left annotation --}}
                    <div class="annot">
                        <span class="annot-pill">
                            <span class="pulse"></span>
                            <span x-text="t.hero.ann1">Synced with the kitchen</span>
                        </span>
                        <div class="annot-card">
                            <div class="lbl">
                                <span style="color:var(--accent)">●</span>
                                <span x-text="t.hero.ann1card.lbl">Live update</span>
                            </div>
                            <div class="body" x-text="t.hero.ann1card.body">
                                Mark a dish unavailable and it disappears across every table in 200ms.
                            </div>
                        </div>
                    </div>

                    {{-- Center: phone + QR card --}}
                    <div style="position:relative;display:flex;justify-content:center;align-items:flex-end">

                        {{-- QR table card --}}
                        <div class="table-card">
                            <div class="tc-top">
                                <div class="tc-logo" x-text="isAr ? 'ميزون' : 'Maison'">Maison</div>
                                <div class="tc-table" x-text="isAr ? 'طاولة ١٤' : '14'">14</div>
                            </div>
                            <div class="qr-wrap">
                                <svg viewBox="0 0 21 21" shape-rendering="crispEdges">
                                    <g>
                                        <rect x="0" y="0" width="7" height="7" fill="#E8DCCB"/>
                                        <rect x="1" y="1" width="5" height="5" fill="#0F0F10"/>
                                        <rect x="2" y="2" width="3" height="3" fill="#E8DCCB"/>
                                    </g>
                                    <g>
                                        <rect x="14" y="0" width="7" height="7" fill="#E8DCCB"/>
                                        <rect x="15" y="1" width="5" height="5" fill="#0F0F10"/>
                                        <rect x="16" y="2" width="3" height="3" fill="#E8DCCB"/>
                                    </g>
                                    <g>
                                        <rect x="0" y="14" width="7" height="7" fill="#E8DCCB"/>
                                        <rect x="1" y="15" width="5" height="5" fill="#0F0F10"/>
                                        <rect x="2" y="16" width="3" height="3" fill="#E8DCCB"/>
                                    </g>
                                    <rect x="9"  y="0"  width="2" height="4" fill="#E8DCCB"/>
                                    <rect x="9"  y="6"  width="2" height="2" fill="#E8DCCB"/>
                                    <rect x="8"  y="9"  width="4" height="2" fill="#E8DCCB"/>
                                    <rect x="14" y="9"  width="2" height="2" fill="#E8DCCB"/>
                                    <rect x="18" y="9"  width="3" height="2" fill="#E8DCCB"/>
                                    <rect x="9"  y="14" width="2" height="4" fill="#E8DCCB"/>
                                    <rect x="14" y="14" width="3" height="3" fill="#E8DCCB"/>
                                    <rect x="18" y="14" width="3" height="3" fill="#E8DCCB"/>
                                    <rect x="14" y="18" width="3" height="3" fill="#E8DCCB"/>
                                    <rect x="18" y="18" width="3" height="3" fill="#E8DCCB"/>
                                    <rect x="16" y="16" width="2" height="2" fill="#E8DCCB"/>
                                </svg>
                            </div>
                            <div class="tc-foot">
                                <b x-text="isAr ? 'امسح للقائمة' : 'SCAN TO ORDER'">SCAN TO ORDER</b>
                                <span>Qayema.lebify.dev</span>
                            </div>
                        </div>

                        {{-- Phone mockup --}}
                        <div class="phone">
                            <div class="phone-notch"></div>
                            <div class="phone-screen">

                                <div class="ps-status">
                                    <span x-text="isAr ? '٩:٤١' : '9:41'">9:41</span>
                                    <span class="ps-icons">
                                        <svg viewBox="0 0 16 12" fill="currentColor"><path d="M2 8L4 8L4 11L2 11ZM5 6L7 6L7 11L5 11ZM8 4L10 4L10 11L8 11ZM11 1L13 1L13 11L11 11Z"/></svg>
                                        <svg viewBox="0 0 16 16" fill="none"><path d="M2 7a8 8 0 0112 0M5 10a5 5 0 016 0M8 13a2 2 0 012 0" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                                        <svg viewBox="0 0 24 12" fill="none"><rect x="1" y="2" width="20" height="8" rx="2" stroke="currentColor" stroke-width="1"/><rect x="3" y="4" width="13" height="4" fill="currentColor"/><path d="M22 4V8" stroke="currentColor" stroke-linecap="round"/></svg>
                                    </span>
                                </div>

                                <div class="ps-header">
                                    <div>
                                        <div class="ps-rest" x-text="isAr ? 'ميزون أران' : 'Maison Aran'">Maison Aran</div>
                                        <div style="font-size:9px;color:var(--muted);margin-top:2px" x-text="isAr ? 'قائمة المساء' : 'Evening menu'">Evening menu</div>
                                    </div>
                                    <div class="ps-tbl" x-text="isAr ? 'طاولة ١٤' : 'Table 14'">Table 14</div>
                                </div>

                                <div class="ps-tabs">
                                    <span x-text="isAr ? 'المقبلات' : 'Mezze'">Mezze</span>
                                    <span class="on" x-text="isAr ? 'الرئيسية' : 'Mains'">Mains</span>
                                    <span x-text="isAr ? 'الحلويات' : 'Desserts'">Desserts</span>
                                    <span x-text="isAr ? 'مشروبات' : 'Drinks'">Drinks</span>
                                </div>

                                <div class="ps-list">
                                    <div class="ps-item">
                                        <div>
                                            <div class="name" x-text="isAr ? 'كباب الضأن المشوي' : 'Slow-Roast Lamb Shank'">Slow-Roast Lamb Shank</div>
                                            <div class="desc" x-text="isAr ? 'بهارات شامية، أرز بسمتي، طحينة' : 'Levantine spice, basmati, tahini jus'">Levantine spice, basmati, tahini jus</div>
                                        </div>
                                        <div class="price" x-text="isAr ? '٧٨ ر.س' : '78 SAR'">78 SAR</div>
                                    </div>
                                    <div class="ps-item with-img">
                                        <div class="thumb"></div>
                                        <div>
                                            <div class="name" x-text="isAr ? 'تبولة بقدونس بلدي' : 'Heritage Tabbouleh'">Heritage Tabbouleh</div>
                                            <div class="desc" x-text="isAr ? 'بقدونس مفروم، بلغار، سماق' : 'Hand-cut parsley, bulgur, sumac'">Hand-cut parsley, bulgur, sumac</div>
                                        </div>
                                        <div class="price" x-text="isAr ? '٣٢ ر.س' : '32 SAR'">32 SAR</div>
                                    </div>
                                    <div class="ps-item">
                                        <div>
                                            <div class="name" x-text="isAr ? 'حمص بيت قائمة' : 'House Hummus'">House Hummus</div>
                                            <div class="desc" x-text="isAr ? 'حمص مهروس، زيت زيتون، صنوبر' : 'Beit blend, smoked oil, pine'">Beit blend, smoked oil, pine</div>
                                        </div>
                                        <div class="price" x-text="isAr ? '٢٦ ر.س' : '26 SAR'">26 SAR</div>
                                    </div>
                                    <div class="ps-item with-img">
                                        <div class="thumb"></div>
                                        <div>
                                            <div class="name" x-text="isAr ? 'سمك سيد محشي' : 'Stuffed Sea Bass'">Stuffed Sea Bass</div>
                                            <div class="desc" x-text="isAr ? 'سمك متوسطي، أرز جوهري' : 'Mediterranean catch, jeweled rice'">Mediterranean catch, jeweled rice</div>
                                        </div>
                                        <div class="price" x-text="isAr ? '٩٤ ر.س' : '94 SAR'">94 SAR</div>
                                    </div>
                                </div>

                                <div class="ps-footbar">
                                    <div style="font-size:10px;color:var(--muted)" x-text="isAr ? '٤ أصناف' : '4 items'">4 items</div>
                                    <div class="ps-cart">
                                        <span class="cart-count">2</span>
                                        <span x-text="isAr ? 'عرض الطلب · ١١٠ ر.س' : 'View order · 110 SAR'">View order · 110 SAR</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Right annotation --}}
                    <div class="annot annot-right">
                        <span class="annot-pill">
                            <span x-text="t.hero.ann2">No app · zero friction</span>
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div class="annot-card">
                            <div class="lbl">
                                <span x-text="t.hero.ann2card.lbl">Guest experience</span>
                                &nbsp;·&nbsp;
                                <span style="color:var(--accent)">4.9</span>
                            </div>
                            <div class="body" x-text="t.hero.ann2card.body">
                                "It felt like the restaurant cared about us before we ordered."
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────
         LOGOS
         ───────────────────────────────────────────────────── --}}
    <div class="logos">
        <div class="wrap logos-inner">
            <p class="logos-label" x-text="t.logos.label">Used by restaurant owners across the region</p>
            <div class="logos-row">
                <template x-for="(item, i) in t.logos.items" :key="i">
                    <div class="logo-mark">
                        <span :class="i % 2 === 0 ? 'it' : ''" x-text="item"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────
         PROBLEM
         ───────────────────────────────────────────────────── --}}
    <section id="problem" class="section light-2">
        <div class="wrap">

            {{-- Section head --}}
            <div class="section-head">
                <div>
                    <div class="eyebrow"><span class="dot"></span> <span x-text="t.problem.eyebrow">The problem</span></div>
                    <h2 style="margin-top:18px" x-html="t.problem.headline">
                        Paper menus belong <em class="it">in the past.</em>
                    </h2>
                </div>
                <p class="right" x-text="t.problem.sub">
                    Every minute a guest waits for a menu, a refill, or the bill, your kitchen falls further behind and your reviews get a little colder.
                </p>
            </div>

            {{-- Cards --}}
            <div class="problem-grid">

                {{-- Card 1: The wait --}}
                <div class="problem-card">
                    <div class="strike-ill">
                        <svg viewBox="0 0 56 56" fill="none">
                            <path d="M14 6H42M14 50H42M14 6V14C14 22 28 24 28 28C28 32 14 34 14 42V50M42 6V14C42 22 28 24 28 28C28 32 42 34 42 42V50" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M22 14H34M22 42H34" stroke="currentColor" stroke-width="1.5"/>
                            <line x1="6" y1="50" x2="50" y2="6" stroke="#C8543A" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="problem-num" x-text="t.problem.cards[0].n">01</div>
                    <h3 x-text="t.problem.cards[0].h">The endless wait</h3>
                    <p style="margin-top:8px" x-text="t.problem.cards[0].p">Guests stare at a sticky laminated menu while servers run between tables. Orders arrive late, food arrives later.</p>
                </div>

                {{-- Card 2: The reprint cycle --}}
                <div class="problem-card">
                    <div class="strike-ill">
                        <svg viewBox="0 0 56 56" fill="none">
                            <rect x="14" y="6" width="28" height="44" rx="2" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M19 16H37M19 22H37M19 28H32M19 34H37M19 40H30" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                            <line x1="6" y1="50" x2="50" y2="6" stroke="#C8543A" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="problem-num" x-text="t.problem.cards[1].n">02</div>
                    <h3 x-text="t.problem.cards[1].h">The reprint cycle</h3>
                    <p style="margin-top:8px" x-text="t.problem.cards[1].p">Change a price, update a special, swap a dish, wait three days for the printer. Your menu is always slightly out of date.</p>
                </div>

                {{-- Card 3: No insight --}}
                <div class="problem-card">
                    <div class="strike-ill">
                        <svg viewBox="0 0 56 56" fill="none">
                            <path d="M10 10H44V46L40 42L36 46L32 42L28 46L24 42L20 46L16 42L12 46L10 44Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M16 18H38M16 24H38M16 30H30" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                            <line x1="6" y1="50" x2="50" y2="6" stroke="#C8543A" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="problem-num" x-text="t.problem.cards[2].n">03</div>
                    <h3 x-text="t.problem.cards[2].h">No insight at all</h3>
                    <p style="margin-top:8px" x-text="t.problem.cards[2].p">Paper menus don't tell you which dishes get looked at, how long people browse, or what they skip. You're running blind.</p>
                </div>

            </div>
        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────
         SOLUTION
         ───────────────────────────────────────────────────── --}}
    <section id="solution" class="section light">
        <div class="wrap">

            {{-- Section head --}}
            <div class="section-head">
                <div>
                    <div class="eyebrow"><span class="dot"></span> <span x-text="t.solution.eyebrow">The solution</span></div>
                    <h2 style="margin-top:18px" x-html="t.solution.headline">
                        One QR. One menu. <em class="it">Everything in sync.</em>
                    </h2>
                </div>
                <p class="right" x-text="t.solution.sub">
                    Qayema is the layer between your kitchen and your guest's phone. No app downloads. No clunky tablets. Just a beautiful, always-current menu.
                </p>
            </div>

            {{-- Feature rows --}}
            <div class="solution-stack">

                {{-- Row 1: Real-time (text left, dark vis right) --}}
                <div class="feat-row">
                    <div class="feat-text">
                        <div class="eyebrow"><span class="dot"></span> <span x-text="t.solution.rows[0].tag">Real-time</span></div>
                        <h3 x-text="t.solution.rows[0].h">A menu that updates the moment your kitchen does.</h3>
                        <p x-text="t.solution.rows[0].p">Mark a dish 86'd from the dashboard and it disappears across every table instantly. Add a special? It's live in seconds.</p>
                        <ul class="feat-bullets">
                            <template x-for="b in t.solution.rows[0].bullets" :key="b">
                                <li>
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7.5l3 3 5-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span x-text="b"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    {{-- Vis 1: Live menu panel --}}
                    <div class="feat-vis dark-vis" style="padding:40px">
                        <div style="background:rgba(255,255,255,.03);border:.5px solid rgba(255,255,255,.08);border-radius:12px;padding:18px;color:var(--paper);max-width:360px;margin:40px auto">
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:rgba(246,241,232,.5)">
                                <span>● Live</span><span x-text="isAr ? 'الرئيسية' : 'Mains'">Mains</span>
                            </div>
                            <div style="margin-top:16px;display:flex;flex-direction:column;gap:14px">
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;opacity:.32">
                                    <span style="text-decoration:line-through" x-text="isAr ? 'كباب الضأن المشوي' : 'Slow-Roast Lamb Shank'">Slow-Roast Lamb Shank</span>
                                    <span style="color:var(--accent-soft)">78 SAR</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px">
                                    <span x-text="isAr ? 'سمك سيد محشي' : 'Wood-Fire Sea Bass'">Wood-Fire Sea Bass</span>
                                    <span style="color:var(--accent-soft)">94 SAR</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px">
                                    <span style="display:flex;align-items:center;gap:8px">
                                        <span style="background:var(--accent);color:var(--ink);font-size:9px;padding:2px 7px;border-radius:999px;letter-spacing:.08em;text-transform:uppercase;font-weight:600" x-text="isAr ? 'جديد' : 'New'">New</span>
                                        <span x-text="isAr ? 'منسف الكمأة' : 'Truffle Mansaf'">Truffle Mansaf</span>
                                    </span>
                                    <span style="color:var(--accent-soft)">120 SAR</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px">
                                    <span x-text="isAr ? 'ريبآي معتّق 280 غ' : 'Aged Ribeye, 280g'">Aged Ribeye, 280g</span>
                                    <span style="color:var(--accent-soft)">165 SAR</span>
                                </div>
                            </div>
                            <div style="margin-top:24px;padding-top:16px;border-top:.5px solid rgba(255,255,255,.08);font-size:11px;color:rgba(246,241,232,.45)" x-text="isAr ? 'مزامنة منذ ثانيتين · 18 طاولة' : 'Synced 2s ago · across 18 tables'">
                                Synced 2s ago · across 18 tables
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Multilingual (text right, light vis left) --}}
                <div class="feat-row reverse">
                    <div class="feat-text">
                        <div class="eyebrow"><span class="dot"></span> <span x-text="t.solution.rows[1].tag">Multilingual</span></div>
                        <h3 x-text="t.solution.rows[1].h">Designed for guests in their own language.</h3>
                        <p x-text="t.solution.rows[1].p">10 language presets with native Arabic RTL support. Your menu looks and reads perfectly whether the guest is local or visiting from abroad.</p>
                        <ul class="feat-bullets">
                            <template x-for="b in t.solution.rows[1].bullets" :key="b">
                                <li>
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7.5l3 3 5-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span x-text="b"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    {{-- Vis 2: Two language cards --}}
                    <div class="feat-vis" style="padding:32px;position:relative">
                        <div style="position:absolute;inset:0;background:linear-gradient(160deg,var(--sand) 0%,var(--paper-2) 100%)"></div>
                        <div style="position:relative;height:100%;display:flex;align-items:center;justify-content:center;gap:18px">
                            {{-- EN card --}}
                            <div style="width:190px;background:var(--paper);border-radius:14px;padding:18px;box-shadow:0 20px 40px -20px rgba(0,0,0,.18);transform:rotate(-4deg) translateY(-10px);border:.5px solid var(--line);direction:ltr;font-family:var(--font-sans)">
                                <div style="font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:12px">EN</div>
                                <div style="width:100%;aspect-ratio:4/3;border-radius:8px;background:linear-gradient(135deg,#6B5B3F,#3D3324);margin-bottom:14px"></div>
                                <div style="font-size:13px;font-weight:500;line-height:1.25;margin-bottom:4px">Slow-Roast Lamb Shank</div>
                                <div style="font-size:10px;color:var(--muted);line-height:1.4;margin-bottom:12px">Levantine spice, tahini</div>
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px">
                                    <span style="color:var(--accent);font-weight:500">78 SAR</span>
                                    <span style="width:20px;height:20px;border-radius:50%;background:var(--ink);color:var(--paper);display:grid;place-items:center;font-size:13px">+</span>
                                </div>
                            </div>
                            {{-- AR card --}}
                            <div style="width:190px;background:var(--paper);border-radius:14px;padding:18px;box-shadow:0 20px 40px -20px rgba(0,0,0,.18);transform:rotate(4deg) translateY(14px);border:.5px solid var(--line);direction:rtl;font-family:var(--font-ar)">
                                <div style="font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:12px">عربي</div>
                                <div style="width:100%;aspect-ratio:4/3;border-radius:8px;background:linear-gradient(135deg,#6B5B3F,#3D3324);margin-bottom:14px"></div>
                                <div style="font-size:13px;font-weight:500;line-height:1.25;margin-bottom:4px">كباب الضأن المشوي</div>
                                <div style="font-size:10px;color:var(--muted);line-height:1.4;margin-bottom:12px">بهارات شامية، طحينة</div>
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px">
                                    <span style="color:var(--accent);font-weight:500">٧٨ ر.س</span>
                                    <span style="width:20px;height:20px;border-radius:50%;background:var(--ink);color:var(--paper);display:grid;place-items:center;font-size:13px">+</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 3: Dashboard (text left, light vis right) --}}
                <div class="feat-row">
                    <div class="feat-text">
                        <div class="eyebrow"><span class="dot"></span> <span x-text="t.solution.rows[2].tag">Dashboard</span></div>
                        <h3 x-text="t.solution.rows[2].h">A back-of-house tool that doesn't fight you back.</h3>
                        <p x-text="t.solution.rows[2].p">Upload dishes. Set categories. Customize layouts, fonts, and colors. Track visitor stats. No training required.</p>
                        <ul class="feat-bullets">
                            <template x-for="b in t.solution.rows[2].bullets" :key="b">
                                <li>
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7.5l3 3 5-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span x-text="b"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    {{-- Vis 3: Dashboard mockup --}}
                    <div class="feat-vis" style="background:linear-gradient(160deg,var(--paper-2),var(--sand));padding:24px">
                        <div style="background:var(--paper);border-radius:12px;height:100%;box-shadow:0 20px 40px -22px rgba(0,0,0,.2);border:.5px solid var(--line);overflow:hidden;display:flex;flex-direction:column">
                            {{-- Title bar --}}
                            <div style="padding:12px 18px;border-bottom:.5px solid var(--line);display:flex;align-items:center;justify-content:space-between">
                                <div style="display:flex;gap:6px">
                                    <span style="width:9px;height:9px;border-radius:50%;background:#E2796B"></span>
                                    <span style="width:9px;height:9px;border-radius:50%;background:#E8C25C"></span>
                                    <span style="width:9px;height:9px;border-radius:50%;background:var(--accent-soft)"></span>
                                </div>
                                <span style="color:var(--muted);font-size:11px;font-family:var(--font-display);font-style:italic">Qayema · dashboard</span>
                                <span></span>
                            </div>
                            {{-- Body --}}
                            <div style="display:grid;grid-template-columns:120px 1fr;flex:1">
                                {{-- Sidebar --}}
                                <div style="border-right:.5px solid var(--line);padding:16px;font-size:11px;color:var(--muted);display:flex;flex-direction:column;gap:12px">
                                    <span>Overview</span>
                                    <span style="color:var(--ink);font-weight:500"><span style="color:var(--accent);margin-right:6px">●</span>Menu</span>
                                    <span>Dishes</span>
                                    <span>Analytics</span>
                                    <span>QR Code</span>
                                    <span>Settings</span>
                                </div>
                                {{-- Content --}}
                                <div style="padding:18px;display:flex;flex-direction:column;gap:10px">
                                    <div style="font-size:13px;font-weight:500;margin-bottom:4px" x-text="isAr ? 'الرئيسية' : 'Mains'">Mains</div>
                                    <div style="display:grid;grid-template-columns:1fr 60px 40px;align-items:center;padding:8px 12px;background:var(--paper-2);border-radius:6px;font-size:11.5px">
                                        <span x-text="isAr ? 'كباب الضأن' : 'Slow-Roast Lamb'">Slow-Roast Lamb</span>
                                        <span style="display:inline-flex;align-items:center;gap:4px;color:var(--accent)"><span style="width:6px;height:6px;border-radius:50%;background:var(--accent)"></span><span x-text="isAr ? 'نشط' : 'Active'">Active</span></span>
                                        <span style="text-align:right;color:var(--ink)">78</span>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 60px 40px;align-items:center;padding:8px 12px;background:var(--paper-2);border-radius:6px;font-size:11.5px">
                                        <span x-text="isAr ? 'سمك سيد' : 'Wood-Fire Bass'">Wood-Fire Bass</span>
                                        <span style="display:inline-flex;align-items:center;gap:4px;color:var(--accent)"><span style="width:6px;height:6px;border-radius:50%;background:var(--accent)"></span><span x-text="isAr ? 'نشط' : 'Active'">Active</span></span>
                                        <span style="text-align:right;color:var(--ink)">94</span>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 60px 40px;align-items:center;padding:8px 12px;background:var(--paper-2);border-radius:6px;font-size:11.5px">
                                        <span x-text="isAr ? 'منسف الكمأة' : 'Truffle Mansaf'">Truffle Mansaf</span>
                                        <span style="display:inline-flex;align-items:center;gap:4px;color:var(--muted)"><span style="width:6px;height:6px;border-radius:50%;background:var(--muted)"></span><span x-text="isAr ? 'مسودة' : 'Draft'">Draft</span></span>
                                        <span style="text-align:right;color:var(--ink)">120</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────
         FEATURES GRID
         ───────────────────────────────────────────────────── --}}
    <section id="features" class="section light-2">
        <div class="wrap">

            <div class="section-head">
                <div>
                    <div class="eyebrow"><span class="dot"></span> <span x-text="t.features.eyebrow">Everything you need</span></div>
                    <h2 style="margin-top:18px" x-html="t.features.headline">
                        A complete restaurant <em class="it">menu platform.</em>
                    </h2>
                </div>
                <p class="right" x-text="t.features.sub">
                    Every feature your restaurant needs, from the QR code on the table to the analytics in the dashboard.
                </p>
            </div>

            <div class="features-grid">
                <template x-for="(f, i) in t.features.items" :key="i">
                    <div class="feature-cell">
                        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" x-html="f.icon"></svg>
                        <h3 x-text="f.h"></h3>
                        <p x-text="f.p"></p>
                        <div class="meta" x-text="f.meta"></div>
                    </div>
                </template>
            </div>

        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────
         HOW IT WORKS
         ───────────────────────────────────────────────────── --}}
    <section id="how" class="section dark">
        <div class="wrap">

            <div class="section-head">
                <div>
                    <div class="eyebrow"><span class="dot"></span> <span x-text="t.how.eyebrow">How it works</span></div>
                    <h2 style="margin-top:18px" x-html="t.how.headline">
                        Up and running <em class="it">in under 10 minutes.</em>
                    </h2>
                </div>
                <p class="right" x-text="t.how.sub">
                    No developer needed. No onboarding call. Sign up, add your dishes, and your guests are scanning within the hour.
                </p>
            </div>

            <div class="steps">
                <template x-for="(step, i) in t.how.steps" :key="i">
                    <div class="step">
                        <div class="step-num" x-text="step.n">01</div>
                        <h3 x-text="step.h"></h3>
                        <p x-text="step.p"></p>
                        <div class="step-vis" x-html="step.vis"></div>
                    </div>
                </template>
            </div>

        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────
         TESTIMONIALS
         ───────────────────────────────────────────────────── --}}
    <section id="stories" class="section light">
        <div class="wrap">

            <div class="section-head">
                <div>
                    <div class="eyebrow"><span class="dot"></span> <span x-text="t.testimonials.eyebrow">Stories</span></div>
                    <h2 style="margin-top:18px" x-html="t.testimonials.headline">
                        Restaurateurs <em class="it">speak for themselves.</em>
                    </h2>
                </div>
                <p class="right" x-text="t.testimonials.sub">
                    From fast-casual to fine dining, Qayema fits the way your restaurant actually runs.
                </p>
            </div>

            <div class="quote-grid">
                {{-- Feature quote --}}
                <div class="quote-card feature">
                    <div>
                        <div class="quote-mark">"</div>
                        <p class="quote-text" x-text="t.testimonials.quotes[0].q"></p>
                    </div>
                    <div class="quote-meta">
                        <div class="quote-avatar"></div>
                        <div>
                            <div class="who" x-text="t.testimonials.quotes[0].who"></div>
                            <div class="role" x-text="t.testimonials.quotes[0].role"></div>
                        </div>
                    </div>
                </div>

                {{-- Regular quotes --}}
                <template x-for="(q, i) in t.testimonials.quotes.slice(1)" :key="i">
                    <div class="quote-card">
                        <div>
                            <div class="quote-mark">"</div>
                            <p class="quote-text" x-text="q.q"></p>
                        </div>
                        <div class="quote-meta">
                            <div class="quote-avatar"></div>
                            <div>
                                <div class="who" x-text="q.who"></div>
                                <div class="role" x-text="q.role"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────
         CTA
         ───────────────────────────────────────────────────── --}}
    <section class="cta-section">
        <div class="wrap">
            <div class="eyebrow" style="justify-content:center;margin-bottom:28px"><span class="dot" style="background:var(--accent-soft)"></span> <span style="color:var(--accent-soft)" x-text="t.cta.eyebrow">Free to start</span></div>
            <h2 x-html="t.cta.headline">Ready to modernise <em class="it">your menu?</em></h2>
            <p x-text="t.cta.sub">Join restaurant owners already using Qayema. No credit card needed.</p>
            <div class="cta-actions">
                @auth
                    <a class="btn btn-primary" href="{{ route('dashboard') }}">
                        <span x-text="t.nav.dashboard">Dashboard</span>
                        <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @else
                    <a class="btn btn-primary" href="{{ route('login') }}">
                        <span x-text="t.cta.cta1">Start for free</span>
                        <svg class="arr" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a class="btn btn-ghost" href="#how" x-text="t.cta.cta2">See how it works</a>
                @endauth
            </div>
            <p class="cta-fineprint" x-text="t.cta.fine">Free forever on the starter plan. Upgrade when you're ready.</p>
        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────
         FOOTER
         ───────────────────────────────────────────────────── --}}
    <footer>
        <div class="wrap">
            <div class="foot-grid">

                {{-- Brand column --}}
                <div>
                    <a href="/" style="display:inline-block;margin-bottom:16px">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="Qayema" class="brand-logo-footer">
                    </a>
                    <p style="font-size:13.5px;line-height:1.6;max-width:28ch;margin:0" x-text="t.footer.tagline">
                        The digital menu your restaurant deserves.
                    </p>
                </div>

                {{-- Link columns --}}
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

</div>{{-- end #page --}}

{{-- ─────────────────────────────────────────────────────
     Alpine.js data
     ───────────────────────────────────────────────────── --}}
<script>
@include('partials.qayema-app')
</script>

</body>
</html>
