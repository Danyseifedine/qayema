# QR studio

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

The owner's QR code: a plain black-on-white code by default, customizable from
the dashboard's QR page. The link it encodes (`/{slug}?qr=1`) **never changes
with the design**, so a printed code keeps working whatever is saved.

- Styling, the logo and the printable card are gated on `qr_studio` (Premium
  and Custom); the scan counts follow `analytics`. Should one package ever get
  only part of the studio, give each editor group (colours, shapes, logo, card)
  its own flag then.
- `restaurants.qr_settings` is a **flat design we own** (`dot_style`,
  `dot_color`, `dot_gradient`, …), not the drawing library's option tree.
  `Restaurant::qrDesign()` merges it over `qrDefaultDesign()` (the simple QR)
  and drops any key the defaults no longer know.
- `App\Services\Qr\QrStyle::options()` is the one place a design becomes
  `qr-code-styling` options. The dashboard mirrors it in
  `src/features/qr/utils/qr-options.ts`; both are tested against the same
  cases (`tests/Unit/Services/Qr/QrStyleTest.php`); change one, change both.
  Hex checks and the ink-on-a-colour rule live in `App\Support\Color`. The allowed shapes are QrStyle's constants, which
  `QrSettingsRequest` validates against.
- Drawn by `qr-code-styling` 1.9.2 everywhere: npm in the dashboard, and a
  vendored copy at `public/js/qr-code-styling.js` for the printable card at
  `/{slug}/qr` and the menu's own "Scan to open this menu" pop-up. The pop-up
  fetches its options from `GET /{slug}/qr-options` on first open (the logo is
  inlined, too heavy for every menu page): the studio design, or the plain
  code without the studio, exactly as the dashboard shows it. Every code
  encodes `Restaurant::qrUrl()` (`?qr=1`, which counts the visit as a scan).
- The logo is sent **inline as a data URL** (`QrStyle::logoDataUrl()`), not as
  its CDN link: a browser only draws a cross-origin image into a PNG when that
  domain sends CORS headers, and R2 does not by default.
- A logo raises error correction to H. Tested decoding showed red corner
  centres (#EA4335, ~3.9:1 on white) stop a reader finding the code, so the
  dashboard warns below **4.5:1** for every colour against the background.
