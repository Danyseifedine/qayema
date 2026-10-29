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
7. **Never touch the production server unless the user says so.** No
   `ssh qayema-prod` (A2 Hosting, 69.72.244.149) at all: not even a
   read-only look at logs or `php artisan about`, and never a deploy,
   migration, cache clear or file edit. When production would help, say
   what you would run and wait for the user to say go; run only that, then
   stop again. One go-ahead covers one task, never the next. The same
   cPanel account hosts other sites, and the login can reach all of them.
8. **Never touch any project except Qayema.** On the server only
   `~/qayema.com`, `~/dashboard.qayema.com` and Qayema's own crontab line;
   never the other sites on the account (aboulira.com, barja.shop,
   devleb.org, the *.lebify.dev sites and the rest), not to read, report on
   or fix, even when something there looks broken. On this computer only
   the Qayema folders. Narrow any server-wide command to Qayema's paths.

# Qayema: Project Architecture

Bilingual (ar/en) restaurant-menu SaaS. The codebases in this folder:

- **Laravel app (this repo)**: the public portal (landing, legal, contact), auth
  (Google OAuth, or a username + password made at `/create-account`; both
  sign in at `/get-started`, where the box takes an email or a username. The
  email is optional at sign-up; without one, an admin resets the password in
  /admin → Users. Google never links to a username account by its email,
  which was never proven), a 3-step onboarding wizard, the
  **public menu at `/{slug}`**, the JSON API for the dashboard SPA
  (`routes/api.php`), and the Filament v4 admin panel (`/admin`).
- **`qayema-dashboard/` (separate repo)**: the owner dashboard SPA. React 19 +
  Vite + TS, Sanctum **session-cookie** auth (no tokens), CSRF primed from
  `GET /api/csrf-token` (the body, because a cross-subdomain SPA can't read the
  cookie). It also holds the Playwright end-to-end suite for both apps (`e2e/`).
- **`qayema-app/` (Flutter)**: the owner's phone app, over the owner API with
  a Sanctum bearer token (`POST /api/login`, ability
  `AuthController::TOKEN_ABILITY`, 60 days, one per phone).
- **`qayema-admin-app/` (Flutter)**: the admin's phone app, over `api/admin/*`
  with tokens of its own (`POST /api/admin/login`); each app's tokens open
  only its own routes (`AppServiceProvider::keepTokensToTheirApp()`).
- **Admin phone notifications** (Firebase Cloud Messaging): `PushSender` is
  the only sender and never throws; `AdminAlerts` says what goes out.

  **Both apps, their endpoints and the notifications in full:
  [docs/architecture/phone-apps.md](docs/architecture/phone-apps.md).**

## Packages

**Four packages to start: Free, Pro, Premium and Custom** (an admin may add more). A restaurant points at one
(`restaurants.package_id`) and that package holds every limit and flag it gets.
Nothing is sold in-app yet: an owner asks for a package and an admin assigns it.

```
Owner → POST /api/packages/request → ContactService::submit()
    → contact_messages row (user_id + package_id) + email to the admin
Admin → /admin → Inbox → the request → "Apply this package" (or Restaurants →
    Package → Change package / Extend) → PackageAssigner → in force at its start
```

What each ships with (`config/package.php`, pinned by `PackageCatalogTest`;
the admin owns the numbers after install):

| | Free | Pro | Premium | Custom |
|---|---|---|---|---|
| price a month | free | $7.99 | $10 | talk to us |
| dishes / categories / social links | 40 / 8 / 1 | 150 / 15 / 2 | 1,000 / 1,000 (fair use, shown as unlimited) / 10 | unlimited |
| AI budget a month (`ai_budget_cents`; credits at 0.01¢ each) | $0.05 (500) | $0.75 (7,500) | $1.50 (15,000) | $1.50 (15,000) |
| `multiple_languages`, `variants`, `addons`, `appearance`, `analytics` | - | ✓ | ✓ | ✓ |
| `premium_designs`, `qr_studio`, `ordering`, `menu_ordering`, `advanced_analytics`, `offers` | - | - | ✓ | ✓ |
| `offer_banner_limit` / `offer_popup_limit` / `offer_ad_limit` (switched on at once) | (3 / 1 / 1, unused) | (3 / 1 / 1, unused) | 3 / 1 / 1 | 3 / 1 / 1 |

Premium is `is_featured` ("Most popular" on the dashboard and the landing page); only one package holds it, and marking another in the admin takes it off the rest.
The landing page's pricing is read from the
`packages` table by `App\Services\Portal\PricingCards` (names, prices,
"Everything in Pro, plus" lines like the dashboard's), so an admin's edit
shows there at once; only its headings live in `lang/{en,ar}/portal.php`.
The landing copy says only what the product does: no AI, no checkout, no
invented reviews (`tests/Feature/Portal/LandingContentTest`).

- `packages.features` is a JSON map of `App\Enums\Feature` slug => value:
  an integer allowance, **null for unlimited**, or 0/1 for a flag. A key the map
  doesn't carry falls back to that feature's `defaultValue()`, so adding an enum
  case never breaks an existing package.
- The four start from `config('package.catalog')` (seeded by
  `create_packages_table`; `PackageSeeder` is `firstOrCreate`, so seeding a
  live database cannot overwrite an admin's edits). At **/admin → Packages**
  an admin edits them and makes new ones ("New package", `CreatePackage`:
  it opens on the default package's limits, features and AI budget, so it
  never starts unlimited; its slug, lowercase letters, numbers and dashes,
  is chosen then and fixed after). Each has a **crown** (`tier`,
  `App\Enums\PackageTier`: bronze, silver or gold), the metal the dashboard
  draws it in. One package is the **default** (`is_default`: new restaurants
  start on it, one whose package has not started yet uses it, a deleted
  package's restaurants move to it); "Default for new restaurants" on
  another package moves the mark (`PackageObserver`, one at a time like
  "Most popular"). The default is always offered and never deleted, so to
  switch Free off, make another package the default first; restaurants
  already on the old default keep it.
- **Dates:** the assigned package is in force from `package_started_at` (null =
  always): `Restaurant::packageStatus()` (`PackageStatus` Active / Scheduled /
  Expired). Before its start `effectivePackage()` is the default package.
  **An ended package is never swapped for a smaller one** (the owner's rule,
  2026-10-13): the restaurant keeps it, its menu stays online for
  `package.grace_days` (3) past `package_ends_at`, then goes offline
  (`Restaurant::offlineAt()`, `menuOnline()`: switched on and not past the
  grace days; scope `menuOnline()`). Offline is a 404 on everything a guest
  opens (the menu, ordering, tracking, events, the QR card and options, an
  old link) and out of the sitemap, like the admin's switch; the owner may
  still preview. Extending or assigning a package brings it back that
  moment; no job flips anything (`menus:forget-ended` only clears the cached
  menu). `/api/user` sends the package in force with `ends_at` and
  `days_left`, plus `lapsed` (with `ended_at`, `offline_at`, `offline`) or
  `upcoming` (with `starts_at`); the dashboard warns in the grace days and
  shows a renew screen once offline. The admin API adds `menu_online` and
  `package.offline_at`. Query scopes: `packageActive()`,
  `packageScheduled()`, `packageExpired()` (ended, in grace or past it),
  `packageEndingWithin($days)`, `onPackage($id)` (on it now: assigned and
  started, ended included, or the default while the assigned one waits),
  `menuOnline()`.
- **`App\Services\Packages\PackageAssigner` is the one way an admin changes a
  package**: `assign()` (package, start, end|null, note), `extend()` (months
  added to the end, or to today once ended; null = forever), `reset()` (default,
  forever). Every admin action calls it: Change package (row, bulk, the
  ending-soon widget, "Apply this package" on a request), Extend, Back to
  default. The restaurant form's Package section uses the same fields
  (`Restaurants\Schemas\PackageFields`: package, starts, forever / N months /
  until a date, note).
- **History:** `package_changes` (model `PackageChange`) gets a row for every
  write to `Restaurant::PACKAGE_FIELDS`, from `RestaurantObserver`'s `created`/`saved`,
  so no path skips it: from → to, dates, `changed_by` (the signed-in
  admin), and the note (`$restaurant->packageChangeNote`, set before the save).
  Read-only "Package history" relation manager on the restaurant.
- **Active or inactive** (`packages.is_active`, the "Active" switch on the
  Packages list and form): an inactive package leaves the landing page
  and `/pricing` (`PricingCards`, `Package::offered()`), the search offers
  (`StructuredData`), the dashboard's Package page (`/api/packages` lists
  what is offered plus the owner's own package) and package requests (422).
  Restaurants already on it keep it until it ends; the admin can still
  assign it, labelled "(inactive)". The default package is always
  active, and making one inactive takes its "Most popular" mark
  (`PackageObserver::saving`). Written copy that names a package (`lang/*/portal.php`,
  `pages.php`) is not touched: edit it by hand.
- **Card lines** (`packages.highlights`, `{en: [...], ar: [...]}`, the
  admin's "Card lines" section): written, they replace the lines a package
  card works out from its features, on the landing page (`PricingCards`,
  `Package::highlightsIn()`, English when Arabic is empty) and the dashboard
  (`/api/packages` sends `highlights`, `writtenHighlights()` per language).
  Custom ships with its own (`config/package.php`; the migration fills them
  only where none are written): a design for the brand, limits for the
  group, direct help. Empty, the card lists what the package adds.
- The admin home (`App\Filament\Admin\Pages\Dashboard`) is the
  `Overview` figures (restaurants and menus online, new this week, paying
  (`Restaurant::paying()`: switched on, package in force, own or package price above 0)
  with Whish, what they pay a month added up in SQL by `Restaurant::monthlyIncome()`, which the admin app's summary sends too, open inbox), then the
  `PackagesEndingSoon` widget: ending in 14 days or ended in the last 30,
  with Extend / Change package; its header has "Send test notification"
  (`SendTestNotificationAction`, `AdminAlerts::test()`: my phones or every
  admin's, sent at once, and it says why nothing went).
- A package request shares the public contact form's durable per-IP quota of
  3/day, and comes back as a **429** carrying `retry_after` when it is hit.
- **A design made for one brand:** `templates.restaurant_id` (the admin's
  "Only for" on a template). Only that restaurant sees it
  (`Template::scopeAvailableTo()`: the design list, choosing it, `?preview=`),
  picks it whatever its package (`Restaurant::mayUseTemplate()`), and its
  card says "Made for you"; for every other restaurant it is a 404, never a
  fallback (`Template::fallback()`), and it goes when its restaurant is
  deleted (`RestaurantObserver::deleting`, `ownDesigns()`). The admin's
  restaurant form offers only every restaurant's designs and its own.
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
  (the main language only, the second one kept) and choosing a second one in
  `PUT /api/menu-languages` (403; the main language needs no flag);
  `qr_studio`, `ordering`, `advanced_analytics` as below.

## Limits / entitlements

One rule: **effective value = the package's value + Σ active added grants**
(limits add, flags OR, unlimited stays unlimited), **unless a set grant
decides it**: `feature_grants.mode` is `add` (as every grant was) or `set`
(`GrantMode::Set`), and a set grant's `value` is the final say for that
restaurant (a limit lower or higher than the package, null for unlimited; a
flag 0 off or 1 on; the newest when several). `Entitlements::turnedOff()`
names the flags set to 0, which `/api/user` sends as `turned_off` so the
dashboard says "turned off for your restaurant" instead of offering a
package. Rows over a lowered limit stay; no new ones until under it.
Resolved by
`App\Services\Packages\Entitlements`, cached `entitlements:{id}` for 300 s or
until the package starts or a grant ends, whichever is sooner. A
date passing never leaves a stale answer, and no scheduler is needed.

- `App\Enums\Feature` is the registry: adding a limit or a flag is one enum
  case (`kind()`, `defaultValue()` and `label()`/`hint()` in
  `lang/{en,ar}/features.php` list every case, no default arm). The admin form,
  the packages table and `/api/user`'s `plan` (`Feature::flags()`) all render
  from it. A package that does not carry a new key reads it as its default,
  and the admin form fills it so a save never turns it unlimited.
- Saving a package flushes **every** restaurant's cache (`Entitlements::flushAll()`
  from `PackageObserver`): one write however many restaurants, since it bumps
  `entitlements:version`, which every answer's key carries (`cacheKey()`); the
  old answers go unread until they expire. Changing one restaurant's package or expiry
  flushes only that one.
- **The restaurant page's Features section** (`Restaurants/Schemas/FeatureFields`,
  every feature once in `GROUPS`, a test holds it to `Feature::cases()`)
  shows what the restaurant has now, like a package, each with "Package:
  …" or "Changed for this restaurant". Saving writes only what the admin
  changed (`RestaurantFeatures::changed()`, read before the save, so a
  package changed in the same save is not pinned to the old one) as a
  lasting set grant noted "Set on the restaurant page"; set back to the
  package's value, that grant goes.
- `feature_grants` = per-restaurant grants, managed on the restaurant's
  "Extras & overrides" tab (`FeatureGrantsRelationManager`: feature, add or
  set, the amount / on-off / unlimited, source `admin` or `purchase`, an
  optional end and a `note`).
- **What one restaurant pays**: `restaurants.price_cents` (null: its
  package's price, in the package's currency), set on the restaurant's
  Package section and shown to the owner on their Package page
  (`/api/packages` `meta.your_price_cents`); `monthlyPriceCents()` is the
  price in force. `PackageAssigner::assign()` (every Change package action)
  clears it when the package changes; the restaurant's own page saves the
  price box as left. Every change is a Package history
  row (`package_changes.price_cents`). `restaurants.pays_with_whish` records
  that it pays through Whish Money, for the admin only. Neither clears the
  cached menu (`Restaurant::ADMIN_ONLY_FIELDS`). The admin API sends both
  and sets them (`PUT /api/admin/restaurants/{id}/billing`, or with a package change).
- A limit of **null is unlimited**: `Restaurant::hasReached(Feature::DishLimit)`
  is false, the API sends `limit: null`, and a grant on top of it leaves it
  unlimited. `limitOf(Feature)` is the limit in force; a create checks it with
  `ensureRoomFor()` on the restaurant read under its row lock (dishes,
  categories, links; the fair-use or plan message, a 422 on the field).

## Templates

A template is a row + a Blade view of the same slug
(`resources/views/menu/templates/{slug}.blade.php`) + its stylesheet
(`public/css/menu-{slug}.css`, linked with `App\Support\Asset::versioned()`). The view's `<head>`
`@include('menu.partials.theme')`, which loads the owner's fonts and prints
every colour the design declares as a CSS variable (`primary_color` →
`--primary-color` and `--primary-color-ink`), plus `--font`. Classic also
keeps its own `--accent/--bg/--text` lines; tests assert `--accent: #XXXXXX`,
so keep their spacing. The inline SVG icons come from `App\Support\MenuIcons`.
**A design is its layout only**: what the menu does is shared partials in
`resources/views/menu/partials/`, which every design includes once, last in
its `<body>`: `scripts` (the hours on the phone, then `cart` with
`track-sheet`, `dish-sheet`, and the track and nav scripts). The design
keeps the hooks they look for (`data-open-cart`, `data-cart-panel`,
`data-dish`, `data-pop-open`, `data-track`...). Scaffold all of it with:

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

**Ember** (`ember`, "جمر", premium) is the second design: dark and warm, the
cover full-bleed behind the round logo and the name, search in a dark pill
on the cover (the magnifier turns the top bar into a search field held at
the top with a close button; the cover, offers, builds and bottom bar step
aside; menu-nav.js puts `is-searching` on the body and makes the change a
view transition where the browser has one, and the close button or Escape
empties it and goes back to where the guest was, only for a design with
`[data-search-close]`; the language sits in the pill on a wide screen), the
phone, WhatsApp, QR (and the map on a wide screen) as round buttons under
the hours, the categories as tiles of one size (the icon on one line across the row;
a category without one shows its name alone, centred; names in EB Garamond for Latin letters, through the theme
partial's `tile_font`, one weight in the same Google Fonts request; the
tiles' edge, icons and the picked one's glow follow the owner's main colour over a dark olive), dark dish
cards of one height (the name a line, the ingredients up to two) with the
photo fading in from the end side, and a floating bar at the foot of a
phone, icons only (WhatsApp or the social links as the cover's button,
Menu, Language when there is a second one, Location when there is a map,
Cart with its count when the menu takes orders) in
place of Classic's dock. Headings and dish names are the
owner's own font, heavier; until the owner picks one, Ember's Latin font is
Playfair Display (`MenuFonts::DESIGN_DEFAULTS`, `defaultFor()`, also the
default the Appearance page shows), other scripts keep the catalogue's. Its row comes from a migration
(`2026_10_29_000001_add_the_ember_design`), so production and the e2e
database get it; its settings are the gold, the background, the text and
`show_name`. Its stylesheet restyles every shared part (cart, dish sheet,
tracking, offers, pop-ups) dark and gold, with the same behaviour; while a
modal `<dialog>` is open the page behind is locked and blurred (CSS only;
Classic does not lock it).

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
premium_designs, qr_studio, ordering, menu_ordering, analytics, advanced_analytics, offers}` in `/api/user`,
resolved by `Entitlements`); **grant** = an admin giving one restaurant more
than its package (`FeatureGrant`, table `feature_grants`); **switched off** =
what the owner turned off on the Features page (`restaurant.switched_off`).
`App\Enums\Feature` and `Package.features` describe what a *package* contains.

- `app/Services/<Group>/`: `Analytics` (MenuStats over a StatsRange, its
  orders in MenuOrderStats; MenuTraffic, the admin's visit counts and the
  passes MenuStats shares; MenuEventRecorder, MenuVisitRecorder), `Menu` (MenuLanguages, OpeningHours, MapPoint,
  DisplayOrder, DishOptionsSync, MenuDishOptions, FormerMenuLink), `Orders` (OrderPlacer, OrderEditor, WhatsAppLink), `Packages`
  (Entitlements, PackageAssigner), `Qr` (QrStyle, QrDesign: the link, the default, saved and
  shown design), `Media` (MediaService, UploadLimits),
  `Security` (AbuseGuard, Captcha), `Contact` (ContactService), `Portal`
  (OnboardingService), `Ai` (WritingHelp). A new service goes in the group it serves.
  A service's exceptions sit beside it (`Orders/OrderChanged`,
  `Contact/TooManyContactMessages`); there is no `app/Exceptions`.
- `app/Ai/Agents/` holds the AI SDK's agents (see Writing help).
- `app/Support/` is for value helpers with no dependencies (`Color`, `Asset`); anything
  that reads the database is a service.
- Every owner API controller resolves its restaurant through the
  `ResolvesRestaurant` trait (403 when the user has none); never copy the
  helper. `PackageRequestController` is the one exception on purpose: a user
  without a restaurant may still ask for a package. A row is authorised
  through its policy (`$this->authorize()`: `CategoryPolicy`, `DishPolicy`,
  `RestaurantSocialLinkPolicy`, `OrderPolicy`), never an inline id check;
  `OrderPolicy`'s `manage` (the dashboard's move, edit and delete) is the
  owner's own orders only, refused with the same words as
  `ResolvesRestaurant`; its `delete` also lets an admin remove orders in
  /admin.
- Validation is always a Form Request, including one-field ones
  (`AnalyticsRangeRequest`, `UpdateOrderRequest`, `UpdateDishAvailabilityRequest`)
  and onboarding (`AdvanceOnboardingRequest`, rules per `_step`). Rules
  several forms share live once in `Requests/Concerns/SharedFieldRules`
  (phone, country, currency, temp image key, a name without control
  characters), with their translated messages.
- `Template::CLASSIC_SCHEMA` is the one copy of the classic design's settings
  (seeder and `make:menu-template`).

## Conventions & gotchas

- **Content:** category = name, an optional one-line description,
  an optional icon and order. The icon is one of 30 Tabler outline icons
  (`App\Support\CategoryIcons`, keys like `pizza`, `coffee`; validated with
  `Rule::in(CategoryIcons::keys())`, null for none), drawn inline in the
  menu's category tabs (not the section headings); the dashboard's picker
  (`src/features/menu/categories/constants/category-icons.ts`) has the same
  list, so add one in both. Dish = name, price, ingredients, one image,
  availability, order. No category images, no tags. Every text's most
  characters (dish name 60, ingredients 300, category name 40 and
  description 120, restaurant name 60 and description 200, a choice's name
  40, an offer's title 60 and description 200) are `App\Support\MenuTextLimits`,
  read by every request, the admin forms and the writing help; the
  dashboard's twin is `src/shared/constants/menu-text-limits.ts`. Ingredients are free text tidied into a clean
  list on every save (`App\Support\Ingredients`, through the dish requests'
  `TidiesIngredients` and the admin form): commas, Arabic commas, dots, new
  lines and "etc." become one list joined with the language's separator
  ("، " in Arabic). The dashboard's twin (`shared/utils/string/ingredients.ts`)
  tidies the box as the owner leaves it: change one, change both.
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
  Spatie's accessor (`$model->name`, what the admin's tables, selects and
  titles read) falls back to English, then to any language written
  (`AppServiceProvider::neverShowWrittenTextBlank()`); a restaurant falls
  back to its main language first (`Restaurant::getFallbackLocale()`).
  Without it, a menu written only in Arabic or French showed "N/A" there.
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
  `contact`); the high-volume ones feed the `AbuseGuard` auto-ban. A limiter
  may return several limits (`menu-events`: 120 a minute per IP and 20 per
  guest on that IP, the `visitor` id the batch names). `AbuseGuard` reads
  one cached list of active blocks (`abuse_guard:blocked`, 60 s, each with
  its end), never a query per address; a `BlockedIp` save or delete forgets it.
- **Strict models** (`AppServiceProvider::keepModelsStrict()`): locally and
  in the tests a lazy-loaded relation on a model that came with others
  (N+1), an attribute dropped because it is not fillable, and a column read
  that was not selected all throw; fix the query, never loosen the rule.
  Production only logs a lazy load ("Lazy loading: Model::relation").
  A nullable column the database fills itself goes in the model's
  `$attributes`, so a row just created reads it (`Restaurant`, `User`).
- **What saving a model sets off** lives in its observer, named on the
  model (`#[ObservedBy]`): `app/Observers/RestaurantObserver` (first package
  and link, package history, former links, the owner's account, the menu's
  cache), `PackageObserver`, `FeatureGrantObserver`. Smaller hooks stay in
  `booted()` (`BlockedIp`, `DiningTable`) or a trait (`ClearsMenuCache`).
- **Only through Cloudflare** (`OnlyThroughCloudflare`, first of all
  middleware): with `CLOUDFLARE_ORIGIN_SECRET` set, a request without
  Cloudflare's `X-Origin-Secret` header gets a 403, so nobody reaches the
  server around Cloudflare and `TRUSTED_PROXIES=*` cannot be abused;
  127.0.0.1 needs none. Empty: off (local, tests, e2e).
- **Every log line names its request** (Laravel's Context, set in
  AppServiceProvider): `route`, `restaurant` (the slug in the address) and
  `user_id`, next to the trace id; queued jobs carry it. Context lands in a
  record's `extra`, which the OpenTelemetry handler does not send, so the
  `otlp` channel's tap (`Services/Health/SendLogContextToGrafana`) hands it
  over as `context`.
- **The dashboard's errors reach the log**: `POST /api/client-errors` (its
  error boundary, script errors, uncaught promises, and requests that got
  no answer, `source: network`, "blocked" when the host's bot protection
  refused them; `ClientErrorRequest`) and
  `POST /api/csp-report` (its Content-Security-Policy), public, outside CSRF,
  `throttle:client-errors` (10 a minute), logged as warnings
  ("Dashboard error:", "Dashboard CSP blocked:").
- Session lifetime is intentionally 1 year (the SPA rides it). So a
  stranger is not given one where none is needed: the web group's
  StartSession is `StartSessionForReturningVisitors` (bootstrap/app.php),
  and routes grouped under its `ONLY_RETURNING` attribute (the website's
  pages without a form, and every guest route of the menu) store no session and send no session cookie to
  anyone who does not already have a stored one. A crawler or a new guest
  gets a session for that request only. Contact, sign-in and everything
  else start one as before. The website language a public page remembers
  for the sign-in pages is its own cookie, `owner_locale`
  (`SetPortalLocale::remember`), not session data. A test sends it with
  `withCookie`, plus `withCredentials()` on a JSON request.
- `restaurants.switched_off` holds the optional features the owner
  switched off on the dashboard's Features page (`PUT /api/features` with
  `{off: [...]}`, limited to `Restaurant::OPTIONAL_FEATURES`): `orders` (no ordering at all:
  `takesOrders()`), `qr` (studio styling and printable card off, plain code
  kept: `hasQrStudio()`), `analytics` (page hidden), `languages` (the main
  language only: `MenuLanguages::for()`; `written()` ignores the switch), `variants` and
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
- **Admin layout.** The menu: Dashboard, Restaurants (badge: packages to
  renew, `RestaurantResource::toRenew()`: ending within 7 days, or ended
  and still in the grace days), Inbox (`ContactMessageResource`; badge: open
  messages), Users; then the "Menu content" group (Dishes, Categories,
  Social links, across every restaurant); then "Settings" (Packages,
  Templates, Blocked IPs). Lists open on tabs for what is asked most
  (Restaurants: to renew, ended, paying, Whish, switched off; Inbox: open,
  package requests, handled; Users: owners, setup not finished, admins).
  A restaurant's page is one page of tabs (`hasCombinedRelationManagerTabsWithContent`):
  Details (with an "At a glance" side box), Extras & overrides, Package
  history, Orders, Dishes, Categories, Social links; the last three reuse
  their resource's table (`$relatedResource`), which hides its restaurant
  column and filter there. Keep the relation managers in that order: the
  e2e suite opens them by `?relation=` index. The rarer row actions (open
  menu, log in as owner, delete) sit in the row's "Actions" menu. The top
  bar's search (Ctrl K) finds restaurants by name or link and users by
  name, email or username. **Inbox:** `contact_messages.handled_at` (null:
  open; `ContactMessage::open()`, `markHandled()` keeps the first date,
  `reopen()`); "Mark handled" / "Reopen" on the row, the message and in
  bulk (`HandleMessageAction`), and applying a requested package marks it
  handled.
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
  request traces (HTTP, queries with `?` placeholders, cache, queue,
  Livewire; events and views only with `OTEL_INSTRUMENTATION_EVENT`/`_VIEW`,
  a span each costs time) and logs (`otlp` channel, from `info` up, added to the log stack)
  to Grafana when `GRAFANA_OTLP_ENDPOINT` is set; `GRAFANA_AUTH_HEADER` is
  passed as a header map (the SDK does not URL-decode a "key=value" string).
  No metrics, no console traces. Tests and the e2e suite set
  `OTEL_SDK_DISABLED=true`. Every trace carries
  `deployment.environment.name` (APP_ENV). The Privacy Policy names Grafana.
  In production the app sends to Grafana Alloy on the same machine
  (`GRAFANA_OTLP_ENDPOINT=http://127.0.0.1:43180`, no auth header), which
  batches and forwards; on A2 it runs from `storage/app/alloy`, kept up by
  cron, everything labelled `deployment.environment.name=prod`, and
  metrics come from the traces (`spanmetrics`), PHP's own are off
  (`docs/grafana-alloy.md`): PHP has no background thread, and sending straight
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
  `RestaurantObserver::deleting` deletes each dish (the media library removes every
  photo, logo and cover). A restaurant and its owner's account go together:
  `RestaurantObserver::deleted` deletes the owner (never an admin), and
  `ownerIsBeingDeleted` stops the two hooks deleting each other twice. A deleted category leaves its dishes uncategorised.
  The default package is never deleted (hidden button, and
  `PackageObserver::deleting` refuses); restaurants on any other deleted package move
  to the default through `PackageAssigner`, with a note in their history.
  Orders and the package history are read and deleted on the restaurant's
  page, never added or edited there.
- Filament v4 testing: table **header** actions need
  `callAction(TestAction::make('create')->table())`, not `callAction('create')`.
- `Restaurant::RESERVED_SLUGS` is the single list behind both the public menu
  route constraint and onboarding's slug validation. Add new top-level pages there.
- **The menu link can change** (`PUT /api/restaurant/slug`,
  `RestaurantSlugController`, the dashboard's "Menu link" section; the admin
  form too). Printed QR codes keep working: the restaurant's `updated` hook
  keeps each former link (`previous_slugs`, model `PreviousSlug`), and a GET
  to one is a 301 to the same page under today's link with its query kept
  (`?qr=1` still counts the scan; `App\Services\Menu\FormerMenuLink`, wired in
  `bootstrap/app.php` only when no restaurant has that slug, so a normal
  visit pays nothing). A POST to an old link is a 404.
  `App\Rules\AvailableSlug::rules()` is the one list of rules (onboarding,
  the owner's API, the admin app's API, the admin panel's restaurant and
  user forms): 2 to 100 characters (`MAX_LENGTH`), lowercase letters,
  numbers and dashes (`FORMAT`), not reserved, not another restaurant's
  link, not another restaurant's former link; a restaurant may take back its
  own. Onboarding's live check asks `isFree()`. Guests' carts and order bars are
  kept per link in their browser, so a guest mid-order when the link changes
  starts again.
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

Guests order from the menu one way at a time: four package switches, each sellable alone (`ordering` WhatsApp and `menu_ordering` in the menu for delivery and pickup, `dine_in` in the menu and `dine_in_whatsapp` for table orders); with both ways of a kind the owner picks (`order_mode`, `dine_in_mode`). `order_tracking` is its own switch: without it an order placed in the menu is sent and the guest only gets its number. `App\Services\Orders\OrderPlacer` is the only way an order is created: every dish is re-read scoped to the restaurant and every price comes from the database. Guest routes store no session and take the page's `X-Menu-Token` instead of CSRF (`MenuToken`, `VerifyMenuToken`); orders are tracked by a 40-character token in a sheet over the menu, live through Pusher (`OrderNews`, after the response), and a guest may change an order until it is accepted. **Full rules: [docs/architecture/ordering.md](docs/architecture/ordering.md); read it before touching orders, the cart or tracking.**

## Two currencies

A restaurant has its main currency (`restaurants.currency`) and may add one second currency with the rate between them (`second_currency`, `exchange_rate`: how many of the main currency one of the second is worth, 89000 for 1 USD in LBP; the dashboard lets the owner write it either way round, 89,000 by default for USD and LBP). A dish is priced in the main one (`dishes.currency` null) or the second one, its variants and add-ons with it (`Restaurant::currencyOf()`, `rateOf()`, `ownCurrency()`). The menu shows each dish in its own money (`data-currency`, `data-rate` on the card); the cart, the order total and `seen_total` are in the main currency, each second-currency line counted at the rate, line by line, to the cent, half up (`App\Support\Price::toMain()`, twinned by `menu-cart.js` and the dashboard; a cent of rounding per such line is not "dearer"). An order keeps the rate it was placed at (`orders.exchange_rate`, set only when a line used it) and each line its currency (`order_items.currency`, null for the main one), so the owner changing the rate later changes no order; the owner adding a dish counts it at the order's rate. An amount-off offer is in the main currency and comes off a second-currency dish at the rate (`Offer::discounted($price, $rate)`, `Price::fromMain()`, `menu-offers.js`); its "below the cheapest dish" check compares in the main currency. A second currency is not removed or swapped while dishes are priced in it (422 naming how many); made the main one, those dishes simply are in it. Analytics' sales are the orders' totals; top dishes count second-currency lines at their order's rate. Admin: the dish form offers the second currency when the restaurant has one. Onboarding's step 2 sets it too: dollars are suggested beside any other main currency, and a menu in dollars gets the money of its phone's country (`config/countries.php` `currency`) until the owner picks one; the rate is written the way people say it, the dollar as the 1 (1 USD = 89,000 LBP, else 1 second = X main), and `OnboardingService::saveSecondCurrency()` stores it as main per one second (`writtenRate()` reads it back).

## Variants and add-ons

A dish's variants (pick one option of each) and add-ons (pick any), each price added to the dish's; flags `variants` and `addons`, each with its own switch. Written whole by `DishOptionsSync`, shown by `MenuDishOptions`, priced by `OrderPlacer`, snapshotted on `order_items.options`. **Full rules: [docs/architecture/variants-and-addons.md](docs/architecture/variants-and-addons.md).**

## Build your own

A dish built from parts (`build_your_own` flag, on no package: an admin switches it on per restaurant): up to three builds (`DishBuild`), each a part count the guest picks (`split`, a pizza half and half; `pieces`, three scoops) and a choice per part, from a category's dishes or the build's own list (`BuildChoices`; a pizza with no price, priced by its first variant's sizes, is offered too: the guest picks the size first and each part takes its share of that size's price), priced by `BuildPrice` (a part pays its set price for that size, else its share; twinned by `public/js/menu-build.js`), charged by `OrderPlacer`, worded "½ Margherita + ½ Pepperoni" (`OrderItem::built()`). A build's dish is a name and an icon in no category, drawn in a row above the menu's search with no price (`menu.partials.builds`). A restaurant may have `build_limit` of them (3 unless an admin sets another). **Full rules: [docs/architecture/build-your-own.md](docs/architecture/build-your-own.md).**

## Offers

An owner's promotions on the menu (`offers` flag, Premium): a banner carousel, a pop-up and/or a short ad that cannot be skipped (its seconds count down, then an X closes it; the ad after the pop-up is closed), each in its shape (banner rectangle or square, pop-up square or tall, ad a rectangle), as many switched on at once as the package's `offer_*_limit` allow (Premium 3 banners, 1 pop-up, 1 ad; `OfferSlots`; 0 leaves the place out), with schedules and discounts as switches of their own (`offer_schedules`, `offer_discounts`; `OfferRules` keeps off what the package lacks), on a schedule (dates, weekdays or days of the month, times of day, in the restaurant's timezone; `OfferSchedule`, twinned by `public/js/menu-offers.js` on the cached menu and the dashboard's `offer-schedule.ts`), with an optional discount on chosen dishes that `OrderPlacer` charges (the line keeps `offer_id`, `offer_title`, `full_price`): a percent or an amount off the dish's own price with the variants picked and what was built (a pizza's halves; add-ons keep theirs), or a pair deal (`second`, "the second at 50% off", 100 free) on every second dish of the offer in the order the cart sent them, those dishes split into a line of their own; a straight discount on a dish beats a pair deal. `shows_outside_hours` keeps an offer on the menu all day inside its dates while its deal works only in its hours (`Offer::isShownAt()` vs `isLiveAt()`); a tap outside them says when it runs (`OfferSchedule::describe()`). An offer may instead make a dish of its own, its deal (`dishes.offer_id`, `Offer::deal()`, `DealDish`; no discount and no other dishes beside it), on the menu and for sale only while the offer is live (`hidden_deals` and `menu-offers.js` on the cached page, `LiveOffers::liveAmong()` in orders), deleted with the offer. **Full rules: [docs/architecture/offers.md](docs/architecture/offers.md); read it before touching offers, the menu's prices or the cart.**

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
  categories, links and the dishes without a photo (Home's checklist,
  `dishes_without_photo`) with one `loadCount`.
- More read once: `Package::onOffer()` (`once()`, the landing page's cards
  and its search data share it), `Entitlements` reads the grants in force
  once on a miss (the value, then its time to live from the same grants),
  `PackageFields::packageOptions()` is `once()`. An order placed or changed
  is handed its restaurant rather than reading it again, and a change's one
  dish query also names the dishes no longer sold (`OrderChange`). Lists
  eager-load what their resource reads (`/api/templates` with `media`).
- The owner's lists (`/api/user`, dishes, categories, offers, orders,
  tables, links, QR, restaurant) cost the same queries with 3 rows as with
  12, and an order's lines are one insert however many (`Order::addItems()`):
  `tests/Feature/Api/OwnerListQueriesTest`. A wildcard rule that queries
  (`exists`, `unique` on `names.*`, `dish_ids.*`) runs once per item, so a
  batch is checked in the request's `after()` with one `whereIn`
  (`StoreOfferRequest::chosenDishes()`, `NamesTables::namesTaken()`,
  `DiningTable::freshCodes()`).
- A value several parts of one request ask for is `App\Support\PerRequest`
  (kept on the request, so the next one reads it fresh): the admin's Inbox
  and To renew counts (menu badge, tab badge, the Overview), the timezones
  `MenuTraffic::withTotals()` needs, `Template::fallback()`, the admin
  forms' restaurant columns (`MenuTextInputs`). The admin's restaurant list
  loads every row's grants in force with the page, and `Entitlements` uses
  them when loaded (`valuesFrom()`, also what `RestaurantFeatures::save()`
  works each feature out with, without reading again).
- Counts in one pass with conditional sums: `MenuStats::summary()` (views,
  visitors, scans and today's views; orders and those done),
  `MenuTraffic::totals()` (the admin's user page), `qrScans()`,
  `OrderPulse`. The admin's lists add `total_views` and `qr_scans` per row
  with `MenuTraffic::withTotals()`: `menu_days` up to the last
  `LIST_RAW_DAYS` and the raw rows since, met at each timezone's midnight,
  so a page is one query and the totals outlive the 6-month prune. The
  menu's orders are added up in SQL (`MenuOrderStats`), never loaded.
- Counts over time are plain ranges, never `date()`/`month()` on a column,
  so the index does the work: `MenuTraffic::qrScans()` (today, week, month,
  total in one pass, in the restaurant's timezone, `localTimezone()`) and
  `OrderPulse` (three numbers, one query, read from `orders_pulse_index`:
  restaurant, channel, status, fulfilment, guest_updated_at, which the
  orders list's `open` count uses too). `menu_sessions` is indexed
  `(restaurant_id, viewed_at, via_qr, session_id)` so QR and visitor counts
  come from the index (every read of `session_id` names the restaurant, so
  it has no index of its own), and `viewed_at` / `menu_events.occurred_at`
  alone for the nightly prune.

## Deploying (the owner does it; never run from here)

Only a commit CI passed is deployed (`.github/workflows/ci.yml` here and in
the dashboard: style, Larastan, every test at 99% coverage, `composer audit`;
the dashboard's checks, size budget, `dist/` and end-to-end suite). The
steps are `docs/release-checklist.md`; on the server the backend is one
command, `bash deploy.sh` in `~/qayema.com` (`--full` runs every step). It
compares the server's commit with origin/main and runs only what the change
needs, so a code-only release is a pull and `optimize`, with the site up:

- always: `git merge --ff-only`, `php artisan optimize` (config, routes,
  events, views; without it about 120 ms of every request's 170 ms went to
  starting Laravel), a boot check (`artisan about`; on failure the site goes
  into maintenance mode), and `/up` asked at the public address, through
  Cloudflare (127.0.0.1 answers with the shared host's own certificate);
- `composer.json`/`.lock` changed: `composer install --no-dev
  --optimize-autoloader --classmap-authoritative`, in maintenance mode
  (2026-10-12: requests landing mid-install failed);
- a PHP file added or removed under `app/` or `database/`: `composer
  dump-autoload --classmap-authoritative`, since the class map is the only
  place classes are looked up;
- a migration: `migrate --force`, in maintenance mode;
- the menu page (its views, `public/js`, `public/css`, `lang`,
  `PublicMenuController`, `Services/Menu`): `menus:forget-all`.

The queue worker needs no restart: the scheduler starts it fresh every
minute. Any failed step prints FAILED and exits non-zero.

The dashboard: upload its `dist/`.

With config cached, `.env` is no longer read, so `env()` works only inside
`config/*.php`; anywhere else it comes back empty (`tests/Unit/CodeRulesTest`
fails on one). The trusted proxies (`TRUSTED_PROXIES`, Cloudflare's
forwarded IP) are read in `config/security.php` and applied by
AppServiceProvider for that reason (`tests/Feature/Security/TrustedProxiesTest`).

**In the background** (`routes/console.php`; production's only cron line is
`schedule:run` every minute): the queue worker (`queue:work --max-time=55`,
every minute, never two at once, so a job starts within seconds), the
heartbeat `/up` checks, `menus:purge-pending` every minute (the Cloudflare
purges it refused, `PendingPurges`), `menus:forget-ended` hourly, and nightly
`stats:rollup` (adds each finished day of analytics up into `menu_days`,
each restaurant on its own so one failure skips only it, then deletes raw
visits older than `MenuDays::RAW_MONTHS` 10,000 rows a statement, keeping a
failed restaurant's), `queue:prune-failed`, `sanctum:prune-expired` and
`model:prune` (ended `BlockedIp` rows).

**Health:** `GET /up` answers 500 (and logs "Health check failed: ...")
when the database or the cache fails, and in production also when the
scheduler's heartbeat (`storage/framework/schedule-heartbeat`) is over 3
minutes old, a job has waited over 5 minutes or a job failed in the last
hour (`App\Services\Health\HealthChecks`). Uptime checks and alerts:
`docs/grafana-alerts.md`.

## Menu speed

Production (qayema.com, A2 Hosting with Redis turned on) keeps the cache, sessions and rate
limits in Redis (`CACHE_STORE=redis`, `SESSION_DRIVER=redis`): on the
database they cost 4 to 6 queries on every request. The account has its own
Redis on its own port with a password (cPanel's Redis page), set in
`REDIS_HOST`, `REDIS_PORT` and `REDIS_PASSWORD`; `redis-cli` needs `-p` and
the password too. Local and the tests keep the database and array stores.

**Cloudflare keeps the menu page** (`tests/Feature/Menu/MenuEdgeCacheTest`,
`MenuCacheTest`). A guest's menu page (`/{slug}` and every `?lang=`,
`?table=`, `?qr=` address) is sent with `Cache-Control: public, max-age=0,
s-maxage=3600` (`CacheMenuForGuests`), so Cloudflare answers the next guests
itself in under 0.1 s and the server is not asked. Never kept: a request
with a session cookie (owner, admin), `?preview=`, `?track=`, anything but a
200. Any other query key (`fbclid`, `utm_*`, `gclid`) would be a miss of its
own, so the menu answers it with a 301 to the same address with only
`lang`, `table`, `qr`, `preview` and `track`, in their order, kept at the
edge too (`DropUnknownMenuQuery`; a new key the menu reads goes in its
`KEYS`). An unknown link's 404 is kept 5 minutes (bootstrap/app.php); a
restaurant created or renamed clears its link. `/{slug}/qr-options` is kept
the hour (under the menu's prefix), `/sitemap.xml` the hour (and built once
an hour). It works because the page holds nothing of one guest: no session or
cookie (StartSessionForReturningVisitors), the visit counted by the phone
(menu-track.js), open or closed and today's hours worked out on the phone
(`public/js/menu-hours.js`, the twin of `OpeningHours`; the cart asks
`QAYEMA_HOURS.isOpen()`), and the order token valid a day (`MenuToken`).
- **A change clears it at once** (`App\Services\Menu\MenuCache`, one per
  request, sent after the response): a saved or deleted row of
  `Models\Concerns\ClearsMenuCache` (Category, Dish, Offer, RestaurantSocialLink,
  DiningTable, FeatureGrant), the restaurant (its old link too), a reorder
  (`DisplayOrder`, which saves no model), a photo (spatie Media, in
  AppServiceProvider). A package or a design clears every menu by the
  menus' host (`{"hosts": ["qayema.com"]}`, `SiteAddress::host()`), never
  `purge_everything`, which would empty the zone's other hosts too. A save
  that changed nothing (`wasChanged()`) clears nothing. Cloudflare is asked
  by prefix (`qayema.com/<slug>`), which covers every address under it; a
  refused call (the Free plan allows five a minute) goes into
  `PendingPurges` (one cache key, under a lock), which `menus:purge-pending`
  sends every minute in at most five calls of 100, the longest waiting
  first; one still refused after 15 minutes is dropped and logged as an
  error. A new writer of menu data that saves no model must call
  `MenuCache::forget()` itself.
- **What Cloudflare needs** (the owner sets it up): a cache rule for
  qayema.com, path not starting with /api, /admin or /livewire, "Eligible
  for cache", edge TTL "Use cache-control header if present, bypass cache
  if not", browser TTL "Respect origin"; and `CLOUDFLARE_ZONE_ID` +
  `CLOUDFLARE_API_TOKEN` (only Zone, Cache Purge) in `.env`. Without them
  nothing is cleared and a copy lives at most the hour. Worth turning on
  too: Smart Tiered Cache (one Cloudflare data centre asks the server, the
  rest ask it) and a rate-limit rule on `POST /*/events` in front of the
  app's own.
- Time-driven changes save nothing (a package starting, a menu going
  offline past its grace days, a grant ending, overnight):
  `menus:forget-ended` (hourly, `ForgetEndedMenus`) clears those menus; the server refuses what the package no longer allows meanwhile (an
  order). A release that changes the menu page clears every menu
  (`menus:forget-all`, run by `deploy.sh`).
  A refused purge is logged as a warning at once ("Menu cache purge
  refused"), and as an error ("Menu cache purge failed") once it has waited
  15 minutes.

The public menu is opened on a phone, often on mobile data, so it stays light
(`tests/Feature/Menu/MenuSpeedTest`):
- Pictures: a dish photo has a 240px `thumb` (the card and the cart) and the
  full one only for its sheet (`data-photo`), or, on a dish without
  choices, the big view a tap on its photo opens (`#pop-photo`, `menu-nav.js`); the cover has a 960px `phone`
  version in a `srcset`, is never `loading="lazy"` and has
  `fetchpriority="high"`. Conversions are made with the upload (`nonQueued`);
  a photo from before them falls back to the full one until
  `php artisan media-library:regenerate --only-missing` runs.
- Uploads go to R2 with `Cache-Control: public, max-age=31536000, immutable`
  (`config/media-library.php`): a new photo is always a new address.
- Fonts load without blocking the first paint (`media="print"` swapped on
  load, `display=swap`).
- Every local script and stylesheet is linked with `?v=filemtime(...)`
  (`App\Support\Asset::versioned('js/menu-cart.js')`):
  `public/.htaccess` keeps css/js for a year, so one without a version would
  go stale for guests. It also compresses text (mod_deflate).
- Opening the menu writes nothing: no session for a guest (Redis) and no
  visit row (the page reports its visit, see Analytics). Entitlements are
  memoized per request (`Cache::memo()`), so a view is one read of them.
- The QR pop-up is ready before it is tapped: once the page has loaded and
  the phone is idle, `menu-nav.js` fetches the library and
  `/{slug}/qr-options` (browser-cached 5 minutes) and draws the code in the
  closed dialog. `QrStyle::logoDataUrl()` keeps the inlined logo per media id,
  so those requests do not download the logo from the disk each time.

## Maps

Only `restaurants.google_maps_url` is stored; `MapPoint` reads the point out of it and builds a keyless OpenStreetMap embed (mirrored in the dashboard). The map frame must send a Referer (`sandbox="allow-scripts allow-same-origin"`, no `referrerpolicy`) or every tile is a 403. **Details: [docs/architecture/maps.md](docs/architecture/maps.md).**

## QR studio

The QR code always encodes `/{slug}?qr=1`, whatever the design. `restaurants.qr_settings` is our own flat design; `QrStyle::options()` is the one place it becomes `qr-code-styling` options, twinned with the dashboard's `qr-options.ts` (change one, change both). Styling, the logo and the card need `qr_studio`. **Details: [docs/architecture/qr-studio.md](docs/architecture/qr-studio.md).**

## Analytics

The page reports its own visit and what guests do (`public/js/menu-track.js` to `POST /{slug}/events`, written after the 204 has gone), so a cached page still counts and a crawler does not; one guest is the id their browser keeps (`qayema-visitor`). Read by `MenuStats` (`analytics`, `advanced_analytics` flags), in the restaurant's timezone; raw rows are deleted after 6 months, once each day is added up into `menu_days`, which "All time" reads for older days. **Full rules: [docs/architecture/analytics.md](docs/architecture/analytics.md).**

## Menu languages

A main language (required for every name) plus one optional second language (`multiple_languages`), from `config('locales.menu')`; `config('locales.supported')` is only the portal's. `App\Services\Menu\MenuLanguages` owns it: read with `text()`/`map()`, never spatie's accessor; write only the active languages, merged into the stored JSON. **Full rules: [docs/architecture/menu-languages.md](docs/architecture/menu-languages.md).**

## Opening hours

`restaurants.opening_hours` holds up to three shifts a weekday (null = closed), in `restaurants.timezone`; `App\Services\Menu\OpeningHours` owns the rules (only a day's last shift may run past midnight, no overlaps), mirrored by `public/js/menu-hours.js` on the cached menu. **Details: [docs/architecture/opening-hours.md](docs/architecture/opening-hours.md).**

## Writing help (AI)

The dashboard's Translate and Write with AI buttons, through `laravel/ai` (`App\Services\Ai\WritingHelp`, agents in `app/Ai/Agents`). Off until a provider and its key are set; held to its credits a month alone (no uses a day), logged by `WritingHelpLog`; credits a month (`WritingHelpCredits`: a package's AI budget in dollars at what a credit is worth, set at /admin → Settings → AI credits with each action's price; one restaurant's own number is a grant), checked before every call so the real cost never passes what they are worth, and owners only ever see credits; the server checks every answer (lengths, no dashes, no invented claims); `PaceWritingHelp` keeps it from starving the menus. Tests never call a provider. **Full rules: [docs/architecture/writing-help.md](docs/architecture/writing-help.md).**

## Not yet built

- **Push notifications to owners** (web push, SMS, the owner app). Orders
  placed in the menu are live through Pusher while a page is open; on
  WhatsApp, WhatsApp is the notification. Admins' phones get them (see the
  admin app above).
- More template designs. Two views exist: `classic` (free) and `ember`
  (premium); the e2e database's Midnight row has no view and draws in Classic.
- **Taking payment.** Pro/Premium/Custom are requested, not bought: there is no
  checkout, no subscription and no billing provider. An admin assigns a package
  by hand.

## Testing

Three layers, and a change is done when all three are green:

- **PHPUnit here** (`composer test`; `composer test:coverage` fails under the
  coverage floor; needs the `pcov` extension). `composer check` is Pint,
  then Larastan (`composer analyse`, level 6, `phpstan.neon`; its baseline
  `phpstan-baseline.neon` only shrinks: fix an entry when touching its file
  and regenerate, never add one), then the coverage run; CI runs it on every
  push. `tests/Unit/CodeRulesTest` checks the source itself (no `env()`
  outside config, no `dd`/`dump`, no HTTP in a model, every job queued). Tests never touch real storage:
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
`E2eSeeder`: Classic, the premium Midnight design (Ember comes from its migration), `admin@e2e.test` /
`e2e-password`.

**Demo restaurant** (`database/seeders/DemoSeeder.php`, not called by
`DatabaseSeeder`): "Olive & Ember" at `/demo`, owner `demo` /
`qayema-demo`, on Premium in English and Arabic, with a logo, a cover,
six categories of dishes with photos, variants and add-ons, three banners,
a pop-up and an ad, tables, links, three weeks of orders and a month of
visits. Its pictures (`database/seeders/demo/`, CC0 and public domain,
listed in `sources.json`; the logo drawn for it) go through
`MediaService::storeUpload()`, so they come out as WebP on `MEDIA_DISK`.
Seeding it again starts it over. `php artisan db:seed --class=DemoSeeder`
(`tests/Integration/Database/DemoSeederTest`).

**PHPUnit layout: three suites**, each file in the first that fits:

- `tests/Unit/`: extends `PHPUnit\Framework\TestCase`: no app, no database
  (`Enums`, `Services/<Group>`, `Support`).
- `tests/Integration/`: boots the app or the database but sends no request:
  `Services/<Group>/` mirrors `app/Services/<Group>/`, then `Models`, `Enums`,
  `Policies`, `Resources` (API resources), `Rules`, `Mail`, `View`
  (components, stylesheets), `Factories`, `Admin` (form helpers), `E2e`.
- `tests/Feature/`: drives the app from outside: HTTP, Livewire/Filament
  pages, artisan. By area: `Api/` is every `/api/*` endpoint, then `Admin`,
  `Auth`, `Console`, `E2e`, `Health` (`/up`, log context), `Journeys`, `Mail`, `Media`, `Menu`, `Onboarding`,
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
