// Qayema landing page — React app

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "lang": "en",
  "accent": "olive",
  "showTweaks": true
}/*EDITMODE-END*/;

// ─────────────────────────────────────────────────────────────────────────────
// Copy: English + Arabic
// ─────────────────────────────────────────────────────────────────────────────
const COPY = {
  en: {
    nav: { product: "Product", restaurants: "For Restaurants", pricing: "Pricing", stories: "Stories", signin: "Sign in", cta: "Get Qayema" },
    hero: {
      tag: { new: "New", text: "Multi-language menus now in 14 languages" },
      title1: "Menus,",
      titleIt: "reimagined",
      title2: "for the modern restaurant.",
      sub: "Qayema turns every table into a frictionless ordering experience — a single QR code, a beautifully designed digital menu, and a dashboard your staff will actually thank you for.",
      cta1: "Get started",
      cta2: "View live demo",
      stats: [
        { n: "2,400+", l: "Tables served daily" },
        { n: "31s", l: "Avg. order time" },
        { n: "14", l: "Languages supported" },
      ],
    },
    logos: {
      label: "Trusted by restaurants from Riyadh to Lisbon",
      items: ["Maison Aran", "Olea & Co.", "Sabor", "Fenwick", "Casa Lume", "North Quarter"],
    },
    problem: {
      eyebrow: "The problem",
      title: "Paper menus belong",
      titleIt: "in the past.",
      sub: "Every minute a guest waits for a menu, a refill, or a check, your kitchen falls further behind and your reviews get a little colder.",
      cards: [
        { n: "01", h: "The 11-minute wait", p: "Guests stare at a sticky laminated menu while servers run between tables. Orders arrive late, food arrives later." },
        { n: "02", h: "The wrong order", p: "Handwritten tickets, mis-heard modifiers, allergens missed. Comped meals add up fast — and so do bad reviews." },
        { n: "03", h: "The reprint cycle", p: "Change a price, update a special, swap a dish — wait three days for the printer. Your menu is always slightly out of date." },
      ],
    },
    solution: {
      eyebrow: "The solution",
      title: "One QR. One menu.",
      titleIt: "Everything in sync.",
      sub: "Qayema is the operating layer between your kitchen, your menu, and your guest's phone. No app downloads. No clunky tablets. Just elegance.",
      rows: [
        {
          h: "A menu that updates the moment your kitchen does.",
          p: "Run out of the lamb shank? Mark it 86'd from the dashboard and it disappears across every table in 200ms. Add a special at 6:42pm? It's live by 6:43.",
          bullets: ["Live availability sync", "Time-windowed specials", "Stock-aware ordering"],
          tag: "Real-time",
        },
        {
          h: "Designed for guests who came to enjoy themselves.",
          p: "Crisp typography, real photography, sensible categories, and a checkout that feels like a concierge — not a vending machine. Built for thumbs, designed for taste.",
          bullets: ["Mobile-first interaction", "14 language presets, RTL ready", "Allergen and dietary filters"],
          tag: "Hospitality",
        },
        {
          h: "A back-of-house tool that doesn't fight you back.",
          p: "Drag-and-drop your menu. Schedule items. Localize prices and translations. Set up modifiers in minutes. Export everything to your POS.",
          bullets: ["No-code menu builder", "POS integrations (Square, Lightspeed, Foodics)", "Multi-location support"],
          tag: "Dashboard",
        },
      ],
    },
    features: {
      eyebrow: "What's inside",
      title: "Everything you need to",
      titleIt: "modernize the table.",
      sub: "A complete digital ordering toolkit — without the complexity, the contracts, or the cluttered interface.",
      items: [
        { h: "Instant QR access", p: "One scan, no app store, no friction. Average load time under 800ms on a hotel Wi-Fi.", meta: "<800ms TTFB" },
        { h: "Live menu updates", p: "Price changes, 86'd items, and seasonal specials propagate to every table in real time.", meta: "Real-time sync" },
        { h: "Editorial menu UI", p: "A menu that feels like a magazine, not a mobile app. High-resolution imagery and thoughtful type.", meta: "Designed in-house" },
        { h: "Built mobile-first", p: "Optimized for one-handed thumbs, low-light dining rooms, and shaky restaurant Wi-Fi.", meta: "iOS · Android web" },
        { h: "14 languages, RTL native", p: "Arabic, Hebrew, Farsi, Urdu rendered properly — not bolted on. Every glyph treated with care.", meta: "Native i18n" },
        { h: "Allergen-aware", p: "Filter by gluten, dairy, nut, shellfish. Surface modifiers that fit each guest before they ask.", meta: "ADA-friendly" },
        { h: "POS integrations", p: "Direct order routing to Square, Lightspeed, Foodics, and Toast. Or use Qayema standalone.", meta: "12 integrations" },
        { h: "Pay at the table", p: "Optional checkout with Apple Pay, Mada, and split-the-bill. Zero hardware to buy.", meta: "Optional add-on" },
      ],
    },
    how: {
      eyebrow: "How it works",
      title: "Three steps from",
      titleIt: "scan to served.",
      sub: "Qayema works the way your guests already think — pick up the phone, look at the menu, decide. We just made each of those moments better.",
      steps: [
        { n: "Step 01", h: "Scan", p: "Guests point their camera at the table card. The menu opens instantly in their browser — no app, no signup, no friction." },
        { n: "Step 02", h: "Browse", p: "They explore a beautifully laid out menu with photography, allergen filters, and their language pre-selected. Discovery, not navigation." },
        { n: "Step 03", h: "Order", p: "One tap sends the ticket straight to your POS or kitchen display. They keep talking, you start cooking. The wait disappears." },
      ],
    },
    quotes: {
      eyebrow: "What they're saying",
      title: "Trusted by restaurants",
      titleIt: "who care about every detail.",
      cards: [
        {
          feature: true,
          q: "We replaced laminated menus with Qayema two months ago. Average ticket time dropped 38%, and our staff finally spends time at the table instead of running between it.",
          who: "Layla Othman",
          role: "Owner, Maison Aran — Dubai",
        },
        {
          q: "The dashboard is the first back-of-house tool I haven't had to write a manual for.",
          who: "Marco Vidale",
          role: "GM, Olea & Co.",
        },
        {
          q: "Our Arabic menu finally looks like our English one. That alone was worth it.",
          who: "Reem Al-Saud",
          role: "Concept Lead, Sabor",
        },
      ],
    },
    cta: {
      title1: "Transform your restaurant",
      titleIt: "experience",
      title2: "today.",
      sub: "Free to start. Live in your dining room in under an hour. Cancel anytime — though no one has yet.",
      cta1: "Start free",
      cta2: "Talk to sales",
      fine: "No credit card required · 30-day full-feature trial · QR table cards included",
    },
    foot: {
      tag: "Designed in Riyadh & Lisbon. Made for the table.",
      cols: [
        { h: "Product", links: ["Digital menus", "QR ordering", "Dashboard", "Integrations", "Pricing"] },
        { h: "Restaurants", links: ["For owners", "For chains", "For hotels", "Case studies", "Switch from paper"] },
        { h: "Company", links: ["About", "Careers", "Press", "Contact", "Status"] },
      ],
      legal: "© 2026 Qayema. All rights reserved.",
      legalLinks: ["Privacy", "Terms", "Security"],
    },
  },
  ar: {
    nav: { product: "المنتج", restaurants: "للمطاعم", pricing: "الأسعار", stories: "قصص نجاح", signin: "تسجيل الدخول", cta: "ابدأ مع قائمة" },
    hero: {
      tag: { new: "جديد", text: "قوائم متعددة اللغات الآن بـ 14 لغة" },
      title1: "قوائمٌ",
      titleIt: "أُعيد تصوّرها",
      title2: "للمطعم العصري.",
      sub: "قائمة تحوّل كل طاولة إلى تجربة طلب سلسة — رمز QR واحد، وقائمة رقمية مصمّمة بأناقة، ولوحة تحكّم سيشكرك عليها فريقك فعلاً.",
      cta1: "ابدأ الآن",
      cta2: "شاهد العرض الحي",
      stats: [
        { n: "+2,400", l: "طاولة يومياً" },
        { n: "31 ث", l: "متوسط زمن الطلب" },
        { n: "14", l: "لغة مدعومة" },
      ],
    },
    logos: {
      label: "موثوق به في مطاعم من الرياض إلى لشبونة",
      items: ["ميزون أران", "أوليا", "سابور", "فينويك", "كاسا لومي", "نورث كوارتر"],
    },
    problem: {
      eyebrow: "المشكلة",
      title: "القوائم الورقية",
      titleIt: "من الماضي.",
      sub: "كل دقيقة ينتظرها الضيف لقائمته أو فاتورته، يتأخر فيها مطبخك أكثر، وتبرد فيها تقييماتك قليلاً.",
      cards: [
        { n: "01", h: "انتظار 11 دقيقة", p: "الضيوف يحدّقون في قائمة لاصقة بينما يركض النادل بين الطاولات. الطلبات تتأخر، والطعام يتأخر أكثر." },
        { n: "02", h: "الطلب الخاطئ", p: "تذاكر مكتوبة بخط اليد، طلبات سُمعت خطأً، حساسيات غُفل عنها. الوجبات المجانية تتراكم — والتقييمات السيئة كذلك." },
        { n: "03", h: "دورة إعادة الطباعة", p: "تغيّر سعراً، تحدّث طبقاً مميزاً، تستبدل صنفاً — انتظر ثلاثة أيام للمطبعة. قائمتك دائماً غير محدّثة قليلاً." },
      ],
    },
    solution: {
      eyebrow: "الحل",
      title: "QR واحد. قائمة واحدة.",
      titleIt: "كل شيء متزامن.",
      sub: "قائمة هي الطبقة التشغيلية بين مطبخك وقائمتك وهاتف ضيفك. لا تطبيقات للتنزيل. لا أجهزة لوحية. فقط الأناقة.",
      rows: [
        {
          h: "قائمةٌ تتحدّث في اللحظة التي يتحدّث فيها مطبخك.",
          p: "نفد طبق اللحم؟ علّمه كمنتهٍ من اللوحة، فيختفي عبر كل الطاولات خلال 200 مللي ثانية. أضف عرضاً عند 6:42 مساءً؟ يبدأ بثّه عند 6:43.",
          bullets: ["مزامنة فورية للتوفّر", "عروض موقوتة", "طلب واعٍ بالمخزون"],
          tag: "في الوقت الفعلي",
        },
        {
          h: "مصمّمة لضيوف جاؤوا ليستمتعوا.",
          p: "طباعة أنيقة، تصوير حقيقي، فئات مفهومة، ودفع يبدو كخدمة كونسيرج لا كآلة بيع. مصنوعة للأصابع، مصمّمة للذوق.",
          bullets: ["تجربة محمولة أولاً", "14 لغة بدعم RTL أصلي", "فلاتر للحساسيات والحميات"],
          tag: "الضيافة",
        },
        {
          h: "أداةٌ خلفية لا تحاربك.",
          p: "اسحب وأفلت قائمتك. جدول الأصناف. ترجم الأسعار والكلمات. أنشئ المعدّلات في دقائق. صدّر كل شيء إلى نظام الكاشير.",
          bullets: ["محرر قائمة بدون كود", "تكامل مع Square وFoodics", "دعم متعدد الفروع"],
          tag: "لوحة التحكم",
        },
      ],
    },
    features: {
      eyebrow: "ما بداخلها",
      title: "كل ما تحتاجه",
      titleIt: "لتحديث الطاولة.",
      sub: "مجموعة طلب رقمية كاملة — بدون التعقيد، ولا العقود، ولا الواجهات المزدحمة.",
      items: [
        { h: "وصول QR فوري", p: "مسحة واحدة، لا متجر تطبيقات، لا احتكاك. زمن تحميل أقل من 800 مللي ثانية على واي فاي الفنادق.", meta: "أقل من 800 مللي ث" },
        { h: "تحديثات حية للقائمة", p: "تغييرات الأسعار والأصناف المنتهية والعروض الموسمية تنتشر لكل طاولة في الوقت الفعلي.", meta: "مزامنة فورية" },
        { h: "واجهة قائمة تحريرية", p: "قائمة تشبه مجلة لا تطبيقاً جوّالاً. صور عالية الجودة وطباعة مدروسة.", meta: "تصميم داخلي" },
        { h: "محمول أولاً", p: "محسّنة للإبهام بيدٍ واحدة، وقاعات الإضاءة الخافتة، وإشارات الواي فاي المتذبذبة.", meta: "iOS · أندرويد" },
        { h: "14 لغة، RTL أصلي", p: "العربية والعبرية والفارسية والأردية مرسومة كما يجب — لا مضافة لاحقاً. كل حرف بعناية.", meta: "i18n أصلي" },
        { h: "واعية بالحساسيات", p: "فلترة بحسب الجلوتين والألبان والمكسرات والمحار. اقترح المعدّلات قبل أن يسأل الضيف.", meta: "صديقة للوصول" },
        { h: "تكامل أنظمة الكاشير", p: "توجيه طلبات مباشر إلى Square وLightspeed وFoodics وToast. أو استخدم قائمة مستقلة.", meta: "12 تكاملاً" },
        { h: "ادفع على الطاولة", p: "دفع اختياري بـ Apple Pay وmada وتقسيم الفاتورة. بلا أجهزة جديدة.", meta: "إضافة اختيارية" },
      ],
    },
    how: {
      eyebrow: "كيف تعمل",
      title: "ثلاث خطوات من",
      titleIt: "المسح إلى التقديم.",
      sub: "قائمة تعمل بالطريقة التي يفكّر بها ضيوفك أصلاً — أمسك الهاتف، انظر للقائمة، اختر. نحن فقط حسّنا كل لحظة من تلك اللحظات.",
      steps: [
        { n: "خطوة 01", h: "امسح", p: "يوجّه الضيوف كاميرا هاتفهم نحو بطاقة الطاولة. تنفتح القائمة فوراً في المتصفح — لا تطبيق، لا تسجيل، لا احتكاك." },
        { n: "خطوة 02", h: "تصفّح", p: "يستكشفون قائمة مصمّمة بأناقة بصور وفلاتر حساسيات ولغتهم محدّدة مسبقاً. اكتشاف لا تنقّل." },
        { n: "خطوة 03", h: "اطلب", p: "نقرة واحدة ترسل التذكرة مباشرة إلى الكاشير أو شاشة المطبخ. هم يكملون الحديث، أنتم تبدأون الطهي. يختفي الانتظار." },
      ],
    },
    quotes: {
      eyebrow: "ماذا يقولون",
      title: "موثوقة من مطاعم",
      titleIt: "تهتمّ بكل تفصيل.",
      cards: [
        {
          feature: true,
          q: "استبدلنا القوائم الورقية بـ قائمة قبل شهرين. انخفض متوسط زمن التذكرة 38%، وأصبح فريقنا أخيراً يقضي وقته على الطاولة لا بين الطاولات.",
          who: "ليلى عثمان",
          role: "مالكة، ميزون أران — دبي",
        },
        { q: "لوحة التحكم أوّل أداة خلفية لم أحتج لكتابة دليل لها.", who: "ماركو فيدالي", role: "مدير عام، أوليا" },
        { q: "قائمتنا العربية أصبحت أخيراً تشبه قائمتنا الإنجليزية. وحدها كانت تستحق ذلك.", who: "ريم آل سعود", role: "مديرة المفهوم، سابور" },
      ],
    },
    cta: {
      title1: "حوّل تجربة",
      titleIt: "مطعمك",
      title2: "اليوم.",
      sub: "ابدأ مجاناً. شغّلها في صالتك خلال أقل من ساعة. ألغِ متى شئت — لكن لم يفعل أحد بعد.",
      cta1: "ابدأ مجاناً",
      cta2: "كلّم المبيعات",
      fine: "لا تحتاج بطاقة ائتمان · تجربة كاملة 30 يوماً · بطاقات QR للطاولات مشمولة",
    },
    foot: {
      tag: "صُمّم في الرياض ولشبونة. صُنع للطاولة.",
      cols: [
        { h: "المنتج", links: ["قوائم رقمية", "طلب QR", "لوحة التحكم", "التكاملات", "الأسعار"] },
        { h: "المطاعم", links: ["للملاك", "للسلاسل", "للفنادق", "قصص نجاح", "بدّل من الورق"] },
        { h: "الشركة", links: ["نبذة", "وظائف", "صحافة", "تواصل", "الحالة"] },
      ],
      legal: "© 2026 قائمة. كل الحقوق محفوظة.",
      legalLinks: ["الخصوصية", "الشروط", "الأمان"],
    },
  },
};

// ─────────────────────────────────────────────────────────────────────────────
// Small icon set (line, monoweight)
// ─────────────────────────────────────────────────────────────────────────────
const Ico = {
  arrow: () => (
    <svg className="arr" width="14" height="14" viewBox="0 0 14 14" fill="none">
      <path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),
  play: () => (
    <svg width="11" height="11" viewBox="0 0 11 11" fill="currentColor"><path d="M2 1.5v8L9 5.5z"/></svg>
  ),
  check: () => (
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7.5l3 3 5-6.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"/></svg>
  ),
  qr: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <rect x="3" y="3" width="11" height="11" rx="1.5" stroke="currentColor" strokeWidth="1.6"/>
      <rect x="22" y="3" width="11" height="11" rx="1.5" stroke="currentColor" strokeWidth="1.6"/>
      <rect x="3" y="22" width="11" height="11" rx="1.5" stroke="currentColor" strokeWidth="1.6"/>
      <rect x="6.5" y="6.5" width="4" height="4" fill="currentColor"/>
      <rect x="25.5" y="6.5" width="4" height="4" fill="currentColor"/>
      <rect x="6.5" y="25.5" width="4" height="4" fill="currentColor"/>
      <rect x="22" y="22" width="3" height="3" fill="currentColor"/>
      <rect x="28" y="22" width="3" height="3" fill="currentColor"/>
      <rect x="22" y="28" width="3" height="3" fill="currentColor"/>
      <rect x="28" y="28" width="3" height="3" fill="currentColor"/>
      <rect x="25" y="25" width="3" height="3" fill="currentColor"/>
      <rect x="17" y="3" width="2" height="6" fill="currentColor"/>
      <rect x="17" y="13" width="2" height="2" fill="currentColor"/>
      <rect x="17" y="22" width="2" height="6" fill="currentColor"/>
      <rect x="17" y="32" width="2" height="2" fill="currentColor"/>
    </svg>
  ),
  bolt: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none"><path d="M19 4 L9 20 H17 L15 32 L27 14 H19 Z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round"/></svg>
  ),
  layout: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <rect x="5" y="5" width="26" height="26" rx="3" stroke="currentColor" strokeWidth="1.6"/>
      <path d="M5 13 H31 M14 13 V31" stroke="currentColor" strokeWidth="1.6"/>
    </svg>
  ),
  phone: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <rect x="11" y="3" width="14" height="30" rx="3" stroke="currentColor" strokeWidth="1.6"/>
      <path d="M16 7 H20" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"/>
      <circle cx="18" cy="29" r="1" fill="currentColor"/>
    </svg>
  ),
  globe: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <circle cx="18" cy="18" r="13" stroke="currentColor" strokeWidth="1.6"/>
      <path d="M5 18 H31 M18 5 C 22 10 22 26 18 31 M18 5 C 14 10 14 26 18 31" stroke="currentColor" strokeWidth="1.6" fill="none"/>
    </svg>
  ),
  shield: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <path d="M18 4 L30 8 V18 C30 25 25 30 18 32 C11 30 6 25 6 18 V8 Z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round"/>
      <path d="M13 18 L17 22 L24 14" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),
  link: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <path d="M15 21 L21 15 M13 23 L9 19 A4.2 4.2 0 0 1 15 13 L19 17 M23 13 L27 17 A4.2 4.2 0 0 1 21 23 L17 19" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
    </svg>
  ),
  card: () => (
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <rect x="4" y="9" width="28" height="18" rx="3" stroke="currentColor" strokeWidth="1.6"/>
      <path d="M4 14 H32" stroke="currentColor" strokeWidth="1.6"/>
      <rect x="8" y="19" width="6" height="3" rx=".5" fill="currentColor"/>
    </svg>
  ),
  // problem-section iconography (struck-through pictograms)
  paperMenu: () => (
    <svg viewBox="0 0 56 56" fill="none">
      <rect x="14" y="6" width="28" height="44" rx="2" stroke="currentColor" strokeWidth="1.5"/>
      <path d="M19 16 H37 M19 22 H37 M19 28 H32 M19 34 H37 M19 40 H30" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
      <line x1="6" y1="50" x2="50" y2="6" stroke="#C8543A" strokeWidth="2" strokeLinecap="round"/>
    </svg>
  ),
  ticket: () => (
    <svg viewBox="0 0 56 56" fill="none">
      <path d="M10 10 H44 V46 L40 42 L36 46 L32 42 L28 46 L24 42 L20 46 L16 42 L12 46 L10 44 Z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round"/>
      <path d="M16 18 H38 M16 24 H38 M16 30 H30" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
      <line x1="6" y1="50" x2="50" y2="6" stroke="#C8543A" strokeWidth="2" strokeLinecap="round"/>
    </svg>
  ),
  hourglass: () => (
    <svg viewBox="0 0 56 56" fill="none">
      <path d="M14 6 H42 M14 50 H42 M14 6 V14 C14 22 28 24 28 28 C28 32 14 34 14 42 V50 M42 6 V14 C42 22 28 24 28 28 C28 32 42 34 42 42 V50" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round"/>
      <path d="M22 14 H34 M22 42 H34" stroke="currentColor" strokeWidth="1.5"/>
      <line x1="6" y1="50" x2="50" y2="6" stroke="#C8543A" strokeWidth="2" strokeLinecap="round"/>
    </svg>
  ),
};

const PROBLEM_ILLOS = [Ico.hourglass, Ico.ticket, Ico.paperMenu];

const FEATURE_ICONS = [Ico.qr, Ico.bolt, Ico.layout, Ico.phone, Ico.globe, Ico.shield, Ico.link, Ico.card];

// ─────────────────────────────────────────────────────────────────────────────
// Brand mark
// ─────────────────────────────────────────────────────────────────────────────
function BrandMark() {
  return (
    <span className="brand-mark" aria-hidden="true">
      <svg viewBox="0 0 14 14" fill="none">
        <path d="M2 7 a5 5 0 1 0 10 0 a5 5 0 1 0 -10 0" stroke="#0F0F10" strokeWidth="1.4"/>
        <path d="M9 9 L12 12" stroke="#0F0F10" strokeWidth="1.6" strokeLinecap="round"/>
      </svg>
    </span>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Components
// ─────────────────────────────────────────────────────────────────────────────
function Nav({ t, lang, setLang }) {
  return (
    <nav className="nav">
      <div className="nav-inner">
        <a className="brand" href="#">
          <BrandMark />
          <span style={{ fontFamily: lang === 'ar' ? 'var(--font-ar)' : 'var(--font-sans)' }}>
            {lang === 'ar' ? 'قائمة' : 'Qayema'}
          </span>
        </a>
        <div className="nav-links">
          <a href="#problem">{t.nav.product}</a>
          <a href="#solution">{t.nav.restaurants}</a>
          <a href="#pricing">{t.nav.pricing}</a>
          <a href="#stories">{t.nav.stories}</a>
        </div>
        <div className="nav-cta">
          <div className="lang-switch">
            <button className={lang === 'en' ? 'on' : ''} onClick={() => setLang('en')}>EN</button>
            <button className={lang === 'ar' ? 'on' : ''} onClick={() => setLang('ar')}>عربي</button>
          </div>
          <a className="btn btn-ink btn-sm" href="#cta">
            {t.nav.cta}
            <Ico.arrow />
          </a>
        </div>
      </div>
    </nav>
  );
}

function PhoneMockup({ lang }) {
  const ar = lang === 'ar';
  const items = ar
    ? [
      { name: "كباب الضأن المشوي", desc: "بهارات شامية، أرز بسمتي، صلصة طحينة", price: "78 ر.س" },
      { name: "تبولة بقدونس بلدي", desc: "بقدونس مفروم، بلغار، طماطم، ليمون", price: "32 ر.س" },
      { name: "حمص بيت قائمة", desc: "حمص مهروس، زيت زيتون، صنوبر محمّص", price: "26 ر.س" },
      { name: "سمك سيد محشي", desc: "سمك متوسطي، أرز، بصل، بهارات سرية", price: "94 ر.س" },
    ]
    : [
      { name: "Slow-Roast Lamb Shank", desc: "Levantine spice, basmati, tahini jus", price: "78 SAR" },
      { name: "Heritage Tabbouleh", desc: "Hand-cut parsley, bulgur, sumac", price: "32 SAR" },
      { name: "House Hummus", desc: "Beit Qayema blend, smoked oil, pine", price: "26 SAR" },
      { name: "Sayyad Stuffed Sea Bass", desc: "Mediterranean catch, jeweled rice", price: "94 SAR" },
    ];
  const tabs = ar ? ['المقبلات', 'الرئيسية', 'الحلويات', 'مشروبات'] : ['Mezze', 'Mains', 'Desserts', 'Drinks'];
  return (
    <div className="phone">
      <div className="phone-notch" />
      <div className="phone-screen">
        <div className="ps-status">
          <span>9:41</span>
          <span className="ps-icons">
            <svg viewBox="0 0 16 12" fill="currentColor"><path d="M2 8 L4 8 L4 11 L2 11 Z M5 6 L7 6 L7 11 L5 11 Z M8 4 L10 4 L10 11 L8 11 Z M11 1 L13 1 L13 11 L11 11 Z"/></svg>
            <svg viewBox="0 0 16 16" fill="none"><path d="M2 6 a 9 9 0 0 1 12 0 M4 9 a 6 6 0 0 1 8 0 M6 12 a 3 3 0 0 1 4 0 Z" stroke="currentColor" strokeWidth="1" fill="currentColor"/></svg>
            <svg viewBox="0 0 24 12" fill="none"><rect x="1" y="2" width="20" height="8" rx="2" stroke="currentColor" strokeWidth="1"/><rect x="3" y="4" width="13" height="4" fill="currentColor"/><path d="M22 4 V8" stroke="currentColor"/></svg>
          </span>
        </div>
        <div className="ps-header">
          <div>
            <div className="ps-rest">{ar ? 'ميزون أران' : 'Maison Aran'}</div>
            <div style={{ fontSize: 9, color: 'var(--muted)', marginTop: 2 }}>{ar ? 'قائمة المساء' : 'Evening menu'}</div>
          </div>
          <div className="ps-tbl">{ar ? 'طاولة 14' : 'Table 14'}</div>
        </div>
        <div className="ps-tabs">
          {tabs.map((tx, i) => (
            <span key={tx} className={i === 1 ? 'on' : ''}>{tx}</span>
          ))}
        </div>
        <div className="ps-list">
          {items.map((it, i) => (
            <div className={`ps-item ${i % 2 === 1 ? 'with-img' : ''}`} key={it.name}>
              {i % 2 === 1 && <div className="img" />}
              <div>
                <div className="name">{it.name}</div>
                <div className="desc">{it.desc}</div>
              </div>
              <div className="price">{it.price}</div>
            </div>
          ))}
        </div>
        <div className="ps-footbar">
          <div style={{ fontSize: 10, color: 'var(--muted)' }}>{ar ? '٤ أصناف' : '4 items'}</div>
          <div className="ps-cart">
            <span className="cart-count">2</span>
            <span>{ar ? 'عرض الطلب' : 'View order'} · 110 SAR</span>
          </div>
        </div>
      </div>
    </div>
  );
}

function TableCardVis({ lang }) {
  const ar = lang === 'ar';
  return (
    <div className="table-card">
      <div className="tc-top">
        <div className="tc-logo">{ar ? 'ميزون' : 'Maison'}</div>
        <div className="tc-table">{ar ? 'طاولة ١٤' : '14'}</div>
      </div>
      <div className="qr-wrap">
        <svg viewBox="0 0 21 21" shapeRendering="crispEdges">
          {/* Stylized QR pattern (decorative) */}
          {(() => {
            const cells = [];
            // Three position markers
            const marker = (x, y) => (
              <g key={`m-${x}-${y}`}>
                <rect x={x} y={y} width="7" height="7" fill="#E8DCCB"/>
                <rect x={x+1} y={y+1} width="5" height="5" fill="#0F0F10"/>
                <rect x={x+2} y={y+2} width="3" height="3" fill="#E8DCCB"/>
              </g>
            );
            cells.push(marker(0,0), marker(14,0), marker(0,14));
            // Random-ish pattern
            const dots = "11010101001110100100101101110010100101101010110010010101100101001011010";
            let idx = 0;
            for (let y = 0; y < 21; y++) {
              for (let x = 0; x < 21; x++) {
                const inMarker = ((x<8&&y<8)||(x>12&&y<8)||(x<8&&y>12));
                if (inMarker) continue;
                if (dots[idx % dots.length] === '1') {
                  cells.push(<rect key={`c-${x}-${y}`} x={x} y={y} width="1" height="1" fill="#E8DCCB"/>);
                }
                idx++;
              }
            }
            return cells;
          })()}
        </svg>
      </div>
      <div className="tc-foot">
        <b>{ar ? 'امسح للقائمة' : 'SCAN TO ORDER'}</b>
        <span>{ar ? 'qayema.io/maison' : 'qayema.io/maison'}</span>
      </div>
    </div>
  );
}

function Hero({ t, lang }) {
  const ar = lang === 'ar';
  return (
    <section className="hero">
      <div className="wrap hero-inner">
        <div className="hero-tag">
          <span className="new">{t.hero.tag.new}</span>
          <span>{t.hero.tag.text}</span>
        </div>
        <h1 className="hero-headline">
          {t.hero.title1}{' '}
          <span className="it">{t.hero.titleIt}</span>{' '}
          <span className="soft">{t.hero.title2}</span>
        </h1>

        <div className="hero-foot">
          <div>
            <div className="meta"><span className="dash" /> {ar ? 'القائمة الرقمية لمطعمك' : 'The digital menu, refined'}</div>
            <p className="hero-sub">{t.hero.sub}</p>
          </div>
          <div className="hero-actions">
            <a className="btn btn-ink" href="#cta">{t.hero.cta1} <Ico.arrow /></a>
            <a className="btn btn-outline" href="#"><Ico.play /> {t.hero.cta2}</a>
          </div>
        </div>

        <div className="hero-strip">
          <div>
            <div className="k">{ar ? 'متوسط الطلب' : 'Avg. order'}</div>
            <div className="v">31<span className="u">s</span></div>
          </div>
          <div>
            <div className="k">{ar ? 'لغة مدعومة' : 'Languages'}</div>
            <div className="v">14</div>
          </div>
          <div>
            <div className="k">{ar ? 'تكامل' : 'Integrations'}</div>
            <div className="v">12<span className="u">+</span></div>
          </div>
          <div>
            <div className="k">{ar ? 'وقت التحميل' : 'Load time'}</div>
            <div className="v"><span className="it">‹</span>800<span className="u">ms</span></div>
          </div>
        </div>
      </div>

      <div className="hero-showcase">
        <div className="wrap">
          <div className="hero-stage">
            <div className="annot">
              <span className="annot-pill">
                <span className="pulse"/>
                {ar ? 'متزامن مع المطبخ' : 'Synced with the kitchen'}
              </span>
              <div className="annot-card">
                <div className="lbl"><span style={{ color: 'var(--olive)' }}>●</span> {ar ? 'تحديث مباشر' : 'Live update'}</div>
                <div className="body">{ar ? 'انتهى لحم الضأن في الساعة 8:14 — اختفى من 18 طاولة في 200 مللي ثانية.' : 'Lamb shank 86\u2019d at 8:14pm — pulled from 18 tables in 200ms.'}</div>
              </div>
            </div>
            <div style={{ position: 'relative', display: 'flex', justifyContent: 'center', alignItems: 'flex-end' }}>
              <TableCardVis lang={lang} />
              <PhoneMockup lang={lang} />
            </div>
            <div className="annot annot-right">
              <span className="annot-pill">
                {ar ? 'بدون تطبيق · بدون احتكاك' : 'No app · zero friction'}
                <Ico.arrow />
              </span>
              <div className="annot-card">
                <div className="lbl">{ar ? 'تجربة الضيف' : 'Guest experience'} · <span style={{ color: 'var(--olive)' }}>{ar ? '٤.٩' : '4.9'}</span></div>
                <div className="body">{ar ? '"شعرنا أن المطعم اعتنى بنا قبل أن نطلب." — ضيفة في ميزون أران' : '"It felt like the restaurant cared about us before we ordered." — guest, Maison Aran'}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function Logos({ t }) {
  return (
    <div className="logos">
      <div className="wrap logos-inner">
        <div className="logos-label">{t.logos.label}</div>
        <div className="logos-row">
          {t.logos.items.map((name, i) => (
            <div key={name} className="logo-mark">
              {i % 2 === 0 ? <span className="it" style={{ fontSize: 22 }}>{name.split(' ')[0]}</span> : <span style={{ fontWeight: 500 }}>{name}</span>}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function SectionHead({ eyebrow, title, titleIt, sub, dark }) {
  return (
    <div className="section-head">
      <div>
        <div className="eyebrow"><span className="dot"/> {eyebrow}</div>
        <h2 style={{ marginTop: 18 }}>
          {title} <span className="it">{titleIt}</span>
        </h2>
      </div>
      <div className="right">{sub}</div>
    </div>
  );
}

function Problem({ t }) {
  return (
    <section id="problem" className="section light-2">
      <div className="wrap">
        <SectionHead eyebrow={t.problem.eyebrow} title={t.problem.title} titleIt={t.problem.titleIt} sub={t.problem.sub} />
        <div className="problem-grid">
          {t.problem.cards.map((c, i) => {
            const Illo = PROBLEM_ILLOS[i];
            return (
              <div className="problem-card" key={c.n}>
                <div className="strike-ill"><Illo /></div>
                <div className="problem-num">{c.n}</div>
                <h3>{c.h}</h3>
                <p style={{ marginTop: 8 }}>{c.p}</p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

// Decorative inline visuals for the solution rows
function SolutionVis1() {
  // "Updates in real time" — a list with one item being struck/replaced
  return (
    <div className="feat-vis dark-vis" style={{ padding: 40 }}>
      <div style={{
        background: 'rgba(255,255,255,.03)', border: '.5px solid rgba(255,255,255,.08)',
        borderRadius: 12, padding: 18, color: 'var(--paper)', maxWidth: 360, margin: '40px auto'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: 11, letterSpacing: '.12em', textTransform: 'uppercase', color: 'rgba(246,241,232,.5)' }}>
          <span>● Live</span><span>Mains</span>
        </div>
        <div style={{ marginTop: 16, display: 'flex', flexDirection: 'column', gap: 14 }}>
          {[
            { n: "Slow-Roast Lamb Shank", p: "78", soldOut: true },
            { n: "Wood-Fire Sea Bass", p: "94" },
            { n: "Truffle Mansaf", p: "120", isNew: true },
            { n: "Aged Ribeye, 280g", p: "165" },
          ].map((x) => (
            <div key={x.n} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: 14, opacity: x.soldOut ? .35 : 1, position: 'relative' }}>
              <span style={{ textDecoration: x.soldOut ? 'line-through' : 'none' }}>{x.n}</span>
              <span style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                {x.isNew && <span style={{ background: 'var(--olive)', color: 'var(--paper)', fontSize: 9, padding: '2px 7px', borderRadius: 999, letterSpacing: '.08em', textTransform: 'uppercase' }}>New</span>}
                <span style={{ color: 'var(--olive-soft)' }}>{x.p} SAR</span>
              </span>
            </div>
          ))}
        </div>
        <div style={{ marginTop: 24, paddingTop: 16, borderTop: '.5px solid rgba(255,255,255,.08)', fontSize: 11, color: 'rgba(246,241,232,.45)' }}>
          Synced 2s ago · across 18 tables
        </div>
      </div>
    </div>
  );
}

function SolutionVis2({ lang }) {
  // Hospitality — show two language "phones" stacked
  const ar = lang === 'ar';
  return (
    <div className="feat-vis" style={{ padding: 32, position: 'relative' }}>
      <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(160deg, var(--sand) 0%, var(--paper-2) 100%)' }} />
      <div style={{ position: 'relative', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 18 }}>
        {[
          { lang: 'EN', name: 'Slow-Roast Lamb Shank', desc: 'Levantine spice, tahini', tag: '78 SAR', font: 'var(--font-sans)' },
          { lang: 'عربي', name: 'كباب الضأن المشوي', desc: 'بهارات شامية، طحينة', tag: '٧٨ ر.س', font: 'var(--font-ar)', dir: 'rtl' },
        ].map((c, i) => (
          <div key={c.lang} style={{
            width: 200, background: 'var(--paper)', borderRadius: 14, padding: 18,
            boxShadow: '0 20px 40px -20px rgba(0,0,0,.18)',
            transform: `rotate(${i ? 4 : -4}deg) translateY(${i ? 14 : -10}px)`,
            border: '.5px solid var(--line)',
            direction: c.dir || 'ltr',
            fontFamily: c.font,
          }}>
            <div style={{ fontSize: 9, letterSpacing: '.14em', textTransform: 'uppercase', color: 'var(--muted)', marginBottom: 12 }}>
              {c.lang}
            </div>
            <div style={{
              width: '100%', aspectRatio: '4/3', borderRadius: 8,
              background: 'linear-gradient(135deg, #6B5B3F, #3D3324)',
              marginBottom: 14,
            }}/>
            <div style={{ fontSize: 14, fontWeight: 500, lineHeight: 1.25, marginBottom: 4 }}>{c.name}</div>
            <div style={{ fontSize: 11, color: 'var(--muted)', lineHeight: 1.4, marginBottom: 12 }}>{c.desc}</div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: 12 }}>
              <span style={{ color: 'var(--olive)', fontWeight: 500 }}>{c.tag}</span>
              <span style={{ width: 22, height: 22, borderRadius: '50%', background: 'var(--ink)', color: 'var(--paper)', display: 'grid', placeItems: 'center', fontSize: 14, lineHeight: 1 }}>+</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function SolutionVis3() {
  // Dashboard
  return (
    <div className="feat-vis" style={{ background: 'linear-gradient(160deg, var(--paper-2), var(--sand))', padding: 24 }}>
      <div style={{
        background: 'var(--paper)', borderRadius: 12, height: '100%',
        boxShadow: '0 20px 40px -22px rgba(0,0,0,.2)',
        border: '.5px solid var(--line)',
        overflow: 'hidden', display: 'flex', flexDirection: 'column'
      }}>
        <div style={{
          padding: '12px 18px', borderBottom: '.5px solid var(--line)',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between',
          fontSize: 12,
        }}>
          <div style={{ display: 'flex', gap: 6 }}>
            <span style={{ width: 9, height: 9, borderRadius: '50%', background: '#E2796B' }}/>
            <span style={{ width: 9, height: 9, borderRadius: '50%', background: '#E8C25C' }}/>
            <span style={{ width: 9, height: 9, borderRadius: '50%', background: 'var(--olive-soft)' }}/>
          </div>
          <span style={{ color: 'var(--muted)', fontSize: 11, fontFamily: 'var(--font-display)', fontStyle: 'italic' }}>Maison Aran · dashboard</span>
          <span/>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: '120px 1fr', flex: 1 }}>
          <div style={{ borderRight: '.5px solid var(--line)', padding: 16, fontSize: 11, color: 'var(--muted)', display: 'flex', flexDirection: 'column', gap: 12 }}>
            {['Overview', 'Menu', 'Items', 'Orders', 'Tables', 'Locations', 'Settings'].map((s, i) => (
              <span key={s} style={{ color: i === 1 ? 'var(--ink)' : 'var(--muted)', fontWeight: i === 1 ? 500 : 400 }}>
                {i === 1 && <span style={{ color: 'var(--olive)', marginRight: 6 }}>●</span>}{s}
              </span>
            ))}
          </div>
          <div style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 10 }}>
            <div style={{ fontSize: 13, fontWeight: 500, marginBottom: 4 }}>Mains</div>
            {[
              { n: 'Slow-Roast Lamb Shank', s: 'Active', p: '78' },
              { n: 'Wood-Fire Sea Bass', s: 'Active', p: '94' },
              { n: 'Truffle Mansaf', s: 'Draft', p: '120' },
              { n: 'Aged Ribeye, 280g', s: 'Active', p: '165' },
            ].map((r) => (
              <div key={r.n} style={{
                display: 'grid', gridTemplateColumns: '1fr 70px 50px',
                alignItems: 'center',
                padding: '8px 12px',
                background: 'var(--paper-2)',
                borderRadius: 6,
                fontSize: 11.5,
              }}>
                <span>{r.n}</span>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: r.s === 'Active' ? 'var(--olive)' : 'var(--muted)' }}>
                  <span style={{ width: 6, height: 6, borderRadius: '50%', background: r.s === 'Active' ? 'var(--olive)' : 'var(--muted)' }}/>
                  {r.s}
                </span>
                <span style={{ textAlign: 'right', color: 'var(--ink)', fontVariantNumeric: 'tabular-nums' }}>{r.p}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

const SOLUTION_VISES = [SolutionVis1, SolutionVis2, SolutionVis3];

function Solution({ t, lang }) {
  return (
    <section id="solution" className="section light">
      <div className="wrap">
        <SectionHead eyebrow={t.solution.eyebrow} title={t.solution.title} titleIt={t.solution.titleIt} sub={t.solution.sub} />
        <div className="solution-stack">
          {t.solution.rows.map((r, i) => {
            const Vis = SOLUTION_VISES[i];
            const reverse = i % 2 === 1;
            return (
              <div className={`feat-row ${reverse ? 'reverse' : ''}`} key={r.h}>
                <div className="feat-text">
                  <div className="eyebrow"><span className="dot"/> {r.tag}</div>
                  <h3>{r.h}</h3>
                  <p>{r.p}</p>
                  <ul className="feat-bullets">
                    {r.bullets.map((b) => (
                      <li key={b}><Ico.check /> {b}</li>
                    ))}
                  </ul>
                </div>
                <Vis lang={lang} />
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function Features({ t }) {
  return (
    <section id="features" className="section light-2">
      <div className="wrap">
        <SectionHead eyebrow={t.features.eyebrow} title={t.features.title} titleIt={t.features.titleIt} sub={t.features.sub} />
        <div className="features-grid">
          {t.features.items.map((f, i) => {
            const Icon = FEATURE_ICONS[i];
            return (
              <div className="feature-cell" key={f.h}>
                <div className="ico"><Icon /></div>
                <h3>{f.h}</h3>
                <p>{f.p}</p>
                <div className="meta">{f.meta}</div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function HowItWorks({ t }) {
  return (
    <section id="how" className="section dark">
      <div className="wrap">
        <SectionHead eyebrow={t.how.eyebrow} title={t.how.title} titleIt={t.how.titleIt} sub={t.how.sub} dark />
        <div className="steps">
          {t.how.steps.map((s, i) => (
            <div className="step" key={s.n}>
              <div className="step-num">{s.n}</div>
              <h3>{s.h}</h3>
              <p>{s.p}</p>
              <div className="step-vis">
                {i === 0 && <StepVis1 />}
                {i === 1 && <StepVis2 />}
                {i === 2 && <StepVis3 />}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function StepVis1() {
  // Phone scanning a QR — minimal
  return (
    <div style={{ width: '100%', height: '100%', display: 'grid', placeItems: 'center', position: 'relative' }}>
      <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
        <rect x="6" y="6" width="28" height="28" rx="3" stroke="rgba(232,220,203,.35)" strokeWidth="1.5"/>
        <rect x="46" y="6" width="28" height="28" rx="3" stroke="rgba(232,220,203,.35)" strokeWidth="1.5"/>
        <rect x="6" y="46" width="28" height="28" rx="3" stroke="rgba(232,220,203,.35)" strokeWidth="1.5"/>
        <rect x="14" y="14" width="12" height="12" fill="rgba(232,220,203,.7)"/>
        <rect x="54" y="14" width="12" height="12" fill="rgba(232,220,203,.7)"/>
        <rect x="14" y="54" width="12" height="12" fill="rgba(232,220,203,.7)"/>
        <rect x="40" y="14" width="6" height="6" fill="var(--olive-soft)"/>
        <rect x="46" y="40" width="6" height="6" fill="var(--olive-soft)"/>
        <rect x="60" y="46" width="6" height="6" fill="var(--olive-soft)"/>
        <rect x="46" y="60" width="6" height="6" fill="var(--olive-soft)"/>
        <rect x="68" y="60" width="6" height="6" fill="var(--olive-soft)"/>
        <rect x="60" y="68" width="6" height="6" fill="var(--olive-soft)"/>
      </svg>
      {/* corner brackets */}
      {[[14,14,'tl'],[14,14,'tr'],[14,14,'bl'],[14,14,'br']].map((_, i) => {
        const positions = [
          { top: 30, left: 30 }, { top: 30, right: 30 },
          { bottom: 30, left: 30 }, { bottom: 30, right: 30 }
        ];
        const rotations = ['0', '90deg', '270deg', '180deg'];
        return (
          <div key={i} style={{
            position: 'absolute', ...positions[i], width: 16, height: 16,
            borderTop: '1.5px solid var(--olive-soft)', borderLeft: '1.5px solid var(--olive-soft)',
            transform: `rotate(${rotations[i]})`,
          }}/>
        );
      })}
    </div>
  );
}

function StepVis2() {
  return (
    <div style={{ padding: 24, width: '100%', height: '100%', display: 'flex', flexDirection: 'column', gap: 8, justifyContent: 'center' }}>
      {[
        { n: 'Mezze', dot: 'rgba(232,220,203,.35)' },
        { n: 'Mains', dot: 'var(--olive-soft)', active: true },
        { n: 'Sides', dot: 'rgba(232,220,203,.35)' },
        { n: 'Desserts', dot: 'rgba(232,220,203,.35)' },
        { n: 'Drinks', dot: 'rgba(232,220,203,.35)' },
      ].map((c) => (
        <div key={c.n} style={{
          display: 'flex', alignItems: 'center', gap: 10,
          padding: '10px 14px',
          background: c.active ? 'rgba(232,220,203,.06)' : 'transparent',
          border: `.5px solid ${c.active ? 'rgba(232,220,203,.18)' : 'rgba(232,220,203,.06)'}`,
          borderRadius: 8,
          fontSize: 13,
          color: c.active ? 'var(--paper)' : 'rgba(246,241,232,.4)',
          fontWeight: c.active ? 500 : 400,
        }}>
          <span style={{ width: 6, height: 6, borderRadius: '50%', background: c.dot }}/>
          {c.n}
        </div>
      ))}
    </div>
  );
}

function StepVis3() {
  return (
    <div style={{ padding: 24, width: '100%', height: '100%', display: 'flex', flexDirection: 'column', justifyContent: 'center', gap: 12 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, color: 'rgba(246,241,232,.45)', letterSpacing: '.12em', textTransform: 'uppercase' }}>
        <span>Order #2841</span>
        <span style={{ color: 'var(--olive-soft)' }}>● Sent</span>
      </div>
      {[
        { n: 'Slow-Roast Lamb Shank', q: '×1', p: '78' },
        { n: 'Heritage Tabbouleh', q: '×1', p: '32' },
      ].map((it) => (
        <div key={it.n} style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13, alignItems: 'baseline' }}>
          <span style={{ display: 'flex', gap: 8 }}>
            <span style={{ color: 'rgba(246,241,232,.45)' }}>{it.q}</span>
            <span>{it.n}</span>
          </span>
          <span style={{ color: 'rgba(246,241,232,.7)', fontVariantNumeric: 'tabular-nums' }}>{it.p}</span>
        </div>
      ))}
      <div style={{ borderTop: '.5px solid rgba(232,220,203,.12)', paddingTop: 12, display: 'flex', justifyContent: 'space-between', fontSize: 13, fontWeight: 500 }}>
        <span>Total</span>
        <span>110 SAR</span>
      </div>
    </div>
  );
}

function Quotes({ t }) {
  return (
    <section id="stories" className="section light">
      <div className="wrap">
        <SectionHead eyebrow={t.quotes.eyebrow} title={t.quotes.title} titleIt={t.quotes.titleIt} sub="" />
        <div className="quote-grid">
          {t.quotes.cards.map((c, i) => (
            <div className={`quote-card ${c.feature ? 'feature' : ''}`} key={c.who}>
              <div>
                <div className="quote-mark">"</div>
                <div className="quote-text">{c.q}</div>
              </div>
              <div className="quote-meta">
                <div className="quote-avatar"/>
                <div>
                  <div className="who">{c.who}</div>
                  <div className="role">{c.role}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function FinalCTA({ t }) {
  return (
    <section id="cta" className="cta-section">
      <div className="wrap">
        <h2>
          {t.cta.title1}{' '}
          <span className="it">{t.cta.titleIt}</span>{' '}
          {t.cta.title2}
        </h2>
        <p>{t.cta.sub}</p>
        <div className="cta-actions">
          <a className="btn btn-primary" href="#">{t.cta.cta1} <Ico.arrow /></a>
          <a className="btn btn-ghost" href="#">{t.cta.cta2}</a>
        </div>
        <div className="cta-fineprint">{t.cta.fine}</div>
      </div>
    </section>
  );
}

function Footer({ t, lang }) {
  return (
    <footer>
      <div className="wrap">
        <div className="foot-grid">
          <div>
            <div className="brand" style={{ color: 'var(--paper)', marginBottom: 16 }}>
              <BrandMark />
              <span style={{ fontFamily: lang === 'ar' ? 'var(--font-ar)' : 'var(--font-sans)' }}>
                {lang === 'ar' ? 'قائمة' : 'Qayema'}
              </span>
            </div>
            <p style={{ margin: 0, maxWidth: '36ch', lineHeight: 1.6 }}>{t.foot.tag}</p>
          </div>
          {t.foot.cols.map((col) => (
            <div key={col.h}>
              <h4>{col.h}</h4>
              <ul>
                {col.links.map((l) => <li key={l}><a href="#">{l}</a></li>)}
              </ul>
            </div>
          ))}
        </div>
        <div className="foot-bottom">
          <span>{t.foot.legal}</span>
          <span style={{ display: 'flex', gap: 24 }}>
            {t.foot.legalLinks.map((l) => <a key={l} href="#" style={{ color: 'inherit' }}>{l}</a>)}
          </span>
        </div>
      </div>
    </footer>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// App
// ─────────────────────────────────────────────────────────────────────────────
function App() {
  const [tw, setTweak] = useTweaks(TWEAK_DEFAULTS);
  const lang = tw.lang;
  const t = COPY[lang] || COPY.en;

  // Sync html lang/dir
  React.useEffect(() => {
    const html = document.documentElement;
    html.lang = lang;
    html.dir = lang === 'ar' ? 'rtl' : 'ltr';
  }, [lang]);

  // Apply accent
  React.useEffect(() => {
    const r = document.documentElement;
    if (tw.accent === 'gold') {
      r.style.setProperty('--olive', '#C8A85A');
      r.style.setProperty('--olive-deep', '#9C7F3D');
      r.style.setProperty('--olive-soft', '#E0C684');
    } else if (tw.accent === 'terracotta') {
      r.style.setProperty('--olive', '#B8694A');
      r.style.setProperty('--olive-deep', '#8C4A33');
      r.style.setProperty('--olive-soft', '#D89880');
    } else {
      r.style.setProperty('--olive', '#6B7A4F');
      r.style.setProperty('--olive-deep', '#4F5C3A');
      r.style.setProperty('--olive-soft', '#A8B388');
    }
  }, [tw.accent]);

  const setLang = (l) => setTweak('lang', l);

  return (
    <>
      <Nav t={t} lang={lang} setLang={setLang} />
      <Hero t={t} lang={lang} />
      <Logos t={t} />
      <Problem t={t} />
      <Solution t={t} lang={lang} />
      <Features t={t} />
      <HowItWorks t={t} />
      <Quotes t={t} />
      <FinalCTA t={t} />
      <Footer t={t} lang={lang} />

      <TweaksPanel title="Tweaks">
        <TweakSection label="Language" />
        <TweakRadio label="Direction" value={tw.lang}
          options={[{ value: 'en', label: 'English' }, { value: 'ar', label: 'عربي' }]}
          onChange={(v) => setTweak('lang', v)} />
        <TweakSection label="Accent" />
        <TweakRadio label="Color" value={tw.accent}
          options={[{ value: 'olive', label: 'Olive' }, { value: 'gold', label: 'Gold' }, { value: 'terracotta', label: 'Clay' }]}
          onChange={(v) => setTweak('accent', v)} />
      </TweaksPanel>
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
