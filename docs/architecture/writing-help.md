# Writing help (AI)

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

The dashboard's AI buttons, through the official Laravel AI SDK
(`laravel/ai`): **Translate** on every translatable field (either way, the
open tab's text into the other language or the other's into an empty tab)
and **Write with AI / Improve with AI** on a category's description and on
the restaurant's. `POST /api/writing/translate` (`text`, `from`, `to`: menu
languages, never the same; `max`, the field's limit; `fit`, `category` or
`restaurant` for a description), `POST /api/writing/category-description`
(`name`, `text` to improve or empty, `language`) and
`POST /api/writing/restaurant-description` (`text`, `language`; the name and
the saved menu, up to 12 categories with 6 dishes each, are read by the
server; the menu only inspires the line, the kind of place and its mood, and
is never listed back; an empty menu gets a welcome that suits any
restaurant) answer `{data: {text, left}}`; nothing is saved there, the owner
checks it in the form and saves as usual. `App\Services\Ai\WritingHelp`
holds the calls (one instance a request, AppServiceProvider);
`WritingHelpSetup` says whether they run and on what; the agents are
`App\Ai\Agents\MenuTranslator`, `CategoryDescriptionWriter` and
`RestaurantDescriptionWriter` (structured output, one `text`).

- **Off until set up:** `config('services.writing_help')` reads `AI_PROVIDER`
  (anthropic, openai, gemini...) and `AI_MODEL` (empty = the provider's
  cheapest, `#[UseCheapestModel]`). It is on only while the provider is named
  **and** its key is set in `config/ai.php` (`ANTHROPIC_API_KEY`...):
  `WritingHelpSetup::isOn()`. For a restaurant it also needs uses on its
  package: `isOnFor()`; the dashboard hears `restaurant.writing_help`. Off,
  the endpoints 404 and no button shows.
- **Uses a day per package:** the limit `Feature::WritingHelpLimit`
  (`writing_help_limit`), set per package at /admin → Packages like dishes:
  0 is no writing help, empty is no daily cap, grants add to it. A package
  saved before it existed reads `defaultValue()` (30) until edited; the
  seeded catalog has Free 10, Pro 100, Premium and Custom 200. Kept off the
  landing page's pricing cards (`Feature::isOnPricing()`; the page claims no
  AI, `LandingContentTest`); the dashboard's Package page lists it.
- **Counting the uses:** `App\Services\Ai\WritingHelpUses`, a rate limiter
  key per restaurant per day of its own (`localTimezone()`), so they all come
  back at its midnight and "come back tomorrow" is true. `PaceWritingHelp`
  checks it: none left is an instant 429 `writing_help_limit` with `left: 0`
  and `retry_after` (seconds to that midnight); a use is taken only once the
  request may run and given back when the answer is not a success (the
  provider failed, a 422), so an owner only spends a use on text they got.
  Every answer carries `left` (null for no cap) and the session sends
  `writing_help: {limit, left}` (null when off); the dashboard shows it on
  each AI button. It never bans.
- **The usage log:** `writing_help_days`, a row per restaurant per day of
  its own (`WritingHelpDay`, written by `App\Services\Ai\WritingHelpLog`
  from PaceWritingHelp): the uses that gave the owner text, and the tokens
  every call spent, a failure's and a retry's included
  (`WritingHelp::takeTokens()`). The admin reads it in the Restaurants table
  ("AI uses (30 days)", sortable, the tokens under it; one subquery with the
  page, `WritingHelpLog::lastDaysOf()`), on each restaurant ("AI writing help
  used: 3 today, 41 in the last 30 days (52,300 tokens)") and on the
  dashboard (`WritingHelpUsage`, everyone's last 30 days).
- **What an answer must keep, checked by the server** (models cannot count
  or be trusted not to invent): a category's description is 60 to 70
  characters (`WritingHelp::CATEGORY_LENGTH`), the restaurant's 100 to 110
  (`RESTAURANT_LENGTH`); a description never has a dash (`withoutDashes()`:
  between words a comma, inside a word a space) and never makes a claim
  what it was written from does not make (`UNSAID`: an oven, fresh,
  homemade, a chef, since, the best..., in English and Arabic; "Grills" may
  say grilled, an owner's "homemade" stays). A translation is never longer
  than its field (`max`); one of a description the writing help wrote
  (`fit`, and the text of that length) keeps that length and loses its
  dashes in the other language, the owner's own text keeps its dashes. A
  miss is asked again with what was wrong, 3 tries, then the failure below;
  never a broken rule. The writers pad with the moment, never the food, and
  avoid stock phrases.
- **Never starves the menus:** a call holds a PHP worker while it waits, so
  `PaceWritingHelp` lets one run at a time per restaurant and at most
  `AI_AT_ONCE` (10) on the server (cache locks, tried from a random one, 30 s
  at most); past either, an instant 503 `writing_help_busy`, which costs no
  use. Each call waits at most `WritingHelp::TRY_SECONDS` (10) and a whole
  answer, every retry together, `TIME_BUDGET` (15).
- **Failures:** a provider that is down, out of credit or refusing the key,
  or an answer with no text or not in the shape asked, is one 503
  `writing_help_failed` ("try again in a moment"); the cause is reported to
  the log.
- **Tests** need no key and make no calls. Most fake the agents
  (`MenuTranslator::fake()`): `tests/Feature/Api/WritingHelpTest.php`,
  `WritingHelpEdgeTest.php` (validation edges, PaceWritingHelp's order) and
  `WritingHelpUsageLogTest.php`; `tests/Integration/Services/Ai` (the
  service, the uses, the log), `tests/Integration/Ai` (the agents'
  instructions; the good examples must keep the rules they teach),
  `tests/Unit/Services/Ai` (dashes). `WritingHelpGeminiTest.php` runs the
  real Gemini driver with only the HTTP faked (every way Gemini fails is a
  503 that costs no use). Admin: `WritingHelpLimitAdminTest.php`; the
  landing page: `tests/Feature/Portal/WritingHelpPricingTest.php`.
- **E2E:** the e2e server (tests/E2e/E2eServiceProvider) answers as a
  pretend Gemini (`geminiAnswer()`: "Translated: <text>", or a fixed line of
  each description's length) and switches the writing help on only for a
  request with the `X-E2E-Writing-Help` header (decided in `register()`,
  before the rate limiter is made), so every other spec and baseline sees it
  off. Those requests count in a file cache (`WRITING_HELP_CACHE`), so uses
  carry from one request to the next, and a scenario can start with
  `writing_help_used`.
