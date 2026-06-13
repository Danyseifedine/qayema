{{-- ===== Portal footer ===== --}}
@php
    // Section links jump home first when viewed from a non-home portal page.
    $base = request()->is('/') ? '' : '/';
@endphp
<footer>
  <div class="wrap">
    <div class="foot-top">
      <div class="foot-brand">
        <img src="{{ asset('images/logo/logo.svg') }}" alt="Qayema"/>
        <p data-en="The AI menu studio for modern restaurants. Photograph, generate, and serve — one QR, two languages." data-ar="استوديو القوائم بالذكاء الاصطناعي للمطاعم الحديثة. صوّر، أنشئ، وقدّم — رمز واحد، لغتان.">The AI menu studio for modern restaurants. Photograph, generate, and serve — one QR, two languages.</p>
        <div class="seg lang" role="group" aria-label="language">
          <button data-l="ar">ع</button>
          <button data-l="en">EN</button>
        </div>
      </div>
      <div class="foot-col">
        <h5 data-en="Product" data-ar="المنتج">Product</h5>
        <a href="{{ $base }}#features" data-en="Features" data-ar="المميزات">Features</a>
        <a href="{{ $base }}#pricing" data-en="Pricing" data-ar="الأسعار">Pricing</a>
        <a href="{{ $base }}#how" data-en="How it works" data-ar="كيف يعمل">How it works</a>
      </div>
      <div class="foot-col">
        <h5 data-en="Company" data-ar="الشركة">Company</h5>
        <a href="#" data-en="About" data-ar="عن قيمة">About</a>
        <a href="{{ route('contact') }}" data-en="Contact" data-ar="تواصل">Contact</a>
        <a href="{{ $base }}#stories" data-en="Stories" data-ar="قصص">Stories</a>
      </div>
      <div class="foot-col">
        <h5 data-en="Legal" data-ar="قانوني">Legal</h5>
        <a href="{{ route('terms') }}" data-en="Terms" data-ar="الشروط">Terms</a>
        <a href="{{ route('privacy') }}" data-en="Privacy" data-ar="الخصوصية">Privacy</a>
        <a href="{{ route('cookies') }}" data-en="Cookies" data-ar="الكوكيز">Cookies</a>
        <a href="{{ route('refund') }}" data-en="Refund Policy" data-ar="سياسة الاسترجاع">Refund Policy</a>
      </div>
    </div>
    <div class="foot-bot">
      <span class="cp" data-en="© 2026 Qayema. All rights reserved." data-ar="© ٢٠٢٦ قيمة. جميع الحقوق محفوظة.">© 2026 Qayema. All rights reserved.</span>
      <div class="foot-soc">
        <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor"/></svg></a>
        <a href="#" aria-label="X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.1 8.1L23 22h-6.8l-5-6.6L5.5 22H2.3l7.6-8.7L1.5 2h6.9l4.5 6 5.9-6zm-1.2 18h1.9L7.4 4H5.4l12.3 16z"/></svg></a>
        <a href="#" aria-label="WhatsApp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M3 21l1.7-5A8 8 0 1 1 8 19.3z"/></svg></a>
      </div>
    </div>
  </div>
</footer>
