<!DOCTYPE html>
<html lang="en" dir="ltr" data-theme="light">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<x-seo
    :title="$seoTitle ?? 'Qayema — Your restaurant menu, live with one QR'"
    :description="$seoDescription ?? 'Photograph your menu, let AI rebuild it bilingually in Arabic & English, and go live with one custom QR code. The Arabic-first digital menu platform.'" />

<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&family=Noto+Kufi+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('portal/css/app.css') }}">
<link rel="stylesheet" href="{{ asset('portal/css/pages/landing.css') }}">
<link rel="stylesheet" href="{{ asset('portal/css/layout/navbar.css') }}">
<link rel="stylesheet" href="{{ asset('portal/css/layout/footer.css') }}">
@stack('styles')

<script>
  (function(){ try {
    var t = localStorage.getItem('qayema-theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
    var l = localStorage.getItem('qayema-lang') || 'en';
    document.documentElement.setAttribute('lang', l);
    document.documentElement.setAttribute('dir', l === 'ar' ? 'rtl' : 'ltr');
  } catch(e){} })();
</script>
</head>
<body style="font-family: Geist">

  @include('portal.layout.component.navbar')

  @yield('content')

  @include('portal.layout.component.footer')

  {{-- GSAP + Lenis + landing choreography (shared by every portal page) --}}
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
  <script src="{{ asset('portal/js/landing.js') }}"></script>
  @stack('scripts')
</body>
</html>
