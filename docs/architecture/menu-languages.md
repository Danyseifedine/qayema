# Menu languages

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

Every menu is written in a **main language** (`restaurants.main_locale`,
English by default) plus, optionally, **one second language**
(`restaurants.second_locale`, null = none), both picked from
`config('locales.menu')`: English, Arabic, French, Spanish, Turkish, German,
Italian, Russian, Chinese, Hindi, Portuguese. Every package picks its main
language (Free gets one language, any of them); a second one needs
`multiple_languages`. Both are set on the dashboard's Features page
(`GET/PUT /api/menu-languages`; a PUT without `main_locale` keeps it).
`default_locale` is what the menu opens in: the main or the second language.
`config('locales.supported')` is only the **portal's** UI list and has
nothing to do with menus.

- `App\Services\Menu\MenuLanguages` owns it: `main()`, `for()`
  (`[main, second?]`, main only while the second is off or not on the
  package), `written()`, `default()`, `withMain()` (a new main language:
  choosing the second one swaps the two, nothing is copied since text is
  stored per language code), `text()` (that language, else the `$main`
  passed, else whatever was written, so a dish not yet written in a new
  main language shows its old name; never spatie's accessor, which fell
  back to the app locale and showed Arabic-only text blank),
  `missingInMain()` (what still has no name in the main language), `map()`
  for resources, `rules()` for requests (the required rule goes on the first
  language, the main one), `requiredMessages()` ("required in your menu's
  main language (Français)"), `input()` + `fill()` for writes.
- **The main language is required** for every name (restaurant, category,
  dish, variant, option, add-on); everything else is optional per language.
- The API only reads and writes the menu's **active** languages and merges into
  the stored JSON, so text in a language the owner switched away from stays,
  hidden, and comes back if they switch back. A language sent blank clears
  that language; a field sent as `null` clears all active ones; a field not
  sent is untouched.
- Public menu: `?lang=` accepts only the menu's own languages (anything else
  opens the default), hreflang and the switcher list only those, and a
  one-language menu (in any language) has no switcher. `dir` comes from the
  catalogue; fonts come from the language's script (see Templates → fonts).
  The menu's search (`fold()` in `public/js/menu-nav.js`) ignores case,
  accents and Arabic short vowels and treats every alef as a bare one, ة as
  ه and ى as ي, so a guest finds "كنافة" by typing "كنافه".
- The admin forms (restaurant, category, dish and its choices) edit the
  restaurant's main language: one input per language, only the main one
  shown (`App\Filament\Admin\Schemas\Components\MenuTextInputs`). The
  restaurant form has a "Main language" select (a swap when it picks the
  second language, `EditRestaurant`), and Create User one for the new
  restaurant. The admin phone app (`../qayema-admin-app`) does the same
  through `/api/admin/restaurants`: each restaurant carries `main_locale`
  (its name field is labelled with it), and a new one may send it; the
  app's list of languages (`lib/core/menu_languages.dart`) mirrors
  `config('locales.menu')`.
- The menu's own words live in `lang/{code}.json` (same keys as `ar.json`;
  `tests/Integration/View/MenuTranslationsTest` checks every string a guest
  can see is in each one, with its :placeholders). Owner text on the menu
  sits in `<bdi>`, so a line shown in the main language keeps its own
  direction on an Arabic page.
  The non-Arabic ones were written by Claude and still want a native read.
- The cart sends the guest's language with an order; any error and the
  line names of an order come back in it. A WhatsApp message (labels,
  the restaurant's name and line names) is written in it too.
- Onboarding's first step asks "Your menu is written in" (suggested from the
  page's language); the name typed there is saved in it, the menu opens in
  it, and the second language starts as Arabic (English for an Arabic
  menu), shown once the package has more than one language.

A page reached from this same menu reports **no** visit (menu-track.js
compares `document.referrer` with `QAYEMA_TRACK.menu`): a language switch is
one visit continuing, not two.
