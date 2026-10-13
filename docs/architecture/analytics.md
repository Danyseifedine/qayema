# Analytics

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

- **Visits:** the page reports its own visit (`public/js/menu-track.js`,
  with its first batch to `POST /{slug}/events`, at most 4 s after opening
  or as the guest leaves), so a crawler that runs no script is not counted
  and a page served from a cache still is. A preview loads no tracker, and a
  page reached from the same menu (a language switch) reports no visit.
  `MenuVisitRecorder` writes the `menu_sessions` row with device, browser,
  OS, the `locale` it opened in (one off the menu counts as its main
  language) and `via_qr` from the `?qr=1` the QR codes encode.
- **Who is one guest:** a random id the menu keeps in local storage
  (`qayema-visitor`, stored as `<id>.<day made>` and made anew after 395
  days, as audience counting is expected to), sent as `visitor` with every batch; without one, a
  hash of the IP and user agent (`MenuEventsRequest::visitor()`). It is the
  `session_id` of the visit and of its actions alike. The menu stores no
  session for guests, so the session id is not it.
- **Guest actions:** `public/js/menu-track.js` batches what guests do into
  `POST /{slug}/events` (`throttle:menu-events`, never a ban, since a dining room
  shares one IP) → `MenuEventRecorder` → `menu_events`. Types are
  `App\Enums\MenuEventType`. Links opt in with `data-track="…"` (+
  `data-track-value`); the cart and the menu navigation dispatch a
  `qayema:track` DOM event rather than calling the tracker, so a menu without
  it loses nothing. The recorder drops a dish/category that is not the
  restaurant's, clears fields a type does not carry, and normalises search
  terms. Events share the visit's `session_id` (the visitor id), which is what the funnel counts.
  Recorded on **every** package, so moving up shows history at once. Owner
  previews load no tracker.
- **Reading:** `App\Services\Analytics\MenuStats`. `summary()` (`GET /api/analytics`)
  needs `analytics` (Pro and up), ranges `7d`/`30d`; `advanced()` (`GET /api/analytics/advanced`)
  and the `90d`/`all` ranges need `advanced_analytics` (Premium and up);
  `teaser()` (`GET /api/analytics/teaser`, this week's views) is every package.
  Tests about a feature itself put it on the default package with
  `defaultPackageIncludes()` (`CreatesOwners`); which package has what is
  tested in `tests/Feature/Packages`. Days and hours are the restaurant's `timezone` (UTC when
  unset): rows are grouped by UTC hour with `SUBSTR(ts, 1, 13)` (portable
  across MySQL and SQLite) and shifted in PHP. The funnel (visit → cart → order)
  is null when the package does not take orders. Devices, browsers, systems
  and order money are deliberately **not** reported: the owner decided they
  do not help a restaurant. The
  visit rows still record device/browser/OS.
- `stats:rollup` prunes `menu_sessions` and `menu_events` after 6 months.
