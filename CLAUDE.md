<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.4
- filament/filament (FILAMENT) - v4
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- Middleware lives in `app/Http/Middleware/` and is registered in `bootstrap/app.php`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.
- Cast every integer column, foreign keys included (`'template_id' => 'integer'`), and `(int)` every count or aggregate a response sends. The production MySQL driver returns them as strings, which the dashboard's schemas reject and which fail `===` ownership checks. `IntegerCastsTest` fails on an uncast column.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.


=== filament/filament rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


### Important Version 4 Changes
- File visibility is now `private` by default.
- The `deferFilters` method from Filament v3 is now the default behavior in Filament v4, so users must click a button before the filters are applied to the table. To disable this behavior, you can use the `deferFilters(false)` method.
- The `Grid`, `Section`, and `Fieldset` layout components no longer span all columns by default.
- The `all` pagination page method is not available for tables by default.
- All action classes extend `Filament\Actions\Action`. No action classes exist in `Filament\Tables\Actions`.
- The `Form` & `Infolist` layout components have been moved to `Filament\Schemas\Components`, for example `Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.
- A new `Repeater` component for Forms has been added.
- Icons now use the `Filament\Support\Icons\Heroicon` Enum by default. Other options are available and documented.

### Organize Component Classes Structure
- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`
- Table filters: `Tables/Filters/`
- Actions: `Actions/`
</laravel-boost-guidelines>

# Working Rules

1. **Never commit.** Never run `git commit`, `git push`, `git add`, `git stash`,
   `git reset`, or any command that changes git state. The user does every
   commit. If a step would normally end with a commit, stop and say the tree is
   ready to commit.
2. **Never leave code unused.** Every Blade component, Livewire component,
   Filament resource/action/widget, service, job, enum case or helper you create
   must be used in the same task. If it ends up unused, delete it or wire it
   in. Never leave it.
3. **Always use the component.** Before writing Blade markup, look in
   `resources/views/components/` (especially `components/ui/`) and the Filament
   component set. If a component exists for the job, use it. Never re-implement
   a button, card, field, modal or layout inline. If the existing component does
   not fit, extend it; do not fork it.
4. **Always tell the truth.** Report what actually happened: if `php artisan test`
   failed, a test was skipped, a step was not done, or you are guessing, say it
   plainly in the first sentence. No softening, no reassurance, no claiming
   something works without having run it. The user does not need feelings
   managed; they need accurate status.
5. **Never use em dashes.** Not in code, comments, UI copy, translations
   (English or Arabic), tests, docs or messages. Write a comma, colon,
   semicolon, parentheses or a new sentence instead (in Arabic, the Arabic comma ، or a colon);
   never an en dash or `--` in its place. An empty-value placeholder is `-`.
6. **Never run a full suite unless the user says to.** Not `composer check`,
   not a whole `php artisan test`, not `npm run check` or `npm run e2e` in the
   dashboard. Check your work with targeted runs only: the test files you
   touched (`php artisan test tests/Feature/...` or `--filter`). When you are
   done, say which full suites have not been run and leave the decision to
   the user.

# Qayema: Project Architecture

Bilingual (ar/en) restaurant-menu SaaS. Two codebases in this folder:

- **Laravel app (this repo)**: the public portal (landing, legal, contact), auth
  (Google OAuth + email via `/get-started`), a 3-step onboarding wizard, the
  **public menu at `/{slug}`**, the JSON API for the dashboard SPA
  (`routes/api.php`), and the Filament v4 admin panel (`/admin`).
- **`qayema-dashboard/` (separate repo)**: the owner dashboard SPA. React 19 +
  Vite + TS, Sanctum **session-cookie** auth (no tokens), CSRF primed from
  `GET /api/csrf-token` (the body, because a cross-subdomain SPA can't read the
  cookie). It also holds the Playwright end-to-end suite for both apps (`e2e/`).

## Packages

**Four packages: Free, Pro, Premium and Custom.** A restaurant points at one
(`restaurants.package_id`) and that package holds every limit and flag it gets.
Nothing is sold in-app yet: an owner asks for a package and an admin assigns it.

```
Owner → POST /api/packages/request → ContactService::submit()
    → contact_messages row (user_id + package_id) + email to the admin
Admin → /admin → the request → "Apply this package" (or Restaurants →
    Package → Change package / Extend) → PackageAssigner → in force at its start
```

What each ships with (`config/package.php`, pinned by `PackageCatalogTest`;
the admin owns the numbers after install):

| | Free | Pro | Premium | Custom |
|---|---|---|---|---|
| dishes / categories / social links | 40 / 8 / 1 | 150 / 15 / 2 | 1,000 / 1,000 (fair use, shown as unlimited) / 10 | unlimited |
| `multiple_languages`, `variants`, `addons`, `appearance`, `analytics` | - | ✓ | ✓ | ✓ |
| `premium_designs`, `qr_studio`, `ordering`, `menu_ordering`, `advanced_analytics` | - | - | ✓ | ✓ |

Premium is `is_featured` ("Most popular" on the dashboard and the landing page); only one package holds it, and marking another in the admin takes it off the rest.
Prices are still placeholders. The landing page's pricing is read from the
`packages` table by `App\Services\Portal\PricingCards` (names, prices,
"Everything in Pro, plus" lines like the dashboard's), so an admin's edit
shows there at once; only its headings live in `lang/{en,ar}/portal.php`.
The landing copy says only what the product does: no AI, no checkout, no
invented reviews (`tests/Feature/Portal/LandingContentTest`).

- `packages.features` is a JSON map of `App\Enums\Feature` slug => value:
  an integer allowance, **null for unlimited**, or 0/1 for a flag. A key the map
  doesn't carry falls back to that feature's `defaultValue()`, so adding an enum
  case never breaks an existing package.
- The four rows are **fixed**: seeded by `create_packages_table` from
  `config('package.catalog')`, edited at **/admin → Packages**, never created or
  deleted there. `PackageSeeder` is `firstOrCreate`, so seeding a live database
  cannot overwrite an admin's edits.
- **Dates:** the assigned package is in force from `package_started_at` (null =
  always) until `package_ends_at` (null = forever): `Restaurant::packageStatus()`
  (`PackageStatus` Active / Scheduled / Expired). Outside that window
  `effectivePackage()` is the default package, and the assignment stays on the
  row so an admin sees what is coming or what lapsed. `/api/user` sends the
  package in force with `ends_at` and `days_left`, plus `lapsed` (with
  `ended_at`) or `upcoming` (with `starts_at`) for the assigned one. Query scopes: `packageActive()`,
  `packageScheduled()`, `packageExpired()`, `packageEndingWithin($days)`,
  `onPackage($id)` (in force, including restaurants fallen back to the default).
- **`App\Services\Packages\PackageAssigner` is the one way an admin changes a
  package**: `assign()` (package, start, end|null, note), `extend()` (months
  added to the end, or to today once ended; null = forever), `reset()` (default,
  forever). Every admin action calls it: Change package (row, bulk, the
  ending-soon widget, "Apply this package" on a request), Extend, Back to
  default. The restaurant form's Package section uses the same fields
  (`Restaurants\Schemas\PackageFields`: package, starts, forever / N months /
  until a date, note).
- **History:** `package_changes` (model `PackageChange`) gets a row for every
  write to `Restaurant::PACKAGE_FIELDS`, from the model's own `created`/`saved`
  hooks, so no path skips it: from → to, dates, `changed_by` (the signed-in
  admin), and the note (`$restaurant->packageChangeNote`, set before the save).
  Read-only "Package history" relation manager on the restaurant.
- **Offered or not** (`packages.is_active`, the "Offered" switch on the
  Packages list and form): a package switched off leaves the landing page
  and `/pricing` (`PricingCards`, `Package::offered()`), the search offers
  (`StructuredData`), the dashboard's Package page (`/api/packages` lists
  what is offered plus the owner's own package) and package requests (422).
  Restaurants already on it keep it until it ends; the admin can still
  assign it, labelled "(not offered)". The default package is always
  offered, and switching one off takes its "Most popular" mark (model
  `saving` hook). Written copy that names a package (`lang/*/portal.php`,
  `pages.php`) is not touched: edit it by hand.
- **Card lines** (`packages.highlights`, `{en: [...], ar: [...]}`, the
  admin's "Card lines" section): written, they replace the lines a package
  card works out from its features, on the landing page (`PricingCards`,
  `Package::highlightsIn()`, English when Arabic is empty) and the dashboard
  (`/api/packages` sends `highlights`, `writtenHighlights()` per language).
  Custom ships with its own (`config/package.php`; the migration fills them
  only where none are written): a design for the brand, limits for the
  group, direct help. Empty, the card lists what the package adds.
- The admin home is the `PackagesEndingSoon` widget: ending in 14 days or
  ended in the last 30, with Extend / Change package.
- A package request shares the public contact form's durable per-IP quota of
  3/day, and comes back as a **429** carrying `retry_after` when it is hit.
- **Premium designs:** a template with `is_premium` needs the
  `premium_designs` flag to be chosen (`TemplateController::select` 403s, the
  list sends `is_premium` and `locked`). A restaurant that loses the flag keeps
  its `template_id`; `Restaurant::menuTemplate()` draws the menu (and
  Appearance, the QR brand colour) in `Template::fallback()`, the first active
  non-premium design, until it is back. `meta.shown` tells the dashboard.
- **What each flag gates** (all "package allows AND owner did not switch it
  off" where the owner has a switch):
  `analytics` → `GET /api/analytics` (403) and the QR page's scan counts
  (`GET /api/analytics/teaser`, this week's views, is open to all);
  `appearance` → `PUT /api/appearance` (403), and without it the menu draws the
  design's defaults and default fonts (`designSettings()`, `MenuFonts::family()`;
  `MenuFonts::chosen()` is the owner's pick for the dashboard); choices kept;
  `multiple_languages` → `showsSecondLanguage()` / `MenuLanguages::for()`
  (English-only, the second language kept) and choosing one in
  `PUT /api/menu-languages` (403);
  `qr_studio`, `ordering`, `advanced_analytics` as below.

## Limits / entitlements

One rule: **effective value = the package's value + Σ active grants** (limits
add, flags OR, unlimited stays unlimited). Resolved by
`App\Services\Packages\Entitlements`, cached `entitlements:{id}` for 300 s or
until the next start/end of the package or a grant, whichever is sooner. A
date passing never leaves a stale answer, and no scheduler is needed.

- `App\Enums\Feature` is the registry: adding a limit or a flag is one enum
  case (`kind()`, `defaultValue()` and `label()`/`hint()` in
  `lang/{en,ar}/features.php` list every case, no default arm). The admin form,
  the packages table and `/api/user`'s `plan` (`Feature::flags()`) all render
  from it. A package that does not carry a new key reads it as its default,
  and the admin form fills it so a save never turns it unlimited.
- Saving a package flushes **every** restaurant's cache (`Entitlements::flushAll()`
  via the model's `saved` hook); changing one restaurant's package or expiry
  flushes only that one.
- `feature_grants` = per-restaurant grants, managed on the restaurant's
  "Extra slots & add-ons" tab, with source `admin` or `purchase`, an optional
  end and a `note`.
- A limit of **null is unlimited**: `hasReachedXLimit()` is false, the API sends
  `limit: null`, and a grant on top of it leaves it unlimited.

## Templates

A template is a row + a Blade view of the same slug
(`resources/views/menu/templates/{slug}.blade.php`) + its stylesheet
(`public/css/menu-{slug}.css`, cache-busted by `filemtime`). The view's `<head>`
`@include('menu.partials.theme')`, which loads the owner's fonts and prints
every colour the design declares as a CSS variable (`primary_color` →
`--primary-color` and `--primary-color-ink`), plus `--font`. Classic also
keeps its own `--accent/--bg/--text` lines; tests assert `--accent: #XXXXXX`,
so keep their spacing. The inline SVG icons come from `App\Support\MenuIcons`.
Scaffold all of it with:

```bash
php artisan make:menu-template midnight
```

**A design's settings are data, not code.** `templates.settings_schema`
declares them: `[{key, type, default, label: {en, ar}, contrast_with?, options?}]`,
type `color` | `boolean` | `select` | `text` (edited in the admin panel; `key`
is `^[a-z][a-z0-9_]*$`, a colour default must be hex, an on/off default is
written `true`/`false`). To give a design a new setting: add a row, then use it
in the view: `$settings['key']` for any type, and a colour is also
`var(--the-key)`. Nothing else. The dashboard's **Appearance** page shows
every row (`Template::editableSettings()`) with the field its type needs:
colour picker (with a contrast warning against `contrast_with`), switch,
choice, short text. `UpdateAppearanceRequest` builds its rules from those rows
and rejects anything else. `Template::accepts()` / `cast()` keep stored values
honest: a value that no longer fits its row (a removed choice, a non-hex
colour) falls back to the default, and on/off is always a real boolean.
Classic's rows: three colours and `show_name` (the name beside the logo in the
top bar). An empty schema = a fixed design.

**Each design remembers its colours.** `restaurants.template_settings` is
`{template_id: {key: value}}` holding only what the owner changed
(`Restaurant::designSettings()` resolves it over the design's defaults;
`saveDesignSettings()` writes one entry, by key, never `array_merge`, which
renumbers the integer keys). Switching design resets nothing. `resolveSettings()` drops a stored colour
that isn't hex, so a view can print one straight into CSS.

**Fonts belong to the restaurant**, one per writing system the menu uses:
`config/fonts.php` is the curated catalogue (per script: families with the
weights each really has (Google 400s on a missing one), a default, a sample
dish name, and `latin_first`); each menu language names its `script` in
`config/locales.php`. `restaurants.menu_fonts` = `{script: family}`.
`App\Services\Menu\MenuFonts` gives the scripts in use (via
`MenuLanguages::for()`), the pick or default, the CSS stack and the Google
Fonts URL. Arabic and Chinese stack the Latin pick first (their fonts also
carry Latin, which would win for prices); Cyrillic and Devanagari put their
own first (Latin fonts also carry them). The QR card loads every font the
menu uses. API: `GET/PUT /api/appearance` (`AppearanceController`), body
`{settings?: {key: value}, fonts?: {script: family}}`, null = back to default.

The public menu controller falls back to `classic` when a template row exists
without its Blade file, so a half-finished template never 500s a guest. The
printable QR card is `resources/views/menu/qr-card.blade.php` (a per-restaurant
public page, not portal content).

## Code layout and names

One name per thing, shared with the dashboard (`../qayema-dashboard`):

| Owner sees | Backend |
|---|---|
| Analytics | `AnalyticsController`, `GET /api/analytics[/advanced]`, `Services/Analytics/MenuStats`, model `MenuSession` (table `menu_sessions`) |
| Design | `TemplateController`, `/api/templates`; a *design* is a `Template` row; the model keeps its name |
| Appearance | `AppearanceController`, `GET/PUT /api/appearance`, `Restaurant::designSettings()`, `MenuFonts`, `config/fonts.php` |
| Restaurant | `RestaurantController`, `GET/PUT /api/restaurant`, `RestaurantResource`, `UpdateRestaurantRequest` |
| Features | `FeaturesController`, `PUT /api/features`, column `switched_off` |
| Package | `/api/packages` |
| Account | `/api/account` |

Three words that are never swapped: **plan** = what a restaurant may use
(`restaurant.plan.{multiple_languages, variants, addons, appearance,
premium_designs, qr_studio, ordering, menu_ordering, analytics, advanced_analytics}` in `/api/user`,
resolved by `Entitlements`); **grant** = an admin giving one restaurant more
than its package (`FeatureGrant`, table `feature_grants`); **switched off** =
what the owner turned off on the Features page (`restaurant.switched_off`).
`App\Enums\Feature` and `Package.features` describe what a *package* contains.

- `app/Services/<Group>/`: `Analytics` (MenuStats, MenuEventRecorder,
  MenuVisitRecorder), `Menu` (MenuLanguages, OpeningHours, MapPoint,
  DisplayOrder, DishOptionsSync, MenuDishOptions), `Orders` (OrderPlacer, WhatsAppLink), `Packages`
  (Entitlements, PackageAssigner), `Qr` (QrStyle), `Media` (MediaService, UploadLimits),
  `Security` (AbuseGuard, Captcha), `Contact` (ContactService), `Portal`
  (OnboardingService). A new service goes in the group it serves.
- `app/Support/` is for value helpers with no dependencies (`Color`).
- Every owner API controller resolves its restaurant through the
  `ResolvesRestaurant` trait (403 when the user has none); never copy the
  helper. `PackageRequestController` is the one exception on purpose: a user
  without a restaurant may still ask for a package.
- Validation is always a Form Request, including one-field ones
  (`AnalyticsRangeRequest`, `UpdateOrderRequest`, `UpdateDishAvailabilityRequest`).
- `Template::CLASSIC_SCHEMA` is the one copy of the classic design's settings
  (seeder and `make:menu-template`).

## Conventions & gotchas

- **Content:** category = name, an optional one-line description (max 300),
  and order. Dish = name, price, ingredients, one image, availability, order.
  No category images, no tags.
- **Never run `migrate:fresh`, `migrate:refresh`, `db:wipe` or `db:seed` over
  the local database without asking.** Migrations are edited in place
  pre-launch, which makes `migrate:fresh` look like the way to apply one, but
  the local MySQL holds the owner's own working data, binary logging is off,
  and there are no dumps, so a wipe is unrecoverable. Tests run on in-memory
  SQLite and need none of this. To apply an in-place column change to the live
  local DB, ask, or add it with a one-off `Schema::table` in tinker.
- **Translatable** columns are spatie JSON keyed by language. For menu
  content see "Menu languages" below; packages and templates (platform
  content) stay `{en, ar}`, shown in the dashboard's interface language.
- **Media:** Spatie medialibrary on Cloudflare R2, the `r2` disk in
  `config/filesystems.php`, chosen by `MEDIA_DISK=r2`. Temp-upload flow: POST
  an image → optimized to WebP → parked per-user on the private `local` disk
  (`temp/{user}`, `MediaService::tempRoot()`) → promoted by key into the media library on the
  next create/update. The raw upload is never stored. The `r2` disk sets no
  `visibility` (R2 has no per-object ACLs; the bucket is public through its
  domain). `phpunit.xml` pins `MEDIA_DISK=public`, so tests can never reach the
  bucket.
- **Uploads are up to 20 MB** (`UploadLimits::APP_MAX_BYTES`, the dashboard's
  `MAX_IMAGE_BYTES`) and always come out as a small WebP (presets in
  `config/image-optimization.php`). PHP must allow it too: `composer serve`
  locally (never `php artisan serve`: its child process ignores `-d`),
  `public/.user.ini` under PHP-FPM, nginx `client_max_body_size 25m`.
  `MediaService::ensureMemoryFor()` raises `memory_limit` for a big photo.
- **Every promotion goes through `MediaService::replace()`**: the dashboard's
  `sync()` and onboarding's `saveBranding()` alike. It stores on `MEDIA_DISK`
  and, if that disk is down or not configured, **falls back to the local
  `public` disk** and logs a warning (`Media disk failed; …`). Files that fell
  back stay local; nothing moves them to R2 later. A problem with the file
  itself (too big, missing, wrong type) is not retried. The old image is
  removed only *after* the new one is stored. Clearing first, as both paths
  used to, lost the logo whenever the upload then failed.
- **Keep `throw: false` on the `r2` disk.** The media library saves the row,
  then copies the file, and only cleans the row up when the copy *returns*
  false. A disk that throws skips that and leaves a row pointing at nothing.
  `replace()` also wraps each attempt in a transaction as a second guard.
- **The `/{slug}` route is a catch-all** declared last in `routes/web.php` and
  constrained against reserved prefixes (`admin`, `api`, `up`, …). Adding a new
  top-level page means adding it *before* that route.
- **Analytics:** see the Analytics section below.
- Rate limiters in `AppServiceProvider` (`api`/`mutations`/`uploads`/`auth`/
  `contact`); the high-volume ones feed the `AbuseGuard` auto-ban.
- Session lifetime is intentionally 1 year (the SPA rides it).
- `restaurants.switched_off` holds the optional features the owner
  switched off on the dashboard's Features page (`PUT /api/features` with
  `{off: [...]}`, limited to `Restaurant::OPTIONAL_FEATURES`): `orders` (no ordering at all:
  `takesOrders()`), `qr` (studio styling and printable card off, plain code
  kept: `hasQrStudio()`), `analytics` (page hidden), `languages` (English-only
  menu: `MenuLanguages::for()`; `written()` ignores the switch), `variants` and
  `addons` (a dish's choices leave the menu and orders: `showsVariants()`,
  `showsAddons()`; see Variants and add-ons). Nothing is
  deleted by switching one off. The package still decides what can be on.
  A feature a new package or grant brings arrives switched on
  (`switchOnWhatCameIntoReach()`, from the restaurant's and the grant's save
  hooks); one the old package already had keeps the owner's choice.
- Locale middleware alias is `portal.locale`; the session key stays `owner_locale`.
- **Content pages.** `/qr-menu-lebanon`, `/digital-menu-for-cafes`
  (`portal.pages.topic`), `/pricing`, `/guides` and `/guides/{guide}`, each
  also under `/ar`. Their text is `lang/{en,ar}/pages.php` (same keys in both,
  tested); a new guide is a key under `articles` plus its slug in
  `PortalUrl::GUIDES`, and every slug is a reserved restaurant link. Never
  write a price in that text: prices come from the packages
  (`portal.partials.pricing`, shared with home). Every page links from the
  footer and is in the sitemap through `PortalUrl::all()`. Each menu ends
  with a "Menu by Qayema" credit (`Menu by :brand` in every menu language).
- **Search (SEO).** The public pages (home, contact, the four legal pages)
  live once per language: English at the root, Arabic under `/ar`
  (`App\Support\PortalUrl`; routes `privacy` and `ar.privacy`). The address
  decides the language (`portal.locale:ar`) and is remembered for the sign-in
  pages; a remembered Arabic choice sends only the English home to `/ar`.
  Links between public pages go through `PortalUrl::to()`, the language
  switch through `PortalUrl::switchTo()`. Never decide a public page's
  language from the session alone: crawlers carry none, which once left
  Arabic invisible to Google. `ar`, `sitemap` and `robots` are reserved slugs.
- `<x-seo>` builds every portal head: "Page | Qayema" (once), the canonical
  address, the hreflang twins, the sharing image per language
  (`public/images/og/qayema-{en,ar}.jpg`, made from `resources/og/card.html`),
  and the schema.org data (`App\Services\Portal\StructuredData`: product,
  packages as offers, FAQ on home; a breadcrumb elsewhere). Sign-in, password
  and onboarding pages are `noindex`. `/robots.txt` and `/sitemap.xml` come
  from `SeoController` (no static robots.txt in public/). In production every
  generated address is pinned to the main address (`App\Support\SiteAddress`:
  `APP_URL` without "www.", even when `APP_URL` names www), and a GET to
  `www.` moves there with a 301 (`RedirectToMainAddress`, first in the web
  group), so `www.` never splits a page in two.
- A menu's head comes from `App\Services\Menu\MenuSeo` through
  `menu.partials.seo` (every menu design includes it): ":name: menu and
  prices" in the menu's language, one address per language (the opening
  language is the bare link), and Restaurant + Menu schema.org data with
  every available dish and price. A preview or an empty menu is `noindex`.
  On Arabic pages the brand is written "Qayema" in Latin letters.
- API requests take their language from `Accept-Language` (`SetApiLocale`,
  first in the `api` group so even a 401 is translated), limited to
  `locales.supported`. Arabic API text: `lang/ar.json` and
  `lang/ar/validation.php` (only the rules the API uses; anything else falls
  back to Laravel's English).
- **Admin forms of translatable models** (Restaurant, Category, Dish, Package,
  Template) edit one field per language (`name.en`, and `name.ar` for platform
  text) and use `App\Filament\Admin\Concerns\KeepsTranslations` on their
  create/edit pages: filling gives the form every language, saving merges what
  the form sent over the languages it does not show. Never bind a translatable
  column to a single input (it shows `[object Object]` and overwrites the text).
- **Admin forms and tables** (`AdminPanelProvider::boot()` sets the defaults):
  a select or select filter is never the browser's native dropdown
  (`native(false)` everywhere; never switch one back); a section spans the
  full width of where it sits; a form with more than two sections is a wide
  main column and a narrow side column (`->columns(['lg' => 3])`, two
  `Group`s spanning 2 and 1), with short settings (visibility, template,
  image, availability, limits) on the side, single-column there. An upload's
  preview loads from `admin.media.preview` (`MediaPreviewController`, admins
  only), not the R2 domain: the field fetches the file, and R2 refuses a
  cross-origin fetch without CORS. The phone field's flag images are
  published to `public/vendor/filament-phone-input` (also on
  `composer update`); without them the flag is an empty box.
- **Telemetry (Grafana Cloud).** `keepsuit/laravel-opentelemetry` sends
  request traces (HTTP, queries with `?` placeholders, cache, queue, views,
  Livewire) and logs (`otlp` channel, from `info` up, added to the log stack)
  to Grafana when `GRAFANA_OTLP_ENDPOINT` is set; `GRAFANA_AUTH_HEADER` is
  passed as a header map (the SDK does not URL-decode a "key=value" string).
  No metrics, no console traces. Tests and the e2e suite set
  `OTEL_SDK_DISABLED=true`. Every trace carries
  `deployment.environment.name` (APP_ENV). The Privacy Policy names Grafana.
  In production the app sends to Grafana Alloy on the same machine
  (`GRAFANA_OTLP_ENDPOINT=http://127.0.0.1:4318`, no auth header), which
  batches and forwards: PHP has no background thread, and sending straight
  to Grafana Cloud kept each worker busy 1 to 3 s after its response
  (`docs/grafana-alloy.md`). A long-running script that sends many requests
  also stalls wherever the batch fills, so a slow span there is not the
  code's.
- **Fair use.** A package can show a limit as unlimited while a number
  holds (`packages.fair_use`, ticked in the admin's Limits box; Premium's
  dishes and categories, 1,000 each). `Entitlements::limit()` is enforced,
  `shownLimit()` is what the API sends (null), and `Package::shownValue()`
  is what the pricing cards and `/api/packages` show ("Unlimited dishes*").
  The number is stated under the pricing cards (`PricingCards::fairUseNote()`),
  in the pricing FAQ and in the Terms, and an owner who reaches it is told
  "the fair-use limit of N". Never show "Unlimited" for a limit without one
  of these: an empty limit is truly unlimited.
- **Deleting in the admin.** Every list has a Delete on each row and in
  bulk, and every edit page one in its header; the confirmation says what
  goes with the record. Deletes go through the models, never a query
  delete, so images leave storage: `User::deleting` deletes the restaurant,
  `Restaurant::deleting` deletes each dish (the media library removes every
  photo, logo and cover). A restaurant and its owner's account go together:
  `Restaurant::deleted` deletes the owner (never an admin), and
  `ownerIsBeingDeleted` stops the two hooks deleting each other twice. A deleted category leaves its dishes uncategorised.
  The default package is never deleted (hidden button, and
  `Package::deleting` refuses); restaurants on any other deleted package move
  to the default through `PackageAssigner`, with a note in their history.
  Orders and the package history are read and deleted on the restaurant's
  page, never added or edited there.
- Filament v4 testing: table **header** actions need
  `callAction(TestAction::make('create')->table())`, not `callAction('create')`.
- `Restaurant::RESERVED_SLUGS` is the single list behind both the public menu
  route constraint and onboarding's slug validation. Add new top-level pages there.
- Every `api/*` error is `{message, code}` JSON (see `bootstrap/app.php`); a
  rate limiter's custom response arrives as `HttpResponseException` and must pass through.
- CORS (`config/cors.php`) answers a preflight with `max_age` 7200: at 0 the
  browser sent an `OPTIONS` round trip before nearly every dashboard call,
  about as slow as the call itself. `POST /api/broadcasting/auth` is under
  `throttle:api` like the rest.
- `App\Support\PhoneNumber::international()`: "+…" or "00…" is taken as
  typed; otherwise it is national and gets the dial code, unless it starts
  with that code and is longer than 10 digits (an Indian mobile may begin
  with 91). Italy, San Marino and the Vatican keep their leading 0.

## Ordering

A guest builds a cart on the public menu and places an order. The restaurant
takes orders **one way at a time** (`restaurants.order_mode`, chosen on the
Features page, `PUT /api/features/ordering`), and every order records the way
it came in (`orders.channel`, `App\Enums\OrderChannel`):

- **WhatsApp** (`ordering` flag, Premium and Custom): the order is stored and
  the guest is sent to WhatsApp with it written out (`WhatsAppLink`), an
  optional note included. We never learn whether it was sent or served, so
  these orders are **not** listed on the Orders page and analytics call them
  "Sent to WhatsApp". It needs a usable number: without one the menu has no
  cart.
- **In the menu** (also needs the `menu_ordering` flag, Premium and Custom):
  the guest gives their name, a phone number (country code from `config/countries.php`,
  stored international through `App\Support\PhoneNumber`), Delivery or
  Pickup (`restaurants.order_types`, null = both; `App\Enums\Fulfilment`),
  a typed address for a delivery, optionally their location ("Use my
  location": `latitude`/`longitude`, shown to the owner as a Google Maps
  link), and a note. The menu shows a toast with the order number; the order
  waits on the Orders page. Refused while the restaurant is closed by its
  opening hours (no hours = always open). A page may ask for the location
  only on the menu itself (`SecurityHeaders`: `geolocation=(self)` there,
  `()` everywhere else).

`Restaurant::orderChannel()` is the rule: null without ordering, Menu only
while the package has `menu_ordering` (a downgrade falls back to WhatsApp),
WhatsApp otherwise.

- `App\Services\Orders\OrderPlacer` is the only way an order is created. Every
  dish is re-read scoped to the restaurant and every price comes from the
  database; a price in the request body is ignored. It takes an
  `OrderDetails` (channel, note, fulfilment, name, phone, address, location,
  `client_token`): a token this restaurant already has returns that order, so
  a double tap or a retry never orders twice.
- Order lines carry **their own** `name` and `unit_price`. A dish renamed,
  repriced or deleted later must not rewrite what was ordered, which is why
  `order_items.dish_id` is `nullOnDelete`.
- `POST /{slug}/order` is public, on the `web` group (session + CSRF), limited
  to 10/min per IP with **`autoBan: false`**: a dining room is one IP. The page
  sends `mode`, the way it was built for; a mismatch (the owner switched while
  the guest had the menu open) is a 409 asking to refresh. A hidden `website`
  box is a spam trap. `PlaceOrderRequest` sets the guest's menu language
  before validating, so field errors come back in it.
- "Use my current location" also fills the address box with the street, area
  and town (`GET /{slug}/address`, `throttle:geocode` 10/min,
  `App\Services\Orders\ReverseGeocoder`): our server asks OpenStreetMap's
  Nominatim, with the app's name as User-Agent, and keeps each spot (~11 m)
  for 30 days. It never overwrites what the guest typed. The e2e environment
  fakes Nominatim (`E2eServiceProvider`).
- The country code is a searchable list of our own (names in the menu's
  language from PHP's `intl`, English too; digits; letters), not a select.
  The cart's boxes use 14px text; an iPhone would zoom on focus below 16px,
  so the menu's head adds `maximum-scale=1` on iPhones and iPads only (iOS
  still allows pinch zoom; Android would not, so it never gets it).
- The cart's details form (`public/js/menu-cart.js`) is built once per panel
  and never redrawn, so typing survives cart changes. What the guest types is
  kept in their browser (`qayema-guest-{slug}`) until the order goes, then the
  form starts over, empty.
- **Tracking.** An order placed in the menu gets a 40-character
  `tracking_token`. The guest follows it in a sheet over the menu
  (`#track-sheet`, `public/js/menu-order.js`), never on a page of its own:
  `GET /{slug}/order/{token}` answers JSON (status, `closed`, `closed_at` and
  `html`, drawn by `menu/partials/order-tracking.blade.php`); opened as a page
  it redirects to the menu with `?track={token}`, which opens the sheet.
  Steps: sent → accepted → on its way (delivery) or ready for pickup →
  delivered or picked up, or cancelled, with the times (`accepted_at`,
  `ready_at`, `closed_at`, set by `Order::moveTo()`); a skipped step shows as
  passed with no time. Choices show as the guest picked them ("Large, + Extra
  cheese", `OrderItem::picks()`); the owner and WhatsApp keep "Size: Large".
  The short reference never opens it. The "Order sent" toast and a bar at the
  top of the menu (`qayema-order-{slug}` in the guest's browser, live status)
  open the sheet; the bar goes once the order is done, an hour after a
  cancelled one, and after 12 hours anyway. `OrderStatus` is placed,
  accepted, ready, done, cancelled: the dashboard's one button moves an
  in-menu order on (Accept, then On its way or Ready, then Done), and Accept
  is what tells the guest a person saw it. No SMS and no WhatsApp messages to
  the guest.
- **Live, through Pusher** (`config/broadcasting.php`, `pusher/pusher-php-server`;
  keys `PUSHER_APP_*` in `.env`, never committed). `App\Services\Orders\OrderNews`
  sends after the response (`defer`) and never throws (`rescue`): a guest's
  new or changed order → `OrdersChanged` on `private-orders.{restaurant id}`
  (the pulse: open, latest, changed; only that owner may join,
  `routes/channels.php`, signed at `POST /api/broadcasting/auth` with the
  Sanctum session); the owner moving it → also `OrderMoved` on
  `order.{tracking token}` (public, the token is the secret). Both are
  `ShouldBroadcastNow` (no queue worker on shared hosting). The menu loads
  `public/js/pusher.min.js` (pusher-js 8.6, vendored) only when there is an
  order to follow. Without keys (local, tests, e2e: `BROADCAST_CONNECTION=null`)
  nothing is sent, and the sheet, the bar and the dashboard ask once a minute;
  they do the same whenever Pusher cannot be heard.
- **One order at a time** (per browser; guests have no accounts). While the
  guest's order is going (menu-order.js tells the cart, `qayema:order-state`),
  the cart **adds to it** ("Adding to your order #…", "Add to order": the
  order's lines come back from `/cart`, the new ones are added, and the whole
  goes as a change, with no details asked again). Once the restaurant has
  accepted it, the cart waits ("One order at a time") until it is done or
  cancelled.
- **Changing an order.** Only while it waits to be accepted
  (`OrderStatus::isOpenToGuest()`, placed), the guest may change an order
  placed in the menu: the tracking sheet's "Change my order"
  hands it to `menu-cart.js`, which loads
  `GET /{slug}/order/{token}/cart` (lines with their choice ids, kept in the
  `order_items.options` snapshot as `option_id` / `addon_id`, and the details,
  the phone split back by `PhoneNumber::split()`) into the cart without
  touching the guest's own cart or form in storage, and "Update order" sends
  `PUT /{slug}/order/{token}` (`PlaceOrderRequest`, priced again by
  `OrderPlacer::change()` on a locked row; a shared location stays unless a
  new one comes, and a new address drops the old spot). Once accepted it answers 409 (`OrderLocked`).
  A change carries `version` (the `guest_updates` its page read from `/cart`;
  older is a 409, `OrderChanged`, so two phones never undo each other) and a
  `client_token` of its own, kept in `orders.change_token`: the same change
  sent twice (an answer lost on mobile data) is made once. Dishes marked
  unavailable since are left out and named (`unavailable` in the answer,
  `OrderPlacer::unavailable()`). Each change sets `guest_updated_at` and counts
  `guest_updates`; the dashboard's pulse carries `changed` and chimes, and the
  card says "Changed by the guest at 13:05" (amber while it waits to be
  accepted). The
  sheet's "Change my order" hands the token to the cart (`qayema:edit`).
- The owner reads in-menu orders at `GET /api/orders` (only `channel = menu`;
  only `status` is writable). `PATCH /api/orders/{order}` works on the locked
  row and only moves an order on (`OrderStatus::canMoveTo()`: forward, a step
  may be skipped, or cancelled while open; the same status again is a no-op);
  anything else is a 409 `order_moved_on`. Taking on a new order sends the
  `guest_updates` the card showed; a change made since is a 409
  `order_changed` and the dashboard polls `GET /api/orders/pulse`
  (`{open, latest, changed}`) only while Pusher cannot be heard; the same
  pulse arrives live otherwise. Either way: a sound, a toast, the sidebar
  count and the tab title.
- `orders:forget-guests` (daily, 03:20) clears the name, phone, address and
  location from orders older than 90 days; the order itself stays. Production
  needs the scheduler's cron (`schedule:run` every minute) for it to run.
- Analytics (`MenuStats`) count only the current channel's orders
  (`order_channel` in the summary, `channel` in the funnel), and
  `orders_done` for orders placed in the menu.

## Variants and add-ons

A dish can carry **variants** (owner-named choices such as Size or Spice
level; the guest picks exactly one option of each) and **add-ons** (Extra
cheese; the guest picks any). Every option and add-on has a `price` that is
**added** to the dish's (0 for a choice that costs nothing more). Tables
`dish_variants`, `dish_variant_options`, `dish_addons` (models `DishVariant`,
`DishVariantOption`, `DishAddon`, names translatable like the dish's).

- Package flags `variants` and `addons` (Pro, Premium, Custom), each with its
  own switch on the Features page. Off, or not on the package: the rows stay
  saved but leave the menu and orders, and dish saves leave them alone.
- **Writing:** the dashboard's dish form sends each whole list in order
  (`variants: [{id?, name, options: [{id?, name, price}]}]`, `addons: [{id?,
  name, price}]`). `ValidatesDishOptions` (on Store/UpdateDishRequest) checks a
  list only while it is shown, so a switched-off list never reaches
  `validated()`. `App\Services\Menu\DishOptionsSync` writes it: an id of this
  dish's row updates it (hidden languages kept), anything else is created, a
  saved row left out is deleted. Validated nested input can come back with
  its indexes shuffled; the sync orders rows by index. Caps per dish are
  `config/menu.php` (5 variants, 10 options each, 20 add-ons).
- **Price:** a dish may have no price of its own when it has variants: its
  first variant's options are then full prices (a sandwich: Small $7, Large
  $12), each one required, and everything else (other variants, add-ons)
  still adds on top. Add-ons alone need a dish price. `MenuDishOptions` sends
  `priced: false` so the sheet shows those options as "$7.00", not "+$7.00";
  the card, the cart (`data-price="0.00"`) and `OrderPlacer` start from 0.
- **Menu:** `App\Services\Menu\MenuDishOptions` builds each dish's choices in
  the guest's language (a dish with a price or one variant to price it,
  only variants with 2+
  options), passed to the template as `$dish_options`. The card shows the
  cheapest combination's price; one
  `#dish-sheet` dialog is filled by `public/js/menu-dish.js` from the
  `#dish-options` JSON. With ordering it hands `qayema:add` events to
  `menu-cart.js` (a cart line is a dish plus its choice ids, so one dish can
  sit in the cart twice); without ordering the sheet only shows the choices.
- **Ordering:** `items.*.options` (option ids) and `items.*.addons`.
  `OrderPlacer` requires one option of every variant ("Choose a Size for
  Burger." in the guest's language), ignores ids that are not this dish's,
  prices `unit_price` = dish + options + add-ons, merges identical lines and
  snapshots `order_items.options` `{variants: [{name, choice, price}], addons:
  [{name, price}]}`. `OrderItem::choices()` is the readable form used by the
  WhatsApp message and the admin's orders list.

## Queries

- Never one query per row. An admin table reads what its columns show with
  the page (`modifyQueryUsing`: `with`, `withCount`, a sub-select), never in
  a column's `getStateUsing` (`tests/Feature/Admin/AdminListQueriesTest`).
- Translatable text is read with `MenuLanguages::text()` / `map()`, which
  decode the column once; spatie's `getTranslation()` decodes it several
  times per call and a menu asks hundreds of times.
- Something a page needs from several places is read once:
  `PricingCards` keeps its packages per instance (a page shares one),
  `Entitlements` is memoized per request, `Package::default()` is `once()`
  per request (a package save flushes it), and `/api/user` counts dishes,
  categories and links with one `loadCount`.
- Counts over time are plain ranges, never `date()`/`month()` on a column,
  so the index does the work: `Restaurant::qrScans()` (today, week, month,
  total in one pass, in the restaurant's timezone, `localTimezone()`) and
  `OrderPulse` (three numbers, one query). `menu_sessions` is indexed
  `(restaurant_id, viewed_at, via_qr, session_id)` so QR and visitor counts
  come from the index, and `viewed_at` / `menu_events.occurred_at` alone for
  the nightly prune.

## Menu speed

Production (qayema.com, A2 Hosting with Redis turned on) keeps the cache, sessions and rate
limits in Redis (`CACHE_STORE=redis`, `SESSION_DRIVER=redis`): on the
database they cost 4 to 6 queries on every request. The account has its own
Redis on its own port with a password (cPanel's Redis page), set in
`REDIS_HOST`, `REDIS_PORT` and `REDIS_PASSWORD`; `redis-cli` needs `-p` and
the password too. Local and the tests keep the database and array stores.

The public menu is opened on a phone, often on mobile data, so it stays light
(`tests/Feature/Menu/MenuSpeedTest`):
- Pictures: a dish photo has a 240px `thumb` (the card and the cart) and the
  full one only for its sheet (`data-photo`); the cover has a 960px `phone`
  version in a `srcset`, is never `loading="lazy"` and has
  `fetchpriority="high"`. Conversions are made with the upload (`nonQueued`);
  a photo from before them falls back to the full one until
  `php artisan media-library:regenerate --only-missing` runs.
- Uploads go to R2 with `Cache-Control: public, max-age=31536000, immutable`
  (`config/media-library.php`): a new photo is always a new address.
- Fonts load without blocking the first paint (`media="print"` swapped on
  load, `display=swap`).
- Every local script and stylesheet is linked with `?v=filemtime(...)`:
  `public/.htaccess` keeps css/js for a year, so one without a version would
  go stale for guests. It also compresses text (mod_deflate).
- The visit is written after the response (`defer()`), and entitlements are
  memoized per request (`Cache::memo()`), so a view is one read of them.
- The QR pop-up is ready before it is tapped: once the page has loaded and
  the phone is idle, `menu-nav.js` fetches the library and
  `/{slug}/qr-options` (browser-cached 5 minutes) and draws the code in the
  closed dialog. `QrStyle::logoDataUrl()` keeps the inlined logo per media id,
  so those requests do not download the logo from the disk each time.

## Maps

`restaurants.google_maps_url` is the only thing stored; there are no lat/lng
columns. `App\Services\Menu\MapPoint` reads the point back out of that URL
(`?q=`, `?ll=`, `?query=`, `/@lat,lng,17z`) and builds a **keyless
OpenStreetMap** embed: Google's needs an API key and a billing account, and
this page is scanned all day. A shortened `maps.app.goo.gl` link hides its
coordinates behind a redirect, so it gets the directions button but no map.

It mirrors `parseMapCoordinates` / `mapEmbedUrlFor` in the dashboard
(`src/features/settings/hooks/use-current-location.ts`); change one, change
both.

**The map frame must send a Referer.** OSM's tile policy requires one and
forbids a restrictive referrer policy, so the iframe sets no `referrerpolicy`
and uses `sandbox="allow-scripts allow-same-origin"`. A bare `allow-scripts`
puts the frame in an opaque origin, the tile requests arrive anonymous, and
every tile comes back **403 Access blocked**. `allow-same-origin` restores
openstreetmap.org's own origin, not ours, so the frame still cannot reach the
page. The dashboard's `location-field.tsx` carries the same pair.

## QR studio

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

## Analytics

- **Visits:** every menu render (not a preview, not a self-referred language
  switch) writes a `menu_sessions` row with device, browser, OS, the `locale`
  it opened in, and `via_qr` from the `?qr=1` the QR codes encode.
- **Guest actions:** `public/js/menu-track.js` batches what guests do into
  `POST /{slug}/events` (`throttle:menu-events`, never a ban, since a dining room
  shares one IP) → `MenuEventRecorder` → `menu_events`. Types are
  `App\Enums\MenuEventType`. Links opt in with `data-track="…"` (+
  `data-track-value`); the cart and the menu navigation dispatch a
  `qayema:track` DOM event rather than calling the tracker, so a menu without
  it loses nothing. The recorder drops a dish/category that is not the
  restaurant's, clears fields a type does not carry, and normalises search
  terms. Events share the visit's `session_id`, which is what the funnel counts.
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

## Menu languages

Every menu is written in **English** plus, optionally, **one second language**
the owner picks on the dashboard's Features page (`PUT /api/menu-languages`,
`restaurants.second_locale`, null = English only) from `config('locales.menu')`: Arabic, French, Spanish, Turkish,
German, Italian, Russian, Chinese, Hindi, Portuguese. `default_locale` is what
the menu opens in: `en` or the second language. `config('locales.supported')`
is only the **portal's** UI list and has nothing to do with menus.

- `App\Services\Menu\MenuLanguages` owns it: `for()` (`['en', second?]`),
  `default()`, `text()` (that language, else English; never spatie's
  accessor, which fell back to the app locale and showed Arabic-only text
  blank), `map()` for resources, `rules()` for requests, `input()` + `fill()`
  for writes.
- **English is required** for every name (restaurant, category, dish);
  everything else is optional per language.
- The API only reads and writes the menu's **active** languages and merges into
  the stored JSON, so text in a language the owner switched away from stays,
  hidden, and comes back if they switch back. A language sent blank clears
  that language; a field sent as `null` clears all active ones; a field not
  sent is untouched.
- Public menu: `?lang=` accepts only the menu's own languages (anything else
  opens the default), hreflang and the switcher list only those, and an
  English-only menu has no switcher. `dir` comes from the catalogue; fonts
  come from the language's script (see Templates → fonts).
- The menu's own words live in `lang/{code}.json` (same keys as `ar.json`).
  The non-Arabic ones were written by Claude and still want a native read.
- The cart sends the guest's language with an order; the WhatsApp text, any
  error and the line names come back in it.
- A new restaurant gets Arabic as its second language at onboarding; the name
  typed there is saved in English.

A load whose referer is this same menu is **not** recorded as a visit: a
language switch is one visit continuing, not two.

## Opening hours

`restaurants.opening_hours` is one range per weekday (`null` = closed) and
`restaurants.timezone` is what makes "open now" mean anything.
`App\Services\Menu\OpeningHours` owns the logic, including a range whose
close is at or before its open, which runs past midnight.

## Not yet built

- **Push notifications** to a phone (web push, SMS). Orders placed in the menu
  are live through Pusher while a page is open; on WhatsApp, WhatsApp is the
  notification.
- More template designs. Only the `classic` view exists.
- **Taking payment.** Pro/Premium/Custom are requested, not bought: there is no
  checkout, no subscription and no billing provider. An admin assigns a package
  by hand.
- **Real prices.** Package contents are decided (see Packages); the prices
  are still placeholders, set at /admin → Packages when decided.
- **A Free menu in a language other than English.** English is required on
  every menu, so Free (one language) is English-only for now.

## Testing

Three layers, and a change is done when all three are green:

- **PHPUnit here** (`composer test`; `composer test:coverage` fails under the
  coverage floor; needs the `pcov` extension). Tests never touch real storage:
  `tests/TestCase.php` fakes the `local` and `public` disks.
- **Vitest** in the dashboard repo.
- **Playwright end-to-end** in `../qayema-dashboard/e2e` (`npm run e2e` there),
  against this app on port 8001 (`composer serve:e2e`).

**Everything e2e lives in `tests/E2e/`**: nothing in `app/`, `config/`,
`database/`, `routes/` or the root. `bootstrap/app.php` has the only hook: with
`APP_ENV=e2e` it reads `tests/E2e/.env.e2e` (committed, no secrets) instead of
the root `.env`, and registers `E2eServiceProvider`, which points the SQLite
database, the `e2e` media disk, temp uploads (`local` disk) and the log at
`storage/framework/testing/e2e/` (git-ignored by Laravel's own `.gitignore`)
and adds `routes.php` and the `e2e:reset` command. Outside that environment
none of it exists (`tests/Feature/E2e/E2eGuardTest`). `composer e2e:reset`
wipes that folder and migrates + seeds it; it refuses any other database.
Routes: `POST /__e2e/scenario` builds an owner with whatever a test needs
(package and dates, design, languages, content, orders, visits;
`E2eController`), `/__e2e/login` signs a user in, `/__e2e/package`,
`/__e2e/password-reset-token`, `GET /__e2e/media/{path}` serves uploads.
`E2eSeeder`: Classic, the premium Midnight design, `admin@e2e.test` /
`e2e-password`.

**PHPUnit layout: three suites**, each file in the first that fits:

- `tests/Unit/`: extends `PHPUnit\Framework\TestCase`: no app, no database
  (`Enums`, `Services/<Group>`, `Support`).
- `tests/Integration/`: boots the app or the database but sends no request:
  `Services/<Group>/` mirrors `app/Services/<Group>/`, then `Models`, `Enums`,
  `Policies`, `Resources` (API resources), `Rules`, `Mail`, `View`
  (components, stylesheets), `Factories`, `Admin` (form helpers), `E2e`.
- `tests/Feature/`: drives the app from outside: HTTP, Livewire/Filament
  pages, artisan. By area: `Api/` is every `/api/*` endpoint, then `Admin`,
  `Auth`, `Console`, `E2e`, `Journeys`, `Mail`, `Media`, `Menu`, `Onboarding`,
  `Orders` (public ordering), `Packages`, `Portal`, `Requests`, `Security`.

A class with pure methods and app-bound ones has one test in each layer under
the same name (`Unit/Services/Qr/QrStyleTest` is the options mapping, the
twin of the dashboard's; `Integration/Services/Qr/QrStyleTest` the logo and
brand colour). Names are `<Thing>Test` and `<Thing>EdgeTest`, never
`<Thing>ApiTest`. Shared helpers live in `tests/Support/`:
`CreatesOwners` (`owner()`, `ownerOn('pro')`, `published()`, `admin()`,
`defaultPackageIncludes()`) and `EnforcedCsrf`. One suite:
`php artisan test --testsuite=Integration`.
Run `php artisan test`; format with `vendor/bin/pint --dirty`. Switching `actingAs()`
users inside one test trips Filament's session-hash check; use separate tests.
