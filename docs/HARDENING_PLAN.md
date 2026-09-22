# Backend hardening — progress checklist

Working document. Each step lands green (`php artisan test`) before the next.
Decisions taken (revisit if wrong): drop dead analytics columns; refunds claw
back with a floor at zero; Google-only signup is intended; `default_locale`
stays immutable after onboarding; no account deletion.

## Step 1 — Dead code
- [x] A1 delete `seo_helpers.php`, `ApiResponse` trait, unused model methods, `category` upload context
- [x] A2 drop `restaurants.timezone`, `menu_sessions.{time_spent,page_views,whatsapp_orders}`, `users.email_verified_at`; trim widgets + stats table
- [x] A3 composer (`propaganistas` direct, `breeze`), root `package.json`, dead lang keys, `.env.example`

## Step 2 — Billing & error hygiene
- [x] B2 refund clawback on `TransactionUpdated`
- [x] B3 queue the welcome mail
- [x] B5 consistent JSON error shape for `api/*` (401/403/404/419/422/429/500)
- [x] B8 single login throttle

## Step 3 — Password reset (web + API)
- [x] B1 forgot/reset routes, notification, no-enumeration, expiry

## Step 4 — API for the SPA
- [x] C1 `GET /api/user` shell payload (balance, limits, features, urls)
- [x] C8 `GET /api/stats`
- [x] C9 `PUT /api/password`, `PUT /api/account`
- [x] C7 wallet pagination

## Step 5 — Menu builder conveniences
- [x] C2 template preview `GET /{slug}?preview=`
- [x] C3 `PATCH /dishes/{dish}/availability`, `POST /dishes/{dish}/move`

## Step 6 — Test tooling + unit layer
- [x] D0 `tests/Unit`, shared `CreatesOwners` / `FakesPaddle` traits
- [x] D1 unit tests

## Step 7 — Feature matrix
- [x] D2 API endpoints, six-check matrix + specifics
- [x] D3 web/portal + security

## Step 8 — Admin, journeys, console
- [x] D4 Filament resources, policies, widgets
- [x] D5 journeys
- [x] D6 console + scheduler

## Real bugs the matrix caught (all fixed, all now pinned by a test)
- `ContactController` still called `$this->success()/error()` from a trait removed in Step 1 — the AJAX contact path was a fatal error. (`ContactTest`)
- The reCAPTCHA rule was not implicit, so **omitting** `g-recaptcha-response` skipped the captcha entirely on login and contact. (`ContactTest::captcha_is_enforced_when_enabled`)
- `google_maps_url` accepted `javascript:` / `ftp:` / `data:` schemes. (`SettingsEdgeTest`)
- A Google identity with no email hit the `users.email` NOT NULL constraint (500). (`GoogleLoginEdgeTest`)
- Re-submitting the final onboarding step sent the welcome email again. (`OnboardingEdgeTest`)
- An owner could pick a reserved slug (`admin`, `contact`, …) that the menu route can never serve. (`OnboardingEdgeTest`, `OwnerJourneyTest`)
- `Wallet::balance()` trusted whatever copy of the user the caller held. (`CoinsEdgeTest`)
- Onboarding read a cached `restaurant` relation, so a revisit of step 1 tried to create a second restaurant. (`OnboardingEdgeTest`)
- The admin "create restaurant" form had no uniqueness rule on the owner — a duplicate was a raw DB exception. (`AdminResourcesTest`)
- The `api` limiter's custom 429 was thrown as an `HttpResponseException` and the new JSON error renderer turned it into a 500. (`ErrorShapeTest`)

## Known, accepted
- The Filament panel stack does not run `BlockAbusiveIps`; a banned IP can still reach `/admin/login`, which has its own 5/min limit and admin-only auth.
- `GET /api/csrf-token` without a stateful origin returns an empty token (no session to hand out) — the SPA always sends its origin.
- `composer audit` reports 58 advisories across 19 packages; dependency upgrades were out of scope and need their own pass.
