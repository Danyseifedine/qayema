# The phone apps and admin notifications

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

- **`qayema-app/` (Flutter, Android and iOS)**: the owner's phone app, in
  English and Arabic (`lib/l10n/*.arb`, Flutter's gen-l10n; the phone's
  language unless the owner picks one with the language button, kept on the
  phone). Three onboarding pages (once per phone), the sign-in, then four
  tabs over the owner API, nothing of its own on the server:
  Home (the menu's link to share or show as a QR code, the week's views
  from `/api/analytics` or the teaser without analytics, orders waiting,
  dishes and the package), Menu (dishes by category, the sold-out switch
  through `/availability`, a dish's photo (`/api/uploads/temp`), names per
  menu language, price, category; categories added, renamed, deleted; a
  dish's variants and add-ons are left to the dashboard), Orders (in-menu
  and table orders, the one next step, cancel; `/api/orders/pulse` every 20
  seconds and on return to the app: a banner, a badge) and More
  (restaurant name, logo, phone and opening hours, saved as the whole form
  since `PATCH /api/restaurant` clears what is left out; the QR code;
  language; sign out). Run against the e2e server to try it with test data:
  `--dart-define=API_URL=http://10.0.2.2:8001`.
  It signs in at `POST /api/login` (email or username + password,
  `OwnerLoginRequest`, menu owners only: an admin gets the same "not
  correct") and gets a **Sanctum bearer token** carrying the ability
  `AuthController::TOKEN_ABILITY` (60 days, one per phone). The token opens
  the owner API (`api/*`, the same endpoints the dashboard reaches with its
  session; `/api/user` is who is signed in) while the account is still a
  menu owner (checked on every call: an owner made an admin is shut out), and `POST /api/logout` ends
  that phone's token only. The answers come in the app's language
  (`Accept-Language`).
- **`qayema-admin-app/` (Flutter, Android and iOS)**: the admin's phone app.
  It signs in at `POST /api/admin/login` (email or username + password,
  `AdminLoginRequest`, the website's lockout after five wrong tries, no
  captcha) and gets a **Sanctum bearer token** (60 days, one per phone,
  `personal_access_tokens`). Both apps' sign-ins share `AppLoginRequest`.
  Each app's tokens open its own routes only
  (`AppServiceProvider::keepTokensToTheirApp()`): an admin token opens
  `api/admin/*` and counts as no sign-in on the owner API, and an owner
  token the reverse; anywhere else a token is no sign-in. Every admin route also checks the account is
  still an admin (`EnsureUserIsAdmin`). An owner's account, a wrong password
  and a Google-only admin get the same "not correct" answer.
  What it does (`Api\Admin\*` controllers, `AdminRestaurantResource`,
  requests in `app/Http/Requests/Admin/`): list restaurants
  (`GET /api/admin/restaurants`, search by name, link or owner, filter
  `all|active|inactive|ending|ended|paying`; ending = package in force with an end,
  soonest first; paying = `Restaurant::paying()`), one restaurant, open one with its owner
  (`POST /api/admin/restaurants`: the owner's account and the restaurant
  together, through `OnboardingService::openForOwner()`, which /admin →
  Users → Create also uses), switch a menu on or off
  (`PATCH …/{id}/active`), change its package from today
  (`PUT …/{id}/package`, optional `price_cents` and `pays_with_whish` saved
  after the change, so the price given wins over the assigner clearing it)
  or add time (`POST …/{id}/package/extend`, 422 on a
  package with no end), both through `PackageAssigner`, what it pays
  (`PUT …/{id}/billing`: `price_cents` null or 0 to 10,000,000, null for the
  package's price, and `pays_with_whish`; a plain save, so a new price is a
  history row and the menu cache is left alone), and
  `GET /api/admin/packages`. Months are `PackageAssigner::DURATIONS`, any
  from 1 to 12, added to today (or to the current end when extending); no
  months means no end date. Also: the home screen's numbers
  (`GET /api/admin/summary`, `SummaryController`: restaurants, new in 7
  days (list filter `new`), menus off, ending in 7 days, ended, menu visits
  today since midnight in Beirut, `writing_help_today` and
  `writing_help_month` (from the 1st in Beirut): the AI writing help's uses,
  tokens in and out, cost in dollars, credits and each kind's uses and cost;
  and `income`: `paying`, `paying_whish` and `totals`, a row per currency
  `{currency, cents, whish_cents}`, the most first, from
  `Restaurant::monthlyIncome()`, which the /admin Overview reads too; six
  queries), what the AI
  writing help spent (`GET /api/admin/writing-help?range=today|30d|month`,
  `WritingHelpUsageController`: totals from `WritingHelpLog::totalsSince()`,
  `model` {`name`, `input_per_million`, `output_per_million`} from
  `WritingHelpSetup`/`WritingHelpPrice`, and each restaurant, the most tokens first, `byRestaurantSince()`, at most
  100, with `pays_cents` (its monthly price while it is paying, else null)
  and `currency`; a day is Lebanon's, each restaurant's rows its own days), a restaurant's basics
  (`PATCH /api/admin/restaurants/{id}`: English name with its other
  languages kept, menu link through `AvailableSlug` (the former one keeps
  forwarding), phone), and the owner's password
  (`PUT …/{id}/owner/password`, never an admin's; it clears the
  remember-me token, and the owner's open sessions end on their next
  request). One restaurant (`AdminRestaurantResource::detail()`, every
  answer about one restaurant) also carries its phone with its dial code and
  its visits (`MenuTraffic::totals()`, `qrScans()`) and its writing help
  (`stats.writing_help`, `WritingHelpLog::of()`: uses today, uses and tokens
  over 30 days).
- **Admin phone notifications** (Firebase Cloud Messaging, `kreait/laravel-firebase`,
  Firebase project `qayema`). The app registers its phone after signing in
  (`PUT /api/admin/devices`, table `device_tokens`, one row per phone; a
  phone signed in by another admin moves to them) and removes it on sign-out
  (`DELETE`). `App\Services\Push\PushSender` is the only sender: nothing
  without `FIREBASE_CREDENTIALS` (the service-account JSON, kept in
  git-ignored `storage/app/firebase-credentials.json`; empty in tests and
  e2e), never throws, and deletes a phone Firebase no longer knows.
  `App\Services\Push\AdminAlerts` says what goes out, always naming the
  restaurant, never its owner's name or email: a package request
  (opens the restaurant) or a contact message, from `ContactService` after
  the response (`defer`); a restaurant an owner opens in onboarding
  (`OnboardingService::saveIdentity()`, never one an admin opens); an owner
  editing their menu (`TellAdminsAboutMenuEdits` on the owner API: a
  successful write to `MENU_ROUTES`, not orders, the account or features,
  not an admin signed in as the owner), once an hour at most per
  restaurant (`Cache::add`, `MENU_EDITING_QUIET_MINUTES`); and
  `packages:ending-soon-admin-reminder` at 09:00 Beirut, the
  packages ending in the next 3 days (opens Renewals; needs the scheduler's
  cron). Android channel `admin_alerts` and status icon `ic_stat_qayema`
  live in the app. iOS needs an APNs key and `GoogleService-Info.plist`
  before it gets any.
