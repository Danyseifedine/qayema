function QayemaApp() {
    const copy = {
        en: {
            nav: {
                features:  'Features',
                how:       'How it works',
                stories:   'Stories',
                dashboard: 'Dashboard',
                signin:    'Sign in',
                cta:       'Get started free',
            },
            hero: {
                headline:  'Menus, <em class="it">reimagined</em> <span class="soft">for your restaurant.</span>',
                meta:      'Qayema · قائمة · Arabic for "menu"',
                sub:       'Qayema turns every table into a frictionless dining experience, a single QR code, a beautifully designed digital menu, and a dashboard your staff will love.',
                cta1:      'Start for free',
                cta2:      'See how it works',
                ctaAuth:   'Go to dashboard',
                stats: [
                    { k: 'No credit card',  v: 'Free' },
                    { k: 'Dashboard languages', v: '14'   },
                    { k: 'Menu load time',  v: ''     },
                    { k: 'Menu updates',    v: 'Live' },
                ],
                ann1:     'Synced with the kitchen',
                ann1card: { lbl: 'Live update',      body: 'Mark a dish unavailable and it disappears across every table in 200ms.' },
                ann2:     'No app · zero friction',
                ann2card: { lbl: 'Guest experience', body: '"It felt like the restaurant cared about us before we ordered."' },
            },
            logos: {
                label: 'Used by restaurant owners from Beirut to Dubai',
                items: ['Maison Aran', 'Olea & Co.', 'Sabor', 'Fenwick', 'Casa Lume', 'North Quarter'],
            },
            problem: {
                eyebrow:  'The problem',
                headline: 'Paper menus belong <em class="it">in the past.</em>',
                sub:      'Every minute a guest waits for a menu, a refill, or the bill, your kitchen falls further behind and your reviews get a little colder.',
                cards: [
                    { n: '01', h: 'The endless wait',    p: 'Guests stare at a sticky laminated menu while servers run between tables. Orders arrive late, food arrives later.' },
                    { n: '02', h: 'The reprint cycle',   p: 'Change a price, update a special, swap a dish, wait three days for the printer. Your menu is always slightly out of date.' },
                    { n: '03', h: 'No insight at all',   p: "Paper menus don't tell you which dishes get looked at, how long people browse, or what they skip. You're running blind." },
                ],
            },
            solution: {
                eyebrow: 'The solution',
                headline: 'One QR. One menu. <em class="it">Everything in sync.</em>',
                sub:     "Qayema is the layer between your kitchen and your guest's phone. No app downloads. No clunky tablets. Just a beautiful, always-current menu.",
                rows: [
                    {
                        tag:     'Real-time',
                        h:       'A menu that updates the moment your kitchen does.',
                        p:       "Mark a dish 86'd from the dashboard and it disappears across every table instantly. Add a special? It's live in seconds.",
                        bullets: ['Zero-latency updates across all devices', 'Mark dishes unavailable without reprinting', 'Add specials from your phone in 30 seconds'],
                    },
                    {
                        tag:     'Multilingual',
                        h:       'Manage your restaurant in your own language.',
                        p:       'The dashboard speaks 14 languages including full Arabic RTL support. Switch the interface to your native language and manage everything without friction.',
                        bullets: ['14 dashboard languages including Arabic RTL', 'Switch your dashboard language in one click', 'Full RTL layout for Arabic & right-to-left scripts'],
                    },
                    {
                        tag:     'Dashboard',
                        h:       "A back-of-house tool that doesn't fight you back.",
                        p:       'Upload dishes. Set categories. Customize layouts, fonts, and colors. Track visitor stats. No training required.',
                        bullets: ['AI scans your paper menu, import in seconds', 'Real-time visitor analytics with period filters', 'WhatsApp ordering built in'],
                    },
                ],
            },
            features: {
                eyebrow: 'Everything you need',
                headline: 'A complete restaurant <em class="it">menu platform.</em>',
                sub:     'Every feature your restaurant needs, from the QR code on the table to the analytics in the dashboard.',
                items: [
                    { icon: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h.01M17 14h.01M14 17h.01M17 17h.01M20 14h.01M20 17h.01M14 20h.01M17 20h.01M20 20h.01"/>',  h: 'QR Code',           p: 'Your table card is ready the moment your menu goes live. Download, print, laminate.',                        meta: 'Printable PDF'     },
                    { icon: '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>',                                                                                                                                                                                            h: 'Real-time sync',    p: 'Mark a dish unavailable and it disappears everywhere in under 200ms. No delay, no lag.',                    meta: 'Live updates'      },
                    { icon: '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',                                                                                       h: '14 Dashboard Languages', p: 'Run your dashboard in Arabic, English, French, Turkish, Japanese and 9 more. Full RTL support for Arabic.', meta: 'Incl. RTL'     },
                    { icon: '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/><path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',                                                                          h: 'AI Menu Scanner',   p: 'Photograph your paper menu. AI reads it, extracts every dish, price, and category, and imports them in seconds.', meta: 'AI-powered'        },
                    { icon: '<path d="M18 20V10M12 20V4M6 20v-6"/>',                                                                                                                                                                                                   h: 'Analytics',         p: 'See scan count, time on menu, peak hours, and which dishes attract the most attention.',                    meta: 'Visitor data'      },
                    { icon: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',                                                                                                                                                                          h: 'Category control',  p: 'Create sections, reorder dishes, hide entire categories in one click. Your menu, your rules.',              meta: 'Full control'      },
                    { icon: '<circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>',                                                                        h: 'Custom branding',   p: 'Your colors, your logo. Qayema stays in the background, your restaurant identity comes through.',           meta: 'Your identity'     },
                    { icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',                                                                                                                                                                                h: 'No app required',   p: 'Guests open a browser link, no install, no account, no friction. Works on every smartphone.',              meta: 'Zero friction'     },
                ],
            },
            how: {
                eyebrow: 'How it works',
                headline: 'Up and running <em class="it">in under 10 minutes.</em>',
                sub:     'No developer needed. No onboarding call. Sign up, add your dishes, and your guests are scanning within the hour.',
                steps: [
                    {
                        n:   '01',
                        h:   'Create your menu',
                        p:   'Sign up, add your dishes with names, prices, and photos. Organise them into categories. Takes about 10 minutes.',
                        vis: '<div style="padding:28px;width:100%;text-align:center"><div style="display:inline-flex;flex-direction:column;gap:10px;text-align:left;width:220px"><div style="font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:rgba(246,241,232,.4);margin-bottom:4px">New dish</div><div style="background:rgba(255,255,255,.04);border:.5px solid rgba(255,255,255,.08);border-radius:8px;padding:14px 16px;display:flex;flex-direction:column;gap:8px"><div style="height:8px;background:rgba(255,255,255,.12);border-radius:4px;width:70%"></div><div style="height:8px;background:rgba(255,255,255,.07);border-radius:4px;width:45%"></div></div><div style="background:var(--accent);border-radius:6px;padding:9px 14px;font-size:12px;font-weight:500;color:var(--ink);text-align:center">Save dish</div></div></div>',
                    },
                    {
                        n:   '02',
                        h:   'Print the QR card',
                        p:   'We generate a print-ready table card in seconds. Download the PDF, laminate it, place it on the table.',
                        vis: '<div style="padding:28px;display:flex;justify-content:center;align-items:center"><div style="width:90px;background:rgba(255,255,255,.04);border:.5px solid rgba(255,255,255,.1);border-radius:10px;padding:10px;display:flex;flex-direction:column;align-items:center;gap:8px"><div style="font-size:8px;font-style:italic;color:rgba(246,241,232,.5)">Maison</div><div style="width:60px;height:60px;background:rgba(255,255,255,.08);border-radius:4px;display:grid;place-items:center"><svg viewBox="0 0 20 20" width="44" height="44"><rect x="0" y="0" width="7" height="7" fill="rgba(246,241,232,.6)"/><rect x="1" y="1" width="5" height="5" fill="rgba(15,15,16,.8)"/><rect x="2" y="2" width="3" height="3" fill="rgba(246,241,232,.6)"/><rect x="13" y="0" width="7" height="7" fill="rgba(246,241,232,.6)"/><rect x="14" y="1" width="5" height="5" fill="rgba(15,15,16,.8)"/><rect x="15" y="2" width="3" height="3" fill="rgba(246,241,232,.6)"/><rect x="0" y="13" width="7" height="7" fill="rgba(246,241,232,.6)"/><rect x="1" y="14" width="5" height="5" fill="rgba(15,15,16,.8)"/><rect x="2" y="15" width="3" height="3" fill="rgba(246,241,232,.6)"/></svg></div><div style="font-size:7px;letter-spacing:.1em;text-transform:uppercase;color:rgba(246,241,232,.35)">Scan to order</div></div></div>',
                    },
                    {
                        n:   '03',
                        h:   'Guests just scan',
                        p:   'They browse at their own pace in their own language. You manage updates from anywhere. The kitchen stays in sync.',
                        vis: '<div style="padding:28px;display:flex;justify-content:center;align-items:center;gap:16px"><div style="width:7px;height:7px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 6px rgba(200,168,90,.18)"></div><div style="font-size:13px;color:rgba(246,241,232,.7)">Synced in real time</div></div>',
                    },
                ],
            },
            testimonials: {
                eyebrow: 'Stories',
                headline: 'Restaurateurs <em class="it">speak for themselves.</em>',
                sub:     'From fast-casual to fine dining, Qayema fits the way your restaurant actually runs.',
                quotes: [
                    {
                        q:    'We switched from a laminated paper menu to Qayema in one afternoon. Our guests noticed immediately, and our servers stopped getting asked "what is this dish?" every five minutes.',
                        who:  'Khalid A.',
                        role: 'Owner, Maison Aran · Riyadh',
                    },
                    {
                        q:    'The Arabic support is genuinely perfect. My Lebanese guests and my French guests both get a menu that feels native to them.',
                        who:  'Nadia R.',
                        role: 'Manager, Beit Beirut',
                    },
                    {
                        q:    'I update the daily specials from my phone every morning. No more calling the printer, no more crossing things out with a pen.',
                        who:  'Tariq M.',
                        role: 'Chef-owner, Fenwick Grill',
                    },
                ],
            },
            cta: {
                eyebrow: 'Free to start',
                headline: 'Ready to modernise <em class="it">your menu?</em>',
                sub:     'Join restaurant owners already using Qayema. No credit card needed.',
                cta1:    'Start for free',
                cta2:    'See how it works',
                fine:    'Free forever on the starter plan. Upgrade when you need more.',
            },
            footer: {
                tagline: 'The digital menu your restaurant deserves.',
                cols: [
                    {
                        title: 'Product',
                        links: [
                            { label: 'Features',     href: '#features' },
                            { label: 'How it works', href: '#how'      },
                            { label: 'Stories',      href: '#stories'  },
                            { label: 'Dashboard',    href: '/dashboard' },
                        ],
                    },
                    {
                        title: 'Company',
                        links: [
                            { label: 'About Lebify', href: 'https://www.lebify.dev/', target: '_blank' },
                            { label: 'Blog',         href: '#', disabled: true },
                            { label: 'Careers',      href: '#', disabled: true },
                            { label: 'Contact',      href: '{{ route('contact') }}' },
                        ],
                    },
                    {
                        title: 'Legal',
                        links: [
                            { label: 'Privacy policy', href: '#' },
                            { label: 'Terms of use',   href: '#' },
                            { label: 'Cookie policy',  href: '#' },
                        ],
                    },
                ],
                copy:  '© 2025 Lebify Group. All rights reserved.',
                made:  'Made with care',
            },
        },
        ar: {
            nav: {
                features:  'المميزات',
                how:       'كيف تعمل',
                stories:   'قصص نجاح',
                dashboard: 'لوحة التحكم',
                signin:    'تسجيل الدخول',
                cta:       'ابدأ مجاناً',
            },
            hero: {
                headline:  'قوائمٌ <em class="it">أُعيد تصوّرها</em> <span class="soft">للمطعم العصري.</span>',
                meta:      'Qayema · قائمة · اسمنا من كلمة "القائمة"',
                sub:       'Qayema تحوّل كل طاولة إلى تجربة طلب سلسة, رمز QR واحد، وقائمة رقمية مصمّمة بأناقة، ولوحة تحكّم سيشكرك عليها فريقك فعلاً.',
                cta1:      'ابدأ مجاناً',
                cta2:      'اكتشف كيف تعمل',
                ctaAuth:   'لوحة التحكم',
                stats: [
                    { k: 'بدون بطاقة ائتمان', v: 'مجاني' },
                    { k: 'لغة للوحة التحكم',   v: '14'    },
                    { k: 'زمن تحميل القائمة',  v: ''      },
                    { k: 'تحديثات القائمة',    v: 'مباشر' },
                ],
                ann1:     'متزامن مع المطبخ',
                ann1card: { lbl: 'تحديث مباشر',   body: 'علّم طبقاً كمنتهٍ فيختفي من جميع الطاولات خلال 200 مللي ثانية.' },
                ann2:     'بدون تطبيق · بدون احتكاك',
                ann2card: { lbl: 'تجربة الضيف',   body: '"شعرنا أن المطعم اعتنى بنا قبل أن نطلب."' },
            },
            logos: {
                label: 'موثوق به من أصحاب مطاعم من بيروت إلى دبي',
                items: ['ميزون أران', 'أوليا', 'سابور', 'فينويك', 'كاسا لومي', 'نورث كوارتر'],
            },
            problem: {
                eyebrow:  'المشكلة',
                headline: 'القوائم الورقية <em class="it">من الماضي.</em>',
                sub:      'كل دقيقة ينتظرها الضيف لقائمته أو فاتورته، يتأخر فيها مطبخك أكثر، وتبرد فيها تقييماتك قليلاً.',
                cards: [
                    { n: '01', h: 'الانتظار الطويل',      p: 'الضيوف يحدّقون في قائمة لاصقة بينما يركض النادل بين الطاولات. الطلبات تتأخر، والطعام يتأخر أكثر.' },
                    { n: '02', h: 'دورة إعادة الطباعة',    p: 'تغيّر سعراً، تحدّث طبقاً مميزاً، تستبدل صنفاً, انتظر ثلاثة أيام للمطبعة. قائمتك دائماً غير محدّثة.' },
                    { n: '03', h: 'لا رؤية ولا بيانات',    p: 'القوائم الورقية لا تخبرك أي الأطباق يُنظر إليها، ولا كم يتصفّح الضيف، ولا ما يتجاهله. أنت تعمل في الظلام.' },
                ],
            },
            solution: {
                eyebrow: 'الحل',
                headline: 'رمز QR واحد. قائمة واحدة. <em class="it">كل شيء متزامن.</em>',
                sub:     'Qayema هو الطبقة الواصلة بين مطبخك وهاتف ضيفك. لا تنزيل تطبيقات. لا أجهزة لوحية معقّدة. فقط قائمة جميلة ومحدّثة دائماً.',
                rows: [
                    {
                        tag:     'فوري',
                        h:       'قائمة تتحدّث لحظة تحدّث مطبخك.',
                        p:       'علّم طبقاً كمنتهٍ من لوحة التحكم ويختفي فوراً من كل الطاولات. أضف عرضاً خاصاً؟ سيظهر في ثوانٍ.',
                        bullets: ['تحديثات فورية عبر جميع الأجهزة', 'إيقاف الأطباق دون إعادة طباعة', 'أضف العروض من هاتفك في 30 ثانية'],
                    },
                    {
                        tag:     'متعدد اللغات',
                        h:       'أدِر مطعمك بلغتك الأصلية.',
                        p:       'لوحة التحكم تدعم 14 لغة بما فيها العربية الكاملة من اليمين لليسار. غيّر لغة الواجهة ببساطة وأدِر كل شيء بلا أي احتكاك.',
                        bullets: ['14 لغة للوحة التحكم بما فيها العربية RTL', 'غيّر لغة لوحة التحكم بنقرة واحدة', 'واجهة كاملة من اليمين لليسار للعربية'],
                    },
                    {
                        tag:     'لوحة التحكم',
                        h:       'أداة إدارية لا تقاومك.',
                        p:       'ارفع الأطباق. حدّد الفئات. خصّص التخطيط والخطوط والألوان. تتبّع إحصائيات الزوار. بدون تدريب مسبق.',
                        bullets: ['الذكاء الاصطناعي يقرأ قائمتك الورقية ويستوردها', 'إحصائيات زوار فورية مع فلترة بالفترة الزمنية', 'طلبات واتساب مدمجة في القائمة'],
                    },
                ],
            },
            features: {
                eyebrow: 'كل ما تحتاجه',
                headline: 'منصة قائمة طعام <em class="it">متكاملة.</em>',
                sub:     'كل ميزة يحتاجها مطعمك, من رمز QR على الطاولة إلى الإحصائيات في لوحة التحكم.',
                items: [
                    { icon: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h.01M17 14h.01M14 17h.01M17 17h.01M20 14h.01M20 17h.01M14 20h.01M17 20h.01M20 20h.01"/>',  h: 'رمز QR',              p: 'بطاقة طاولتك جاهزة فور نشر قائمتك. نزّلها، اطبعها، ضعها على الطاولة.',                              meta: 'PDF جاهز للطباعة' },
                    { icon: '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>',                                                                                                                                                                                            h: 'مزامنة فورية',        p: 'علّم طبقاً كمنتهٍ فيختفي في أقل من 200 مللي ثانية من كل الأجهزة.',                                  meta: 'تحديث مباشر'      },
                    { icon: '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',                                                                                       h: '14 لغة للوحة التحكم',  p: 'أدِر لوحة تحكمك بالعربية، الإنجليزية، الفرنسية، التركية، اليابانية وتسع لغات أخرى. دعم كامل للعربية RTL.', meta: 'يشمل RTL'    },
                    { icon: '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/><path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',                                                                          h: 'ماسح القائمة بالذكاء الاصطناعي', p: 'صوّر قائمتك الورقية. يقرأها الذكاء الاصطناعي ويستخرج كل طبق وسعر وفئة ويستوردها في ثوانٍ.', meta: 'بالذكاء الاصطناعي' },
                    { icon: '<path d="M18 20V10M12 20V4M6 20v-6"/>',                                                                                                                                                                                                   h: 'إحصائيات',            p: 'عدد المسح، وقت التصفح، أوقات الذروة، والأطباق الأكثر استقطاباً للاهتمام.',                          meta: 'بيانات الزوار'    },
                    { icon: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',                                                                                                                                                                          h: 'إدارة الفئات',        p: 'أنشئ أقساماً، رتّب الأطباق، أخفِ فئات بأكملها بنقرة. قائمتك بشروطك.',                             meta: 'تحكم كامل'        },
                    { icon: '<circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>',                                                                        h: 'هويتك البصرية',       p: 'ألوانك وشعارك. Qayema يختفي خلف علامتك التجارية, مطعمك هو من يتألق.',                               meta: 'هويتك'            },
                    { icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',                                                                                                                                                                                h: 'لا تطبيق مطلوب',      p: 'الضيوف يفتحون رابطاً في المتصفح, لا تنزيل، لا حساب، لا احتكاك.',                                  meta: 'بدون احتكاك'      },
                ],
            },
            how: {
                eyebrow: 'كيف تعمل',
                headline: 'جاهز للعمل <em class="it">في أقل من 10 دقائق.</em>',
                sub:     'لا حاجة لمطوّر. لا مكالمة تأهيل. سجّل، أضف أطباقك، وضيوفك سيمسحون رمز QR خلال الساعة.',
                steps: [
                    {
                        n:   '٠١',
                        h:   'أنشئ قائمتك',
                        p:   'سجّل الدخول، أضف أطباقك بالأسماء والأسعار والصور. رتّبها في فئات. يستغرق حوالي 10 دقائق.',
                        vis: '<div style="padding:28px;width:100%;text-align:center"><div style="display:inline-flex;flex-direction:column;gap:10px;text-align:right;width:220px"><div style="font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:rgba(246,241,232,.4);margin-bottom:4px">طبق جديد</div><div style="background:rgba(255,255,255,.04);border:.5px solid rgba(255,255,255,.08);border-radius:8px;padding:14px 16px;display:flex;flex-direction:column;gap:8px"><div style="height:8px;background:rgba(255,255,255,.12);border-radius:4px;width:70%"></div><div style="height:8px;background:rgba(255,255,255,.07);border-radius:4px;width:45%"></div></div><div style="background:var(--accent);border-radius:6px;padding:9px 14px;font-size:12px;font-weight:500;color:var(--ink);text-align:center">حفظ الطبق</div></div></div>',
                    },
                    {
                        n:   '٠٢',
                        h:   'اطبع بطاقة QR',
                        p:   'نولّد بطاقة طاولة جاهزة للطباعة في ثوانٍ. نزّل PDF، يُغلف، يُوضع على الطاولة.',
                        vis: '<div style="padding:28px;display:flex;justify-content:center;align-items:center"><div style="width:90px;background:rgba(255,255,255,.04);border:.5px solid rgba(255,255,255,.1);border-radius:10px;padding:10px;display:flex;flex-direction:column;align-items:center;gap:8px"><div style="font-size:8px;font-style:italic;color:rgba(246,241,232,.5)">ميزون</div><div style="width:60px;height:60px;background:rgba(255,255,255,.08);border-radius:4px;display:grid;place-items:center"><svg viewBox="0 0 20 20" width="44" height="44"><rect x="0" y="0" width="7" height="7" fill="rgba(246,241,232,.6)"/><rect x="1" y="1" width="5" height="5" fill="rgba(15,15,16,.8)"/><rect x="2" y="2" width="3" height="3" fill="rgba(246,241,232,.6)"/><rect x="13" y="0" width="7" height="7" fill="rgba(246,241,232,.6)"/><rect x="14" y="1" width="5" height="5" fill="rgba(15,15,16,.8)"/><rect x="15" y="2" width="3" height="3" fill="rgba(246,241,232,.6)"/><rect x="0" y="13" width="7" height="7" fill="rgba(246,241,232,.6)"/><rect x="1" y="14" width="5" height="5" fill="rgba(15,15,16,.8)"/><rect x="2" y="15" width="3" height="3" fill="rgba(246,241,232,.6)"/></svg></div><div style="font-size:7px;letter-spacing:.1em;text-transform:uppercase;color:rgba(246,241,232,.35)">امسح للطلب</div></div></div>',
                    },
                    {
                        n:   '٠٣',
                        h:   'الضيوف فقط يمسحون',
                        p:   'يتصفحون بتمهّل بلغتهم. أنت تحدّث القائمة من أي مكان. المطبخ يبقى متزامناً دائماً.',
                        vis: '<div style="padding:28px;display:flex;justify-content:center;align-items:center;gap:16px"><div style="width:7px;height:7px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 6px rgba(200,168,90,.18)"></div><div style="font-size:13px;color:rgba(246,241,232,.7)">متزامن في الوقت الفعلي</div></div>',
                    },
                ],
            },
            testimonials: {
                eyebrow: 'قصص نجاح',
                headline: 'أصحاب المطاعم <em class="it">يتحدثون بأنفسهم.</em>',
                sub:     'من الوجبات السريعة إلى المطاعم الراقية, Qayema يناسب طريقة عمل مطعمك فعلاً.',
                quotes: [
                    {
                        q:    'انتقلنا من قائمة ورقية مُغلّفة إلى Qayema في بعد ظهر واحد. ضيوفنا لاحظوا الفرق فوراً, وتوقف نادلونا عن الإجابة على سؤال "ما هذا الطبق؟" كل خمس دقائق.',
                        who:  'خالد أ.',
                        role: 'مالك، ميزون أران · الرياض',
                    },
                    {
                        q:    'دعم اللغة العربية مثالي فعلاً. ضيوفي اللبنانيون وضيوفي الفرنسيون يحصلون على قائمة تبدو مصمّمة لهم خصيصاً.',
                        who:  'نادية ر.',
                        role: 'مديرة، بيت بيروت',
                    },
                    {
                        q:    'أحدّث العروض اليومية من هاتفي كل صباح. لا مزيد من الاتصال بالمطبعة، لا مزيد من الشطب بالقلم.',
                        who:  'طارق م.',
                        role: 'شيف مالك، فينويك غريل',
                    },
                ],
            },
            cta: {
                eyebrow: 'مجاني للبدء',
                headline: 'هل أنت مستعد لتحديث <em class="it">قائمتك؟</em>',
                sub:     'انضم إلى أصحاب مطاعم يستخدمون Qayema الآن. لا بطاقة ائتمان مطلوبة.',
                cta1:    'ابدأ مجاناً',
                cta2:    'اكتشف كيف تعمل',
                fine:    'مجاني للأبد على الخطة المجانية. طوّر عندما تكون مستعداً.',
            },
            footer: {
                tagline: 'القائمة الرقمية التي يستحقها مطعمك.',
                cols: [
                    {
                        title: 'المنتج',
                        links: [
                            { label: 'المميزات',     href: '#features' },
                            { label: 'كيف تعمل',     href: '#how'      },
                            { label: 'قصص نجاح',     href: '#stories'  },
                            { label: 'لوحة التحكم',  href: '/dashboard' },
                        ],
                    },
                    {
                        title: 'الشركة',
                        links: [
                            { label: 'عن ليبيفاي', href: 'https://www.lebify.dev/', target: '_blank' },
                            { label: 'المدوّنة',   href: '#', disabled: true },
                            { label: 'وظائف',      href: '#', disabled: true },
                            { label: 'تواصل معنا', href: '{{ route('contact') }}' },
                        ],
                    },
                    {
                        title: 'قانوني',
                        links: [
                            { label: 'سياسة الخصوصية', href: '#' },
                            { label: 'شروط الاستخدام', href: '#' },
                            { label: 'سياسة الكوكيز',  href: '#' },
                        ],
                    },
                ],
                copy:  '© 2025 مجموعة ليبيفاي. جميع الحقوق محفوظة.',
                made:  'صُنع باهتمام',
            },
        },
    };

    return {
        lang:       'en',
        mobileOpen: false,

        get t()    { return copy[this.lang]; },
        get isAr() { return this.lang === 'ar'; },

        setLang(l) {
            this.lang = l;
            document.documentElement.lang = l;
            document.documentElement.dir  = l === 'ar' ? 'rtl' : 'ltr';
        },
    };
}
</script>
