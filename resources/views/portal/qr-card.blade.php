<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="robots" content="noindex, nofollow" />
<title>{{ $card['settings']['name'] ?? 'Qayema' }} — QR</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&family=El+Messiri:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root { --ease: cubic-bezier(.22,.61,.36,1); }
  * { box-sizing: border-box; }
  html, body { height: 100%; }
  body {
    margin: 0;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #000;
    font-family: 'Geist', 'El Messiri', ui-sans-serif, system-ui, sans-serif;
  }
  .stage { position: relative; display: grid; place-items: center; }
  .glow {
    position: fixed; inset: 0; pointer-events: none;
    background: radial-gradient(55% 45% at 50% 8%, rgba(200,168,90,.16), transparent 70%);
  }
  .qr-card {
    position: relative;
    display: block;
    width: 300px;
    border-radius: 22px;
    padding: 32px 28px;
    text-align: center;
    text-decoration: none;
    box-shadow: 0 40px 80px -32px rgba(0,0,0,.7);
  }
  .qr-card.bg-cream { background: #f6f1e6; color: #15120a; }
  .qr-card.bg-ink   { background: #0c0c0d; color: #f3f1ea; border: 1px solid rgba(255,255,255,.09); }
  .qr-card.bg-gold  { background: linear-gradient(155deg,#3a2c12,#191005); color: #f2ddab; border: 1px solid rgba(242,221,171,.16); }
  .qr-card.bg-olive { background: #1a2113; color: #d3dfba; border: 1px solid rgba(211,223,186,.12); }
  .qr-logo { font-family: 'Instrument Serif', 'El Messiri', Georgia, serif; font-size: 26px; line-height: 1; margin-bottom: 5px; }
  .qr-sub { font-size: 9px; letter-spacing: .22em; text-transform: uppercase; opacity: .7; margin-bottom: 22px; }
  .qr-frame { display: inline-block; padding: 14px; background: #fff; }
  .qr-frame svg { display: block; width: 172px; height: 172px; }
  .qr-cta { margin-top: 20px; font-size: 13px; font-weight: 600; letter-spacing: .02em; display: flex; align-items: center; justify-content: center; gap: 8px; }
  .qr-cta svg { width: 15px; height: 15px; }
  html[dir="rtl"] .qr-cta svg { transform: scaleX(-1); }
  .qr-url { margin-top: 7px; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 10px; opacity: .65; }
</style>
</head>
<body>
  <span class="glow" aria-hidden="true"></span>
  <main class="stage">
    <a class="qr-card bg-{{ $card['settings']['bg'] }}" href="{{ $card['url'] }}">
      <div class="qr-logo">{{ $card['settings']['name'] ?: 'Qayema' }}</div>
      <div class="qr-sub">{{ $card['settings']['tagline'] ?: __('Scan · Browse · Order') }}</div>
      <div class="qr-frame" style="border-radius: {{ ['sharp' => 2, 'round' => 16, 'pill' => 28][$card['settings']['corner']] ?? 16 }}px;">
        <svg id="qr" shape-rendering="{{ $card['settings']['dot_style'] === 'square' ? 'crispEdges' : 'auto' }}"></svg>
      </div>
      <div class="qr-cta">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        {{ $card['settings']['cta'] ?: __('View the menu') }}
      </div>
      @if ($card['settings']['show_url'])
        <div class="qr-url">{{ $card['display_url'] }}</div>
      @endif
    </a>
  </main>

  <script src="{{ asset('js/qrcode-generator.js') }}"></script>
  <script>
    (function () {
      var card = @json($card);
      var s = card.settings;

      var qr = qrcode(0, 'H');
      qr.addData(card.url);
      qr.make();
      var N = qr.getModuleCount();

      function inFinder(r, c) {
        function block(r0, c0) { return r >= r0 && r < r0 + 7 && c >= c0 && c < c0 + 7; }
        return block(0, 0) || block(0, N - 7) || block(N - 7, 0);
      }

      var hole = null;
      if (s.logo === 'image' && card.logo_url) {
        var size = Math.max(5, Math.floor(N / 5) | 1);
        hole = { start: Math.floor((N - size) / 2), size: size };
      }
      function inHole(r, c) {
        return hole && r >= hole.start && r < hole.start + hole.size && c >= hole.start && c < hole.start + hole.size;
      }

      var parts = [];
      for (var r = 0; r < N; r++) {
        for (var c = 0; c < N; c++) {
          if (!qr.isDark(r, c) || inHole(r, c)) continue;
          var fill = inFinder(r, c) ? s.eye : s.dot;
          if (s.dot_style === 'dot') {
            parts.push('<circle cx="' + (c + 0.5) + '" cy="' + (r + 0.5) + '" r="0.42" fill="' + fill + '"/>');
          } else if (s.dot_style === 'rounded') {
            parts.push('<rect x="' + (c + 0.04) + '" y="' + (r + 0.04) + '" width="0.92" height="0.92" rx="0.3" fill="' + fill + '"/>');
          } else {
            parts.push('<rect x="' + c + '" y="' + r + '" width="1" height="1" fill="' + fill + '"/>');
          }
        }
      }

      if (hole) {
        var h = hole, pad = h.size * 0.06, inner = h.size * 0.88;
        parts.push('<defs><clipPath id="lc"><rect x="' + (h.start + pad) + '" y="' + (h.start + pad) + '" width="' + inner + '" height="' + inner + '" rx="' + (h.size * 0.19) + '"/></clipPath></defs>');
        parts.push('<rect x="' + h.start + '" y="' + h.start + '" width="' + h.size + '" height="' + h.size + '" rx="' + (h.size * 0.24) + '" fill="#fff"/>');
        parts.push('<image href="' + card.logo_url + '" x="' + (h.start + pad) + '" y="' + (h.start + pad) + '" width="' + inner + '" height="' + inner + '" preserveAspectRatio="xMidYMid slice" clip-path="url(#lc)"/>');
      }

      var svg = document.getElementById('qr');
      svg.setAttribute('viewBox', '0 0 ' + N + ' ' + N);
      svg.innerHTML = parts.join('');
    })();
  </script>
</body>
</html>
