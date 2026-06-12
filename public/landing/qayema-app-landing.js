/* ============================================================
   QAYEMA — content (AR/EN), theme, i18n, GSAP choreography
   ============================================================ */
(function () {
  'use strict';

  /* ---------- i18n dictionary ---------- */
  const T = {
    nav: {
      features:  { en: 'Features',     ar: 'المميزات' },
      how:       { en: 'How it works', ar: 'كيف يعمل' },
      templates: { en: 'Templates',    ar: 'القوالب' },
      pricing:   { en: 'Pricing',      ar: 'الأسعار' },
      contact:   { en: 'Contact',      ar: 'تواصل' },
      cta:       { en: 'Get started free', ar: 'ابدأ مجاناً' },
    },
  };

  /* ---------- Section content ---------- */
  const FEATURES = [
    { feat: true, badge: { en: 'The standout feature', ar: 'الميزة الأبرز' },
      icon: 'camera',
      title: { en: 'Photograph your menu. AI builds the rest.', ar: 'صوّر قائمتك. والذكاء الاصطناعي يبني الباقي.' },
      desc:  { en: 'Snap a photo of your paper or PDF menu. Qayema reads every dish, price and section — then drafts a complete bilingual digital menu in seconds.', ar: 'التقط صورة لقائمتك الورقية. تقرأ قيمة كل طبق وسعر وقسم، ثم تبني قائمة رقمية ثنائية اللغة في ثوانٍ.' } },
    { icon: 'globe', title: { en: 'Arabic-first, bilingual', ar: 'عربي أولاً، ثنائي اللغة' },
      desc: { en: 'Designed RTL from the ground up. Arabic & English side by side.', ar: 'مصمّم من اليمين لليسار. العربية والإنجليزية جنباً إلى جنب.' } },
    { wide: true, icon: 'palette', title: { en: 'Beautiful templates, switch anytime', ar: 'قوالب أنيقة، بدّلها متى شئت' },
      desc: { en: 'Curated, editorial designs that look stunning on every phone. Change template without rebuilding your menu.', ar: 'تصاميم منسّقة تبدو رائعة على كل هاتف. غيّر القالب دون إعادة بناء قائمتك.' } },
    { icon: 'qr', title: { en: 'Custom QR code', ar: 'رمز QR مخصّص' },
      desc: { en: 'Your colors and logo. The link never breaks.', ar: 'بألوانك وشعارك. الرابط لا ينكسر أبداً.' } },
    { icon: 'whatsapp', title: { en: 'WhatsApp ordering', ar: 'الطلب عبر واتساب' },
      desc: { en: 'Diners order in a tap — straight to your WhatsApp.', ar: 'يطلب الضيوف بنقرة — مباشرة إلى واتسابك.' } },
    { icon: 'chart', title: { en: 'Live analytics', ar: 'تحليلات مباشرة' },
      desc: { en: 'Views, scans, visitors, WhatsApp orders, time spent.', ar: 'المشاهدات، المسح، الزوار، طلبات واتساب، الوقت.' } },
    { wide: true, icon: 'layers', title: { en: 'Effortless management', ar: 'إدارة بلا عناء' },
      desc: { en: 'Dishes, categories, prices, ingredients, photos, variants & sizes — drag to reorder, toggle availability instantly.', ar: 'الأطباق، الفئات، الأسعار، المكونات، الصور، الأحجام — اسحب لإعادة الترتيب وبدّل التوفر فوراً.' } },
    { icon: 'phone', title: { en: 'No app for customers', ar: 'لا تطبيق للزبائن' },
      desc: { en: 'They scan, it opens. Nothing to download.', ar: 'يمسحون، فتفتح. لا شيء للتحميل.' } },
  ];

  const STEPS = [
    { idx: '01', icon: 'user', title: { en: 'Sign up', ar: 'سجّل' },
      desc: { en: 'Create your account in under a minute. Pick a free template to begin — no card required.', ar: 'أنشئ حسابك في أقل من دقيقة. اختر قالباً مجانياً للبدء — دون بطاقة.' } },
    { idx: '02', icon: 'camera', title: { en: 'Build it', ar: 'ابنِها' },
      desc: { en: 'Scan your paper menu with AI, or add dishes by hand. Categories, prices, photos and variants — all in one place.', ar: 'امسح قائمتك بالذكاء الاصطناعي، أو أضف الأطباق يدوياً. الفئات والأسعار والصور — في مكان واحد.' } },
    { idx: '03', icon: 'qr', title: { en: 'Get your QR', ar: 'احصل على رمزك' },
      desc: { en: 'Download a custom QR in your colors. Print it, place it — your living menu is one scan away.', ar: 'حمّل رمز QR بألوانك. اطبعه وضعه — قائمتك الحيّة على بُعد مسحة.' } },
    { idx: '04', icon: 'chart', title: { en: 'Track results', ar: 'تابع النتائج' },
      desc: { en: 'Watch scans, popular dishes and WhatsApp orders roll in. Update anything, anytime — instantly live.', ar: 'تابع المسح والأطباق الأكثر طلباً وطلبات واتساب. حدّث أي شيء في أي وقت — فوراً.' } },
  ];

  const TEMPLATES = [
    { name: 'Noir',   tag: { en: 'Fine dining', ar: 'فاخر' },   price: { en: 'Free', ar: 'مجاني' },   badge: 'free', style: 'pv-noir',  rest: 'Maison Aran', rows: [['Lamb shank','$32'],['Sea bass','$28'],['Tabbouleh','$14']] },
    { name: 'Ivory',  tag: { en: 'Café', ar: 'مقهى' },          price: { en: 'Free', ar: 'مجاني' },   badge: 'free', style: 'pv-ivory', rest: 'KISSA Nº7',   rows: [['Pour over','18'],['Matcha','16'],['Latte','14']] },
    { name: 'Aurum',  tag: { en: 'Levantine', ar: 'شامي' },     price: { en: '$12', ar: '٤٥ ر.س' },   badge: 'prem', style: 'pv-gold',  rest: 'Beit Qamar',  rows: [['Mezze','24'],['Grill','38'],['Knafeh','12']] },
    { name: 'Mono',   tag: { en: 'Specialty', ar: 'مختص' },     price: { en: '$9', ar: '٣٥ ر.س' },    badge: 'prem', style: 'pv-min',   rest: 'TYPE / BAR',  rows: [['Espresso','3.0'],['Cortado','3.5'],['Filter','4.0']] },
    { name: 'Obsidian', tag: { en: 'Steakhouse', ar: 'مشاوي' }, price: { en: '$14', ar: '٥٠ ر.س' },   badge: 'prem', style: 'pv-noir',  rest: 'Cut & Coal',  rows: [['Ribeye','46'],['Marrow','14'],['Fries','11']] },
    { name: 'Linen',  tag: { en: 'Brunch', ar: 'فطور' },        price: { en: 'Free', ar: 'مجاني' },   badge: 'free', style: 'pv-ivory', rest: 'Morning Co.', rows: [['Avo toast','13'],['Shakshuka','15'],['Flat white','5']] },
  ];

  const FAQ = [
    { q: { en: 'Do my customers need an app?', ar: 'هل يحتاج زبائني إلى تطبيق؟' },
      a: { en: 'Never. Diners scan your QR and the menu opens instantly in their browser — nothing to download, on any phone.', ar: 'أبداً. يمسح الضيوف رمز QR فتفتح القائمة فوراً في المتصفح — لا شيء للتحميل، على أي هاتف.' } },
    { q: { en: 'Is it really Arabic & English?', ar: 'هل هي فعلاً عربية وإنجليزية؟' },
      a: { en: 'Yes — Qayema is Arabic-first with full RTL support. Every dish can carry both languages, and guests switch with a tap.', ar: 'نعم — قيمة عربية أولاً بدعم كامل لليمين لليسار. كل طبق يحمل اللغتين، ويبدّل الضيف بنقرة.' } },
    { q: { en: 'What happens if I stop paying?', ar: 'ماذا لو توقفت عن الدفع؟' },
      a: { en: 'Your menu pauses — it is never deleted. Reactivate anytime and everything is exactly where you left it.', ar: 'تتوقف قائمتك مؤقتاً — ولا تُحذف أبداً. أعد تفعيلها في أي وقت وكل شيء كما تركته.' } },
    { q: { en: 'How fast can I move off paper?', ar: 'كم بسرعة أنتقل من الورق؟' },
      a: { en: 'Minutes. Photograph your existing menu, let the AI draft it, review, and publish your QR the same day.', ar: 'دقائق. صوّر قائمتك الحالية، دع الذكاء يبنيها، راجع، وانشر رمزك في اليوم نفسه.' } },
    { q: { en: 'Can I change template later?', ar: 'هل أغيّر القالب لاحقاً؟' },
      a: { en: 'Anytime, without rebuilding. Your dishes stay put — only the design changes. The QR link never breaks.', ar: 'في أي وقت، دون إعادة بناء. تبقى أطباقك — يتغيّر التصميم فقط. ورابط QR لا ينكسر.' } },
  ];

  /* ---------- Inline icons ---------- */
  const ICON = {
    camera: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 1 2-2h2l1.5-2h7L17 6h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="12" cy="12.5" r="3.5"/></svg>',
    globe: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg>',
    palette: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 0 18c1.7 0 2-1.3 1.2-2.2-.8-1 .1-2.3 1.3-2.3H17a4 4 0 0 0 4-4 8.5 8.5 0 0 0-9-9.5z"/><circle cx="7.5" cy="11" r="1.2" fill="currentColor"/><circle cx="11" cy="7.5" r="1.2" fill="currentColor"/><circle cx="15.5" cy="9" r="1.2" fill="currentColor"/></svg>',
    qr: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M21 21v-4M17 21h1M14 21h.01"/></svg>',
    whatsapp: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.7-5A8 8 0 1 1 8 19.3z"/><path d="M8.5 9.5c0 3 2 5 5 5 .8 0 1.3-.7.8-1.3l-1-1c-.3-.3-.7-.2-1 0l-.4.3c-.8-.4-1.4-1-1.8-1.8l.3-.4c.2-.3.3-.7 0-1l-1-1c-.6-.5-1.3 0-1.3.8z"/></svg>',
    chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v16h16"/><path d="M8 14l3-4 3 2 4-6"/></svg>',
    layers: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5M3 17l9 5 9-5" opacity=".6"/></svg>',
    phone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="3"/><path d="M11 18.5h2"/></svg>',
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
    spark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3l1.8 5.4L19 10l-5.2 1.6L12 17l-1.8-5.4L5 10l5.2-1.6z"/></svg>',
  };
  const arrowSvg = '<svg class="ico arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

  /* ---------- State ---------- */
  let lang = (function(){ try { return localStorage.getItem('qayema-lang') || 'en'; } catch(e){ return 'en'; } })();
  const tt = (o) => (o && (o[lang] != null)) ? o[lang] : (o && o.en) || '';

  /* ---------- Render helpers ---------- */
  function renderFeatures() {
    const wrap = document.getElementById('featuresBox');
    if (!wrap) return;
    const lead = FEATURES.find(f => f.feat);
    const rest = FEATURES.filter(f => !f.feat);
    let html = '';
    if (lead) {
      html += `<div class="feat-lead reveal">
        <div class="lead-text">
          <span class="badge">${tt(lead.badge)}</span>
          <h3 class="display">${tt(lead.title)}</h3>
          <p>${tt(lead.desc)}</p>
        </div>
        <div class="lead-vis">
          <div class="scan-demo">
            <div class="paper"><div class="pl"></div><div class="pl s"></div><div class="pl"></div><div class="pl s"></div></div>
            <span class="arrow">${ICON.spark}</span>
            <div class="digi"><div class="dl"><span>Mezze</span><span class="p">$8</span></div><div class="dl"><span>Tagine</span><span class="p">$22</span></div><div class="dl"><span>Baklava</span><span class="p">$9</span></div></div>
            <div class="scan-beam"></div>
          </div>
        </div>
      </div>`;
    }
    html += '<div class="feat-grid" data-stagger>' + rest.map(f => `<div class="feat-cell">
        <div class="c-ic">${ICON[f.icon]}</div>
        <h3>${tt(f.title)}</h3>
        <p>${tt(f.desc)}</p>
      </div>`).join('') + '</div>';
    wrap.innerHTML = html;
  }

  function renderSteps() {
    const track = document.getElementById('howTrack');
    if (!track) return;
    const intro = track.querySelector('.how-intro');
    track.querySelectorAll('.how-step').forEach(n => n.remove());
    STEPS.forEach(s => {
      const el = document.createElement('div');
      el.className = 'how-step';
      el.innerHTML = `<div class="how-card">
        <div>
          <div class="idx display">${s.idx}</div>
          <h3 class="display">${tt(s.title)}</h3>
          <p>${tt(s.desc)}</p>
        </div>
        <div class="panel"><div class="big-ic">${ICON[s.icon]}</div></div>
      </div>`;
      track.appendChild(el);
    });
  }

  function renderTemplates() {
    const row = document.getElementById('tplRow');
    if (!row) return;
    row.innerHTML = TEMPLATES.map(t => {
      const rows = t.rows.map((r,i) => `<div class="pv-l"><span>${r[0]}</span><span class="p">${r[1]}</span></div>${i<t.rows.length-1?'<div class="pv-r"></div>':''}`).join('');
      const badge = t.badge === 'free'
        ? `<span class="badge free">${lang==='ar'?'مجاني':'Free'}</span>`
        : `<span class="badge prem">${lang==='ar'?'مميّز':'Premium'}</span>`;
      return `<div class="tpl-card reveal">
        <div class="tpl-prev ${t.style}">${badge}<div class="pv-rest">${t.rest}</div>${rows}</div>
        <div class="tpl-meta">
          <div><div class="nm">${t.name}</div><div class="tg">${tt(t.tag)}</div></div>
          <div class="pr"><div class="a">${tt(t.price)}</div><div class="u">${t.badge==='free'?(lang==='ar'?'يبدأ':'to start'):(lang==='ar'?'شهرياً':'/ month')}</div></div>
        </div>
      </div>`;
    }).join('');
  }

  function renderFaq() {
    const box = document.getElementById('faqList');
    if (!box) return;
    box.innerHTML = FAQ.map(f => `<div class="faq-item">
      <button class="faq-q"><span>${tt(f.q)}</span><span class="pm"></span></button>
      <div class="faq-a"><div class="inner">${tt(f.a)}</div></div>
    </div>`).join('');
    box.querySelectorAll('.faq-q').forEach(btn => {
      btn.addEventListener('click', () => {
        const item = btn.parentElement;
        const ans = item.querySelector('.faq-a');
        const open = item.classList.contains('open');
        box.querySelectorAll('.faq-item.open').forEach(o => {
          if (o !== item) { o.classList.remove('open'); animateHeight(o.querySelector('.faq-a'), 0); }
        });
        if (open) { item.classList.remove('open'); animateHeight(ans, 0); }
        else { item.classList.add('open'); animateHeight(ans, ans.querySelector('.inner').offsetHeight); }
      });
    });
  }
  function animateHeight(el, h) {
    if (window.gsap) gsap.to(el, { height: h, duration: .45, ease: 'power3.inOut' });
    else el.style.height = h ? 'auto' : '0';
  }

  /* ---------- i18n text swap ---------- */
  function applyStatic() {
    document.querySelectorAll('[data-en]').forEach(el => {
      const v = el.getAttribute('data-' + lang);
      if (v != null) el.innerHTML = v;
    });
    document.querySelectorAll('[data-en-ph]').forEach(el => {
      const v = el.getAttribute('data-' + lang + '-ph');
      if (v != null) el.setAttribute('placeholder', v);
    });
    document.querySelectorAll('[data-key]').forEach(el => {
      const k = el.getAttribute('data-key').split('.');
      let o = T; k.forEach(p => o = o && o[p]); if (o) el.textContent = tt(o);
    });
  }

  function setLang(l, opts) {
    lang = l;
    try { localStorage.setItem('qayema-lang', l); } catch(e){}
    const html = document.documentElement;
    html.setAttribute('lang', l);
    html.setAttribute('dir', l === 'ar' ? 'rtl' : 'ltr');
    document.querySelectorAll('.seg.lang button').forEach(b => b.classList.toggle('on', b.dataset.l === l));
    applyStatic();
    renderFeatures(); renderSteps(); renderTemplates(); renderFaq();
    if (!opts || !opts.silent) {
      playHeroSwap();
      initReveals();
      initHowPin();
      if (window.ScrollTrigger) ScrollTrigger.refresh();
    }
  }

  /* ---------- Theme ---------- */
  function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem('qayema-theme', t); } catch(e){}
  }

  /* ================= GSAP choreography ================= */
  let revealsInit = false;
  function initReveals() {
    if (!window.gsap || !window.ScrollTrigger) {
      document.documentElement.classList.add('no-gsap'); return;
    }
    // generic reveals
    gsap.utils.toArray('.reveal').forEach(el => {
      if (el._rv) el._rv.kill();
      gsap.set(el, { opacity: 0, y: 28 });
      el._rv = gsap.to(el, {
        opacity: 1, y: 0, duration: .9, ease: 'power3.out',
        scrollTrigger: { trigger: el, start: 'top 86%', once: true }
      });
    });
    // staggered groups
    gsap.utils.toArray('[data-stagger]').forEach(group => {
      const kids = group.children;
      gsap.set(kids, { opacity: 0, y: 30 });
      gsap.to(kids, {
        opacity: 1, y: 0, duration: .8, ease: 'power3.out', stagger: .09,
        scrollTrigger: { trigger: group, start: 'top 82%', once: true }
      });
    });
  }

  // Hero reveal — clean per-line mask stagger. Keeps word spacing and the gold
  // gradient perfectly intact (SplitText mangled both, so it's intentionally avoided).
  function playHeroSwap() {
    if (!window.gsap) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const lines = document.querySelector('.hero-center h1');
    if (!lines) return;
    /* re-trigger the CSS entrance on language swap */
    lines.style.animation = 'none'; void lines.offsetWidth; lines.style.animation = '';
  }

  function initHero() {
    /* Hero entrance is handled by CSS keyframes (see .hero-center children +
       .hero-shot) so visibility never depends on the JS timeline. */
  }

  function drawQR() {
    if (!window.gsap) return;
    gsap.to('.qr-card .qr i', { opacity: 1, duration: .02, stagger: { each: .012, from: 'random' } });
  }
  function buildQR() {
    const qr = document.querySelector('.qr-card .qr');
    if (!qr) return;
    // pseudo-random but fixed pattern, 11x11, with finder corners
    const N = 11; let html = '';
    const finder = (r,c) => (r<3&&c<3)||(r<3&&c>N-4)||(r>N-4&&c<3);
    const seed = [1,0,1,1,0,1,0,0,1,1,0,1,0,1,0,1,1,0,0,1,0,1,1,0];
    let k=0;
    for (let r=0;r<N;r++) for (let c=0;c<N;c++) {
      let on;
      if (finder(r,c)) { const lr=r%((r<3)?3:1), lc=c%3; on = !( (r===1||r===N-2) && false ); on = !((r%4===1)&&(c%4===1)); on = !( ( (r===1)||(r===N-2) ) && ( (c===1)||(c===N-2) ) ); on = (r===0||r===2||c===0||c===2||r===N-1||r===N-3||c===N-1||c===N-3) ? true : false; on = finderCell(r,c,N); }
      else { on = seed[(k++)%seed.length] === 1; }
      html += `<i style="opacity:0;${on?'':'visibility:hidden'}"></i>`;
    }
    qr.innerHTML = html;
  }
  function finderCell(r,c,N){
    const inBox = (br,bc)=> (r>=br&&r<br+3&&c>=bc&&c<bc+3);
    const ring = (br,bc)=> inBox(br,bc) && (r===br||r===br+2||c===bc||c===bc+2);
    if (r<3&&c<3) return ring(0,0);
    if (r<3&&c>N-4) return ring(0,N-3);
    if (r>N-4&&c<3) return ring(N-3,0);
    return false;
  }

  let howTween = null;
  function initHowPin() {
    if (!window.gsap || !window.ScrollTrigger) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const track = document.getElementById('howTrack');
    const section = document.getElementById('how');
    if (!track || !section) return;
    // tear down any previous instance (e.g. on language switch) so width +
    // direction are recomputed for the new layout
    if (howTween) { if (howTween.scrollTrigger) howTween.scrollTrigger.kill(); howTween.kill(); howTween = null; }
    gsap.set(track, { x: 0 });
    const dir = (lang === 'ar') ? 1 : -1;
    const getScroll = () => Math.max(0, track.scrollWidth - window.innerWidth);
    const bar = section.querySelector('.how-progress i');
    howTween = gsap.to(track, {
      x: () => dir * getScroll(),
      ease: 'none',
      scrollTrigger: {
        trigger: section,
        start: 'top top',
        end: () => '+=' + getScroll(),
        pin: true,
        scrub: 1,
        invalidateOnRefresh: true,
        onUpdate: (self) => { if (bar) bar.style.width = (self.progress * 100) + '%'; }
      }
    });
    return howTween;
  }

  function initCounters() {
    if (!window.gsap || !window.ScrollTrigger) return;
    gsap.utils.toArray('[data-count]').forEach(el => {
      const end = parseFloat(el.getAttribute('data-count'));
      const dec = (el.getAttribute('data-dec') === '1');
      const suf = el.getAttribute('data-suf') || '';
      const obj = { v: 0 };
      ScrollTrigger.create({
        trigger: el, start: 'top 88%', once: true,
        onEnter: () => gsap.to(obj, {
          v: end, duration: 1.8, ease: 'power2.out',
          onUpdate: () => { el.textContent = (dec ? obj.v.toFixed(1) : Math.round(obj.v).toLocaleString()) + suf; }
        })
      });
    });
  }

  function initMagnetic() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (!window.gsap) return;
    document.querySelectorAll('[data-magnetic]').forEach(el => {
      const strength = 0.35;
      el.addEventListener('mousemove', (e) => {
        const r = el.getBoundingClientRect();
        const x = (e.clientX - r.left - r.width/2) * strength;
        const y = (e.clientY - r.top - r.height/2) * strength;
        gsap.to(el, { x, y, duration: .5, ease: 'power3.out' });
      });
      el.addEventListener('mouseleave', () => gsap.to(el, { x: 0, y: 0, duration: .6, ease: 'elastic.out(1,.4)' }));
    });
  }

  function initUnderlines() {
    if (!window.gsap || !window.ScrollTrigger) return;
    gsap.utils.toArray('.underline-draw').forEach(el => {
      gsap.fromTo(el, { '--x': 0 }, {
        duration: 1, ease: 'power3.inOut',
        scrollTrigger: { trigger: el, start: 'top 80%', once: true },
        onStart: () => el.classList.add('drawn')
      });
    });
  }

  /* ---------- Nav scroll state ---------- */
  function initNav() {
    const nav = document.querySelector('.nav');
    const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 30);
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Lenis smooth scroll ---------- */
  function initLenis() {
    if (!window.Lenis || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return null;
    const lenis = new Lenis({ duration: 1.1, easing: (t)=>Math.min(1,1.001-Math.pow(2,-10*t)), smoothWheel: true });
    function raf(time){ lenis.raf(time); requestAnimationFrame(raf); }
    requestAnimationFrame(raf);
    if (window.ScrollTrigger) {
      lenis.on('scroll', ScrollTrigger.update);
    }
    // anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', (e) => {
        const id = a.getAttribute('href'); if (id.length < 2) return;
        const target = document.querySelector(id); if (!target) return;
        e.preventDefault(); lenis.scrollTo(target, { offset: -60 });
      });
    });
    return lenis;
  }

  /* ---------- Boot ---------- */
  function boot() {
    buildQR();
    setLang(lang, { silent: true });

    // toggles
    document.querySelectorAll('.seg.lang button').forEach(b => {
      b.addEventListener('click', () => setLang(b.dataset.l));
    });
    const tog = document.getElementById('themeTog');
    if (tog) tog.addEventListener('click', () => {
      const cur = document.documentElement.getAttribute('data-theme');
      setTheme(cur === 'dark' ? 'light' : 'dark');
    });

    initNav();

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (window.gsap && window.ScrollTrigger) {
      gsap.registerPlugin(ScrollTrigger);
      if (window.SplitText) { try { gsap.registerPlugin(SplitText); } catch(e){} }
      initLenis();
      if (!reduced) {
        initHero();
        initHowPin();
        initUnderlines();
        initMagnetic();
      } else {
        document.documentElement.classList.add('no-gsap');
      }
      initReveals();
      initCounters();
      // refresh after fonts/layout
      window.addEventListener('load', () => ScrollTrigger.refresh());
      setTimeout(() => ScrollTrigger.refresh(), 600);
    } else {
      document.documentElement.classList.add('no-gsap');
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
