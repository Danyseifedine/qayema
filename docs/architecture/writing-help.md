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
`RestaurantDescriptionWriter` (structured output: the translator one `text`,
the description writers their versions, `texts`, see below).

**A whole dish at once:** `POST /api/writing/translate-many` (`items`: a list
of `{text, from, to, max}`, at most `WritingHelp::LIST_ITEMS` (100) and
`LIST_LENGTH` (3,360: everything one dish can hold) characters together) answers `{data: {texts, left}}`,
one translation a piece in the order sent. It is what the dish form calls on
save for every name, ingredient list, variant, option and add-on written in
one language only, the category form for its name and description, and the
"Translate all names" button of the variants and add-ons. Pieces going the same way are one call to `MenuListTranslator`
(structured output, a `texts` list); each answer is held to its piece's
`max`, none empty and one a piece, asked again otherwise, within
`LIST_TIME_BUDGET` (22 s, inside `PaceWritingHelp`'s hold). The whole list is
**one use** of the day.

- **Claude (`AI_PROVIDER=anthropic`, e.g. `AI_MODEL=claude-haiku-5-5`):**
  nothing thinks on Haiku, set or picked as the provider's cheapest with
  `AI_MODEL` empty (`UsesWritingHelpModel::providerOptions()` reads
  `WritingHelpSetup::effectiveModel()`: thinking off); any other Claude model
  thinks at `output_config.effort: low`, since Claude Opus 5.5 and Sonnet 5.5
  refuse thinking off. Thinking cost hundreds of tokens for a line of a dozen
  words. A description writer instead answers with seven versions of rising
  length, from 15 characters under its length to 5 over (`WritesVersions`),
  and `WritingHelp::held()` keeps the first that keeps every rule; when none
  does, it asks again naming their lengths. Measured on Haiku 5.5: a category
  line about 900 tokens in and 200 to 450 out, right first time about 9 in 10.
  **Each answer's output tokens are capped to what its text needs**
  (`UsesWritingHelpModel::tokensFor()`: two tokens a character, plus 100 for
  the JSON, plus 1,000 for any model but Haiku, which may think first): a translation to its
  field's limit (a dish name's 60 characters is 220 tokens at most), a list
  to its pieces' limits together, a description to its seven versions
  together (940 for a category, 1,500 for the restaurant). The field limits
  are `App\Support\MenuTextLimits`, so the most one call can cost follows
  them.
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
- **Credits a month** (`App\Services\Ai\WritingHelpCredits`): the hard
  cap on what a restaurant's AI costs, without ever showing the owner money
  or the model.
  - A package has an **AI budget a month in dollars**
    (`packages.ai_budget_cents`, typed in dollars at /admin → Packages, the
    credits it buys shown under it as it is typed; empty is no cap, 0 turns
    the AI off). Its credits are the budget at what **a credit is worth**,
    `Feature::WritingHelpCredits` (`Package::featureValue()`), so a change
    of either moves them.
  - **Settings, AI credits** (`App\Filament\Admin\Pages\AiCredits`, stored
    in `app_settings` through `App\Models\AppSetting`, one cached map):
    what a credit is worth in cents (0.01¢ by default) and each action's
    price (`App\Enums\WritingHelpAction`: a translation, one field or a
    whole dish, 1; a description 3), with what a credit, a translation and a
    description really cost over the last 30 days to set them by. Saving
    flushes every restaurant's entitlements.
  - **One restaurant** is given its own number on its page's Features
    section ("Worth $2.00 of AI a month" under it), or extra on the Extras &
    overrides tab: ordinary set and add grants, so a custom number stays
    put when the package or a credit's worth changes.
  - The month runs from the 1st in the restaurant's timezone; credits
    taken are summed from `writing_help_days.credits`. `PaceWritingHelp`
    checks them in the restaurant's turn, after the day's uses: the action's
    credits must be left **and** this month's real cost plus the most the
    request could cost (`WritingHelp::mostItCouldCost()`: every try at its
    answer's cap, read from what it sends) must stay within the credits
    times a credit's worth (`WritingHelpCredits::refusal()`: `credits` or
    `cost`). Either is a 429 `writing_help_credits` ("They come back on 1
    November", `resets_on`, `credits_left`), never a word about money: too
    few credits says those left, so what they still cover stays on; a
    request too big for the money left says 0, so the buttons lock until
    the page opens again. The credits left are never more than the money
    left buys (`leftOf()`), so the owner is never shown credits the cap
    would refuse. A call cut off on its way (a timeout) counts as the most
    it could have cost, since the provider may still bill it. So a
    restaurant never costs more than its credits are worth; at the very end
    of a month it may be stopped with the price of one worst-case request
    unused. A writing route not named in `WritingHelpAction::fromRoute()` is
    priced as a description, never free; the prompt's menu names are cut to
    today's limits, so older, longer text never costs more than counted.
  - Credits are taken only for text the owner got, like the uses. Every
    answer carries `credits_left`, and the session's `writing_help.credits`
    is `{limit, left, resets_on, prices}`; `/api/packages` sends each
    package's `writing_help_credits`. Credits of 0 turn the buttons off
    (`WritingHelpSetup::isOnFor()`).
  - The admin sees credits taken and the real cost per credit on the home
    page (`WritingHelpUsage`) and the settings page, and each restaurant's
    month ("1,240 of 7,500 taken ($0.09 real cost, worth $0.75)") on its
    page and in the admin phone app's API (`stats.ai_credits`).
- **The usage log:** `writing_help_days`, a row per restaurant per day of
  its own (`WritingHelpDay`, written by `App\Services\Ai\WritingHelpLog`
  from PaceWritingHelp): the uses that gave the owner text, and the tokens
  every call spent, a failure's and a retry's included
  (`WritingHelp::takeTokens()`), the credits taken, and the uses and cost of
  translations and descriptions apart. The admin reads it in the Restaurants table
  ("AI uses (30 days)", sortable, the tokens under it; one subquery with the
  page, `WritingHelpLog::lastDaysOf()`), on each restaurant ("AI writing help
  used: 3 today, 41 in the last 30 days (52,300 tokens, $0.02)") and on the
  dashboard (`WritingHelpUsage`, everyone's last 30 days).
- **What it cost:** each request is priced when it is made
  (`App\Services\Ai\WritingHelpPrice`: Anthropic's list prices for the
  Claude models, or `AI_PRICE_INPUT` / `AI_PRICE_OUTPUT` in dollars per
  million tokens for any other) and added to the day's `cost`, so a later
  change of model never re-prices the past. The model priced is
  `WritingHelpSetup::effectiveModel()`: the one set, or the provider's
  cheapest. The admin sees it as the dashboard card's "AI cost" (today
  apart, the model and its price above), the Restaurants table's sortable
  "AI cost (30 days)" and each restaurant's line; the admin phone app's API
  sends `cost` with the tokens. Days logged before costs were kept read 0.
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
  instructions; the good examples must keep the rules they teach; the most
  an answer may cost), `WritingHelpCreditsTest` in Integration (the credits,
  the month, the cap) and Feature/Api (taken, refused, never money),
  `tests/Unit/Services/Ai` (dashes). `WritingHelpGeminiTest.php` runs the
  real Gemini driver with only the HTTP faked (every way Gemini fails is a
  503 that costs no use). Admin: `WritingHelpLimitAdminTest.php` and
  `AiCreditsAdminTest.php`; the
  landing page: `tests/Feature/Portal/WritingHelpPricingTest.php`.
- **E2E:** the e2e server (tests/E2e/E2eServiceProvider) answers as a
  pretend Gemini (`geminiAnswer()`: "Translated: <text>", or a fixed line of
  each description's length) and switches the writing help on only for a
  request with the `X-E2E-Writing-Help` header (decided in `register()`,
  before the rate limiter is made), so every other spec and baseline sees it
  off. Those requests count in a file cache (`WRITING_HELP_CACHE`), so uses
  carry from one request to the next, and a scenario can start with
  `writing_help_used`, or with credits taken this month
  (`writing_help_days: [{credits}]`).
