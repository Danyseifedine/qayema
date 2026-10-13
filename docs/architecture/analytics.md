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
  needs `analytics` (Pro and up), ranges `today`/`7d`/`30d` (no range sent
  is `30d`, for the phone app; the dashboard opens on `today`);
  `advanced()` (`GET /api/analytics/advanced`) and the `90d`/`all` ranges
  need `advanced_analytics` (Premium and up). `today` runs from the
  restaurant's midnight, charts one point an hour so far
  (`2026-10-13T14:00`), and compares with yesterday up to the same time;
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
- **History** (`menu_days`, model `MenuDay`, `App\Services\Analytics\MenuDays`):
  `stats:rollup` (nightly, 03:10) first adds up every finished day of every
  menu into one row per day of its own (its timezone then): views, QR scans,
  views per local hour, languages, devices, browsers, systems, every action
  count, dishes added and categories opened by id, searches and missed
  searches, and visitors and carted visitors as HyperLogLog sketches
  (`App\Support\HyperLogLog`, 4,096 registers, about 1% off, compressed to
  well under 2 KB). Then it deletes raw `menu_sessions` and `menu_events`
  older than 6 months; if adding up throws, nothing is deleted. A day is
  added up once, never again.
- "All time" (`MenuStats`, with `trafficTotals()` and `qrScans()` on the
  restaurant) reads `menu_days` for the days before `MenuDays::boundary()`
  (180 days back, inside the raw window) and the raw rows from then on, so
  no day counts twice; visitors over both merge the sketches. With no day
  added up before the boundary it reads only the raw rows, exactly. The
  shorter ranges (90 days and its comparison included) only ever read raw
  rows. A new number on the Analytics page that comes from raw rows needs
  its field in `menu_days` too, or "All time" loses it past 6 months.
