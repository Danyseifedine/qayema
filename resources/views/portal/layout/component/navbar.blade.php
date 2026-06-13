{{-- ===== Portal navbar ===== --}}
@php
    // On the home page, section links are same-page anchors (Lenis smooth-scrolls
    // them). On any other portal page they jump home first, then to the section.
    $base = request()->is('/') ? '' : '/';
@endphp
<nav class="nav">
  <div class="nav-in">
    <a class="brand" href="{{ $base }}#top">
        <img src="{{ asset('images/logo/logo.svg') }}" alt="Qayema" />
    </a>
    <div class="nav-links">
      <a href="{{ $base }}#features" data-en="Features" data-ar="المميزات">Features</a>
      <a href="{{ $base }}#how" data-en="How it works" data-ar="كيف يعمل">How it works</a>
      <a href="{{ $base }}#pricing" data-en="Pricing" data-ar="الأسعار">Pricing</a>
      <a href="{{ $base }}#faq" data-en="FAQ" data-ar="الأسئلة">FAQ</a>
    </div>
    <div class="nav-right">
      <div class="seg lang" role="group" aria-label="language">
        <button data-l="ar">ع</button>
        <button data-l="en">EN</button>
      </div>
      <button class="theme-tog" id="themeTog" aria-label="theme">
        <span class="knob">
          <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="moon" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </span>
      </button>
      <a class="btn btn-gold btn-sm" data-magnetic href="{{ route('register') }}" data-en="Get started free" data-ar="ابدأ مجاناً">Get started free</a>
    </div>
  </div>
</nav>
<span id="top"></span>
