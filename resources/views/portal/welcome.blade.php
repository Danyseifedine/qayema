@extends('portal.layout.master.master')

@section('content')

  {{-- ===== HERO ===== --}}
  <header class="hero">
    <span class="hero-blob b1"></span>
    <span class="hero-blob b2"></span>
    <span class="hero-blob b3"></span>

    <div class="wrap hero-center">
      <h1 class="display" data-en="Step into the <span class='it'>future</span><br>of menus: Human + <span class='it'>AI</span>." data-ar="ادخل إلى <span class='it'>مستقبل</span> القوائم:<br>إنسان + <span class='it'>ذكاء</span>.">Step into the <span class="it">future</span><br>of menus: Human + <span class="it">AI</span>.</h1>
      <p class="hero-sub" data-en="Photograph your menu, let AI rebuild it bilingually, and go live with one QR — all in one Arabic-first platform." data-ar="صوّر قائمتك، ودع الذكاء الاصطناعي يعيد بناءها بلغتين، وانطلق برمز QR واحد — منصّة واحدة عربية أولاً.">Photograph your menu, let AI rebuild it bilingually, and go live with one QR — all in one Arabic-first platform.</p>

      <form class="hero-form" action="{{ route('register') }}" method="GET">
        <input type="email" data-en-ph="Enter your restaurant email" data-ar-ph="أدخل بريد مطعمك" placeholder="Enter your restaurant email" />
        <button type="submit" data-en="Get started free" data-ar="ابدأ مجاناً">Get started free</button>
      </form>

      <div class="hero-rating">
        <span class="hero-stars">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9z"/></svg>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9z"/></svg>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9z"/></svg>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9z"/></svg>
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9z"/></svg>
        </span>
        <span class="sep"></span>
        <span data-en="Loved by restaurants from <b>Riyadh to Lisbon</b>" data-ar="موثوق به في مطاعم من <b>الرياض إلى لشبونة</b>">Loved by restaurants from <b>Riyadh to Lisbon</b></span>
      </div>
    </div>

    <div class="hero-shot">
      <div class="browser">
        <div class="bw-bar">
          <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
          <span class="url">app.qayema.io/menu</span>
        </div>
        <div class="bw-body">
          <iframe src="{{ asset('landing/qayema-dashboard.html') }}" title="Qayema dashboard" loading="lazy" scrolling="no" tabindex="-1" aria-hidden="true"></iframe>
        </div>
      </div>
    </div>
  </header>

  {{-- ===== PROBLEM ===== --}}
  <section class="sec" id="problem">
    <div class="wrap">
      <div class="sec-head reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label" data-en="The problem" data-ar="المشكلة">The problem</span></div>
        <h2 class="display"><span data-en="Paper menus are" data-ar="القوائم الورقية">Paper menus are</span> <span class="gold-text" data-en="quietly costing you." data-ar="تكلّفك بصمت.">quietly costing you.</span></h2>
      </div>
      <div class="prob-grid" data-stagger>
        <div class="prob-card">
          <div class="num display">01</div>
          <h3 data-en="Always out of date" data-ar="دائماً قديمة">Always out of date</h3>
          <p data-en="A price change or a sold-out dish means a stale PDF — or another trip to the printer." data-ar="تغيير سعر أو نفاد طبق يعني ملفاً قديماً — أو رحلة جديدة للمطبعة.">A price change or a sold-out dish means a stale PDF — or another trip to the printer.</p>
          <div class="strike"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="6" y="3" width="12" height="18" rx="1"/><path d="M9 8h6M9 12h6M9 16h3"/><line x1="4" y1="21" x2="20" y2="3" stroke-width="2"/></svg></div>
        </div>
        <div class="prob-card">
          <div class="num display">02</div>
          <h3 data-en="Reprint costs add up" data-ar="تكاليف الطباعة تتراكم">Reprint costs add up</h3>
          <p data-en="Every seasonal update, every typo, every new dish — printed, laminated, distributed, repeat." data-ar="كل تحديث موسمي، كل خطأ، كل طبق جديد — طباعة وتغليف وتوزيع، وتتكرر.">Every seasonal update, every typo, every new dish — printed, laminated, distributed, repeat.</p>
          <div class="strike"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/><line x1="4" y1="21" x2="20" y2="3" stroke-width="2"/></svg></div>
        </div>
        <div class="prob-card">
          <div class="num display">03</div>
          <h3 data-en="Pinch, zoom, give up" data-ar="تكبير، تصغير، استسلام">Pinch, zoom, give up</h3>
          <p data-en="A blurry photo of a menu helps no one — and tells you nothing about what guests actually want." data-ar="صورة ضبابية لقائمة لا تفيد أحداً — ولا تخبرك بما يريده الضيوف فعلاً.">A blurry photo of a menu helps no one — and tells you nothing about what guests actually want.</p>
          <div class="strike"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/><line x1="3" y1="21" x2="21" y2="3" stroke-width="2"/></svg></div>
        </div>
      </div>
    </div>
  </section>

  {{-- ===== SOLUTION ===== --}}
  <section class="sec" id="solution" style="padding-top:0;">
    <div class="wrap sol">
      <div class="sol-vis reveal">
        <div class="sol-ring">
          <div class="core">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M21 21v-4M17 21h1M14 21h.01"/></svg>
          </div>
          <div class="sol-orb" style="top:-6%;left:42%"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="7" y="2.5" width="10" height="19" rx="3"/></svg></div>
          <div class="sol-orb" style="top:42%;right:-6%"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg></div>
          <div class="sol-orb" style="bottom:-6%;left:42%"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4v16h16M8 14l3-4 3 2 4-6"/></svg></div>
          <div class="sol-orb" style="top:42%;left:-6%"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8a2 2 0 0 1 2-2h2l1.5-2h7L17 6h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="12" cy="12.5" r="3.5"/></svg></div>
        </div>
      </div>
      <div class="sol-copy reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label" data-en="The solution" data-ar="الحل">The solution</span></div>
        <h2 class="display" style="font-size:clamp(30px,3.6vw,48px);line-height:1.05;letter-spacing:-0.03em;"><span data-en="One living menu," data-ar="قائمة واحدة حيّة،">One living menu,</span> <span class="gold-text" data-en="one QR." data-ar="خلف رمز واحد.">one QR.</span></h2>
        <ul class="sol-list">
          <li><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 6L9 17l-5-5"/></svg></span><div><h4 data-en="Always current" data-ar="محدّثة دائماً">Always current</h4><p data-en="Change a price or hide a dish — it's live everywhere in an instant." data-ar="غيّر سعراً أو أخفِ طبقاً — يظهر فوراً في كل مكان.">Change a price or hide a dish — it's live everywhere in an instant.</p></div></li>
          <li><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="7" y="2.5" width="10" height="19" rx="3"/></svg></span><div><h4 data-en="Beautiful on every phone" data-ar="أنيقة على كل هاتف">Beautiful on every phone</h4><p data-en="Editorial templates that load fast and read perfectly, big or small." data-ar="قوالب أنيقة تُحمّل بسرعة وتُقرأ بوضوح، صغيرة كانت أم كبيرة.">Editorial templates that load fast and read perfectly, big or small.</p></div></li>
          <li><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></span><div><h4 data-en="The QR never breaks" data-ar="الرمز لا ينكسر">The QR never breaks</h4><p data-en="Print it once. Switch templates, edit forever — the same code keeps working." data-ar="اطبعه مرة. بدّل القوالب وعدّل للأبد — الرمز نفسه يستمر.">Print it once. Switch templates, edit forever — the same code keeps working.</p></div></li>
        </ul>
      </div>
    </div>
  </section>

  {{-- ===== FEATURES ===== --}}
  <section class="sec" id="features" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label" data-en="Everything inside" data-ar="كل ما تحتاجه">Everything inside</span></div>
        <h2 class="display"><span data-en="Built to run" data-ar="مصمّمة لإدارة">Built to run</span> <span class="gold-text" data-en="the whole menu." data-ar="القائمة بالكامل.">the whole menu.</span></h2>
        <p data-en="From AI scanning to live analytics — one calm place to manage how your restaurant looks to every guest." data-ar="من المسح بالذكاء الاصطناعي إلى التحليلات المباشرة — مكان واحد هادئ لإدارة صورة مطعمك أمام كل ضيف.">From AI scanning to live analytics — one calm place to manage how your restaurant looks to every guest.</p>
      </div>
      <div class="features-wrap" id="featuresBox"></div>
    </div>
  </section>

  {{-- ===== HOW IT WORKS (pinned horizontal) ===== --}}
  <section class="how" id="how">
    <div class="pin-wrap">
      <div class="how-track" id="howTrack">
        <div class="how-intro">
          <span class="mono-label" data-en="How it works" data-ar="كيف يعمل">How it works</span>
          <h2 class="display"><span data-en="From paper to" data-ar="من الورق إلى">From paper to</span> <span class="gold-text" data-en="QR, in four moves." data-ar="رمز QR، بأربع خطوات.">QR, in four moves.</span></h2>
          <p data-en="Scroll to follow the journey — sign up, build, get your code, and watch it work." data-ar="مرّر لتتابع الرحلة — سجّل، ابنِ، احصل على رمزك، وراقب النتائج.">Scroll to follow the journey — sign up, build, get your code, and watch it work.</p>
        </div>
        {{-- steps injected by qayema-app-landing.js --}}
      </div>
    </div>
    <div class="how-progress"><i></i></div>
  </section>

  {{-- ===== STATS ===== --}}
  <section class="sec" id="stats" style="padding:90px 0;">
    <div class="wrap">
      <div class="stats reveal">
        <div class="stat"><div class="v"><span class="gold-text" data-count="31" data-suf="s">0s</span></div><div class="k" data-en="Avg. menu build time" data-ar="متوسط زمن بناء القائمة">Avg. menu build time</div></div>
        <div class="stat"><div class="v"><span data-count="14">0</span></div><div class="k" data-en="Languages supported" data-ar="لغة مدعومة">Languages supported</div></div>
        <div class="stat"><div class="v"><span data-count="0" data-dec="1">0</span><span class="gold-text" style="font-family:var(--font-display)">×</span></div><div class="k" data-en="Reprints required" data-ar="إعادة طباعة مطلوبة">Reprints required</div></div>
        <div class="stat"><div class="v"><span data-count="99.9" data-dec="1">0</span><span class="gold-text" style="font-family:var(--font-display)">%</span></div><div class="k" data-en="QR uptime" data-ar="جهوزية الرمز">QR uptime</div></div>
      </div>
    </div>
  </section>

  {{-- ===== PRICING ===== --}}
  <section class="sec" id="pricing" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal" style="text-align:center;max-width:680px;margin-inline:auto;">
        <div class="eyebrow" style="justify-content:center;"><span class="bar"></span><span class="mono-label" data-en="Pricing" data-ar="الأسعار">Pricing</span></div>
        <h2 class="display"><span data-en="Templates" data-ar="القوالب">Templates</span> <span class="gold-text" data-en="are the plans." data-ar="هي الباقات.">are the plans.</span></h2>
        <p style="margin-inline:auto;" data-en="Launch on a free template forever. Subscribe to a premium template only when you want its design — monthly, 6-month, or yearly." data-ar="انطلق بقالب مجاني للأبد. اشترك في قالب مميّز فقط عندما تريد تصميمه — شهرياً أو نصف سنوي أو سنوي.">Launch on a free template forever. Subscribe to a premium template only when you want its design — monthly, 6-month, or yearly.</p>
      </div>

      <div class="price-grid reveal">
        <div class="plan">
          <div class="tier" data-en="Free template" data-ar="قالب مجاني">Free template</div>
          <div class="amt"><span class="big display gold-text" data-en="$0" data-ar="٠">$0</span><span class="per" data-en="forever" data-ar="للأبد">forever</span></div>
          <p class="pdesc" data-en="Everything you need to take one beautiful menu live." data-ar="كل ما تحتاجه لإطلاق قائمة أنيقة واحدة.">Everything you need to take one beautiful menu live.</p>
          <ul>
            <li>__C__<span data-en="A free editorial template" data-ar="قالب أنيق مجاني">A free editorial template</span></li>
            <li>__C__<span data-en="Custom QR code" data-ar="رمز QR مخصّص">Custom QR code</span></li>
            <li>__C__<span data-en="Arabic &amp; English" data-ar="عربي وإنجليزي">Arabic &amp; English</span></li>
            <li>__C__<span data-en="WhatsApp ordering" data-ar="الطلب عبر واتساب">WhatsApp ordering</span></li>
          </ul>
          <a class="btn btn-line" data-magnetic href="{{ route('register') }}" data-en="Start free" data-ar="ابدأ مجاناً">Start free</a>
        </div>

        <div class="plan hot">
          <div class="tier" data-en="Premium template" data-ar="قالب مميّز">Premium template</div>
          <div class="amt"><span class="big display gold-text" data-en="$12" data-ar="٤٥ ر.س">$12</span><span class="per" data-en="/ month · per template" data-ar="شهرياً · لكل قالب">/ month · per template</span></div>
          <p class="pdesc" data-en="Subscribe to a premium design. Monthly, 6-month or yearly." data-ar="اشترك في تصميم مميّز. شهرياً أو نصف سنوي أو سنوي.">Subscribe to a premium design. Monthly, 6-month or yearly.</p>
          <ul>
            <li>__C__<span data-en="Everything in Free" data-ar="كل مزايا المجاني">Everything in Free</span></li>
            <li>__C__<span data-en="Premium editorial templates" data-ar="قوالب مميّزة أنيقة">Premium editorial templates</span></li>
            <li>__C__<span data-en="Live analytics dashboard" data-ar="لوحة تحليلات مباشرة">Live analytics dashboard</span></li>
            <li>__C__<span data-en="Variants, sizes &amp; ingredients" data-ar="أحجام ومكوّنات وخيارات">Variants, sizes &amp; ingredients</span></li>
            <li>__C__<span data-en="Switch templates anytime" data-ar="بدّل القوالب متى شئت">Switch templates anytime</span></li>
          </ul>
          <a class="btn btn-gold" data-magnetic href="{{ route('register') }}" data-en="Choose premium" data-ar="اختر المميّز">Choose premium</a>
        </div>
      </div>
      <p class="price-note reveal"><b data-en="Your menu is safe." data-ar="قائمتك بأمان.">Your menu is safe.</b> <span data-en="If a subscription ends, your menu simply pauses — it is never deleted." data-ar="إذا انتهى الاشتراك، تتوقف قائمتك مؤقتاً فقط — ولا تُحذف أبداً.">If a subscription ends, your menu simply pauses — it is never deleted.</span></p>
    </div>
  </section>

  {{-- ===== TESTIMONIALS ===== --}}
  <section class="sec" id="stories" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label" data-en="Loved by restaurants" data-ar="مطاعم تحبّها">Loved by restaurants</span></div>
        <h2 class="display"><span data-en="From paper to" data-ar="من الورق إلى">From paper to</span> <span class="gold-text" data-en="praised, fast." data-ar="الإعجاب، بسرعة.">praised, fast.</span></h2>
      </div>
      <div class="quotes" data-stagger>
        <div class="quote lead">
          <div><div class="mk">"</div><p class="qt" data-en="We photographed our menu on a Tuesday and were taking QR orders by dinner. Updates that used to take a week now take ten seconds." data-ar="صوّرنا قائمتنا يوم الثلاثاء وكنّا نستقبل الطلبات بالرمز عند العشاء. تحديثات كانت تأخذ أسبوعاً صارت بعشر ثوانٍ.">We photographed our menu on a Tuesday and were taking QR orders by dinner. Updates that used to take a week now take ten seconds.</p></div>
          <div class="by"><span class="av display">L</span><div><div class="nm" data-en="Layla Othman" data-ar="ليلى عثمان">Layla Othman</div><div class="rl" data-en="Owner · Maison Aran" data-ar="مالكة · ميزون أران">Owner · Maison Aran</div></div></div>
        </div>
        <div class="quote">
          <div><div class="mk">"</div><p class="qt" data-en="Finally, our Arabic menu looks as good as the English one." data-ar="أخيراً، قائمتنا العربية تبدو بجمال الإنجليزية.">Finally, our Arabic menu looks as good as the English one.</p></div>
          <div class="by"><span class="av display">R</span><div><div class="nm" data-en="Reem Al-Saud" data-ar="ريم آل سعود">Reem Al-Saud</div><div class="rl" data-en="Concept lead · Sabor" data-ar="مديرة المفهوم · سابور">Concept lead · Sabor</div></div></div>
        </div>
        <div class="quote">
          <div><div class="mk">"</div><p class="qt" data-en="The analytics told us which dishes to push. Revenue followed." data-ar="أخبرتنا التحليلات بالأطباق التي نروّجها. وتبعها الدخل.">The analytics told us which dishes to push. Revenue followed.</p></div>
          <div class="by"><span class="av display">M</span><div><div class="nm" data-en="Marco Vidale" data-ar="ماركو فيدالي">Marco Vidale</div><div class="rl" data-en="GM · Olea &amp; Co." data-ar="مدير عام · أوليا">GM · Olea &amp; Co.</div></div></div>
        </div>
      </div>
    </div>
  </section>

  {{-- ===== FAQ ===== --}}
  <section class="sec" id="faq" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal" style="text-align:center;max-width:640px;margin-inline:auto;">
        <div class="eyebrow" style="justify-content:center;"><span class="bar"></span><span class="mono-label" data-en="Questions" data-ar="أسئلة">Questions</span></div>
        <h2 class="display"><span data-en="Good to" data-ar="من الجيد أن">Good to</span> <span class="gold-text" data-en="know." data-ar="تعرف.">know.</span></h2>
      </div>
      <div class="faq" id="faqList"></div>
    </div>
  </section>

  {{-- ===== FINAL CTA ===== --}}
  <section class="final">
    <div class="wrap">
      <div class="final-box reveal">
        <img class="qmark" src="{{ asset('images/logo/q-logo.png') }}" alt="" />
        <h2 class="display"><span data-en="Turn your menu into a" data-ar="حوّل قائمتك إلى">Turn your menu into a</span> <span class="gold-text" data-en="QR experience today." data-ar="تجربة QR اليوم.">QR experience today.</span></h2>
        <p data-en="Photograph it, let AI design it, and share one QR your guests will love — in both languages." data-ar="صوّرها، دع الذكاء الاصطناعي يصمّمها، وشارك رمز QR واحداً سيحبّه ضيوفك — باللغتين.">Photograph it, let AI design it, and share one QR your guests will love — in both languages.</p>
        <div class="acts">
          <a class="btn btn-gold" data-magnetic href="{{ route('register') }}" data-en="Get started free" data-ar="ابدأ مجاناً">Get started free</a>
          <a class="btn btn-line" href="#how" data-en="See how it works" data-ar="شاهد كيف يعمل">See how it works</a>
        </div>
      </div>
    </div>
  </section>

@endsection

@push('scripts')
<script>
  // inject check icons into pricing lists (keeps markup tidy)
  document.querySelectorAll('.plan li').forEach(function (li) {
    li.innerHTML = li.innerHTML.replace('__C__', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>');
  });
</script>
@endpush
