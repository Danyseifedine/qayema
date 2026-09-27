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
   in — never leave it.
3. **Always use the component.** Before writing Blade markup, look in
   `resources/views/components/` (especially `components/ui/`) and the Filament
   component set. If a component exists for the job, use it. Never re-implement
   a button, card, field, modal or layout inline. If the existing component does
   not fit, extend it — do not fork it.
4. **Always tell the truth.** Report what actually happened: if `php artisan test`
   failed, a test was skipped, a step was not done, or you are guessing — say it
   plainly in the first sentence. No softening, no reassurance, no claiming
   something works without having run it. The user does not need feelings
   managed; they need accurate status.

# Qayema — Project Architecture

Bilingual (ar/en) restaurant-menu SaaS. Two codebases in this folder:

- **Laravel app (this repo)** — the public portal (landing, legal, contact), auth
  (Google OAuth + email via `/get-started`), a 3-step onboarding wizard, the
  **public menu at `/{slug}`**, the JSON API for the dashboard SPA
  (`routes/api.php`), and the Filament v4 admin panel (`/admin`).
- **`qayema-dashboard/` (separate repo)** — the owner dashboard SPA. React 19 +
  Vite + TS, Sanctum **session-cookie** auth (no tokens), CSRF primed from
  `GET /api/csrf-token` (the body, because a cross-subdomain SPA can't read the
  cookie). **Currently only an auth bootstrap — the UI is unbuilt.**

## Packages

**Four packages: Free, Pro, Premium and Custom.** A restaurant points at one
(`restaurants.package_id`) and that package holds every limit and flag it gets.
Nothing is sold in-app yet: an owner asks for a package and an admin assigns it.

```
Owner → POST /api/packages/request → ContactService::submit()
    → contact_messages row (user_id + package_id) + email to the admin
Admin → /admin → Restaurants → set package_id → limits move immediately
```

- `packages.features` is a JSON map of `App\Enums\Feature` slug => value:
  an integer allowance, **null for unlimited**, or 0/1 for a flag. A key the map
  doesn't carry falls back to that feature's `defaultValue()`, so adding an enum
  case never breaks an existing package.
- The four rows are **fixed**: seeded by `create_packages_table` from
  `config('package.catalog')`, edited at **/admin → Packages**, never created or
  deleted there. `PackageSeeder` is `firstOrCreate`, so seeding a live database
  cannot overwrite an admin's edits.
- `package_ends_at` is an admin-set expiry. Once it passes, `effectivePackage()`
  returns the default package while the assignment stays on the row so an admin
  can still see what lapsed. A restaurant with no write between expiry and the
  next read is stale for up to `package.cache_ttl` (300 s).
- A package request shares the public contact form's durable per-IP quota of
  3/day, and comes back as a **429** carrying `retry_after` when it is hit.
- **Templates grant nothing.** They are pure design, free, and every active one
  is available on every package.

## Limits / entitlements

One rule: **effective value = the package's value + Σ active grants** (limits
add, flags OR, unlimited stays unlimited). Resolved by
`App\Services\Packages\Entitlements`, cached `entitlements:{id}` 300s.

- `App\Enums\Feature` is the registry — adding a limit is one enum case. The
  admin form and the packages table both render from `Feature::cases()`.
- Saving a package flushes **every** restaurant's cache (`Entitlements::flushAll()`
  via the model's `saved` hook); changing one restaurant's package or expiry
  flushes only that one.
- `feature_grants` = per-restaurant grants, managed on the restaurant's
  "Extra slots & add-ons" tab, with source `admin` or `purchase`.
- A limit of **null is unlimited**: `hasReachedXLimit()` is false, the API sends
  `limit: null`, and a grant on top of it leaves it unlimited.

## Templates

A template is a row + a Blade view of the same slug
(`resources/views/menu/templates/{slug}.blade.php`) + its stylesheet
(`public/css/menu-{slug}.css`, cache-busted by `filemtime`). Only the `:root`
colours and font stay inline in the view, because they are the restaurant's
own; tests assert on that `--accent: #XXXXXX` line, so keep its spacing. The
inline SVG icons come from `App\Support\MenuIcons`. Scaffold all of it with:

```bash
php artisan make:menu-template midnight
```

`templates.settings_schema` declares what the owner may change
(`[{key, type, default, options?}]`, types: color/text/boolean/select).
`UpdateTemplateSettingsRequest` builds its validation from that schema at request
time and **rejects any key the template doesn't declare**, so "this design can
change its colours, that one is fixed" is data, not code. An empty schema = a
fixed design.

The public menu controller falls back to `classic` when a template row exists
without its Blade file, so a half-finished template never 500s a guest. The
printable QR card is `resources/views/menu/qr-card.blade.php` (a per-restaurant
public page, not portal content).

## Code layout and names

One name per thing, shared with the dashboard (`../qayema-dashboard`):

| Owner sees | Backend |
|---|---|
| Analytics | `AnalyticsController`, `GET /api/analytics[/advanced]`, `Services/Analytics/MenuStats`, model `MenuSession` (table `menu_sessions`) |
| Design | `TemplateController`, `/api/templates` — a *design* is a `Template` row; the model keeps its name |
| Restaurant | `RestaurantController`, `GET/PUT /api/restaurant`, `RestaurantResource`, `UpdateRestaurantRequest` |
| Features | `FeaturesController`, `PUT /api/features`, column `switched_off` |
| Package | `/api/packages` |
| Account | `/api/account` |

Three words that are never swapped: **plan** = what a restaurant may use
(`restaurant.plan.{qr_studio, ordering, advanced_analytics}` in `/api/user`,
resolved by `Entitlements`); **grant** = an admin giving one restaurant more
than its package (`FeatureGrant`, table `feature_grants`); **switched off** =
what the owner turned off on the Features page (`restaurant.switched_off`).
`App\Enums\Feature` and `Package.features` describe what a *package* contains.

- `app/Services/<Group>/`: `Analytics` (MenuStats, MenuEventRecorder,
  MenuVisitRecorder), `Menu` (MenuLanguages, OpeningHours, MapPoint,
  DisplayOrder), `Orders` (OrderPlacer, WhatsAppLink), `Packages`
  (Entitlements), `Qr` (QrStyle), `Media` (MediaService, UploadLimits),
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
  pre-launch, which makes `migrate:fresh` look like the way to apply one — but
  the local MySQL holds the owner's own working data, binary logging is off,
  and there are no dumps, so a wipe is unrecoverable. Tests run on in-memory
  SQLite and need none of this. To apply an in-place column change to the live
  local DB, ask, or add it with a one-off `Schema::table` in tinker.
- **Translatable** columns are spatie JSON keyed by language. For menu
  content see "Menu languages" below; packages and templates (platform
  content) stay `{en, ar}`, shown in the dashboard's interface language.
- **Media:** Spatie medialibrary on Cloudflare R2, the `r2` disk in
  `config/filesystems.php`, chosen by `MEDIA_DISK=r2`. Temp-upload flow: POST
  an image → optimized to WebP → parked per-user on local disk
  (`storage/app/temp/{user}`) → promoted by key into the media library on the
  next create/update. The raw upload is never stored. The `r2` disk sets no
  `visibility` (R2 has no per-object ACLs; the bucket is public through its
  domain). `phpunit.xml` pins `MEDIA_DISK=public`, so tests can never reach the
  bucket.
- **Every promotion goes through `MediaService::replace()`** — the dashboard's
  `sync()` and onboarding's `saveBranding()` alike. It stores on `MEDIA_DISK`
  and, if that disk is down or not configured, **falls back to the local
  `public` disk** and logs a warning (`Media disk failed; …`). Files that fell
  back stay local; nothing moves them to R2 later. A problem with the file
  itself (too big, missing, wrong type) is not retried. The old image is
  removed only *after* the new one is stored — clearing first, as both paths
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
  `{off: [...]}`, limited to `Restaurant::OPTIONAL_FEATURES`): `orders` (no ordering at all —
  `takesOrders()`), `qr` (studio styling and printable card off, plain code
  kept — `hasQrStudio()`), `analytics` (page hidden), `languages` (English-only
  menu — `MenuLanguages::for()`; `written()` ignores the switch). Nothing is
  deleted by switching one off. The package still decides what can be on.
- Locale middleware alias is `portal.locale`; the session key stays `owner_locale`.
- API requests take their language from `Accept-Language` (`SetApiLocale`,
  first in the `api` group so even a 401 is translated), limited to
  `locales.supported`. Arabic API text: `lang/ar.json` and
  `lang/ar/validation.php` (only the rules the API uses; anything else falls
  back to Laravel's English).
- Filament v4 testing: table **header** actions need
  `callAction(TestAction::make('create')->table())`, not `callAction('create')`.
- `Restaurant::RESERVED_SLUGS` is the single list behind both the public menu
  route constraint and onboarding's slug validation — add new top-level pages there.
- Every `api/*` error is `{message, code}` JSON (see `bootstrap/app.php`); a
  rate limiter's custom response arrives as `HttpResponseException` and must pass through.

## Ordering

A guest builds a cart on the public menu and places an order. It is **stored**
(`orders` + `order_items`) and the guest is then sent to **WhatsApp** with the
order written out — nothing here is realtime, so the hand-off is what actually
reaches the owner.

- `App\Services\Orders\OrderPlacer` is the only way an order is created. Every
  dish is re-read scoped to the restaurant and every price comes from the
  database; a price in the request body is ignored.
- Order lines carry **their own** `name` and `unit_price`. A dish renamed,
  repriced or deleted later must not rewrite what was ordered, which is why
  `order_items.dish_id` is `nullOnDelete`.
- `POST /{slug}/order` is public, on the `web` group (session + CSRF), limited
  to 10/min per IP with **`autoBan: false`**: a dining room is one IP.
- Gated on the `ordering` package flag, which `config/package.php` ships **on**
  for all four tiers. Off means the menu renders with no cart
  and the endpoint 404s.
- The owner reads them at `GET /api/orders`; only `status` is writable.

## Maps

`restaurants.google_maps_url` is the only thing stored; there are no lat/lng
columns. `App\Services\Menu\MapPoint` reads the point back out of that URL
(`?q=`, `?ll=`, `?query=`, `/@lat,lng,17z`) and builds a **keyless
OpenStreetMap** embed — Google's needs an API key and a billing account, and
this page is scanned all day. A shortened `maps.app.goo.gl` link hides its
coordinates behind a redirect, so it gets the directions button but no map.

It mirrors `parseMapCoordinates` / `mapEmbedUrlFor` in the dashboard
(`src/features/settings/hooks/use-current-location.ts`) — change one, change
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

- Gated on `qr_studio`, which `config/package.php` ships **on for every tier**
  for now — per-package customization is to be decided later. When it is, give
  each editor group (colours, shapes, logo, card) its own flag then.
- `restaurants.qr_settings` is a **flat design we own** (`dot_style`,
  `dot_color`, `dot_gradient`, …), not the drawing library's option tree.
  `Restaurant::qrDesign()` merges it over `qrDefaultDesign()` (the simple QR)
  and drops any key the defaults no longer know.
- `App\Services\Qr\QrStyle::options()` is the one place a design becomes
  `qr-code-styling` options. The dashboard mirrors it in
  `src/features/qr/utils/qr-options.ts`; both are tested against the same
  cases (`tests/Unit/Services/Qr/QrStyleTest.php`) — change one, change both.
  Hex checks and the ink-on-a-colour rule live in `App\Support\Color`. The allowed shapes are QrStyle's constants, which
  `QrSettingsRequest` validates against.
- Drawn by `qr-code-styling` 1.9.2 on both sides: npm in the dashboard, and a
  vendored copy at `public/js/qr-code-styling.js` for the printable card at
  `/{slug}/qr`. Same library, same options, same code on the table.
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
  `POST /{slug}/events` (`throttle:menu-events`, never a ban — a dining room
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
  is every package, ranges `7d`/`30d`; `advanced()` (`GET /api/analytics/advanced`)
  and the `90d`/`all` ranges need the `advanced_analytics` flag — **on for
  every package for now**, by the owner's choice, until they decide which
  packages keep it; tests of the locked path switch it off themselves. Days and hours are the restaurant's `timezone` (UTC when
  unset): rows are grouped by UTC hour with `SUBSTR(ts, 1, 13)` — portable
  across MySQL and SQLite — and shifted in PHP. The funnel (visit → cart → order)
  is null when the package does not take orders. Devices, browsers, systems
  and order money are deliberately **not** reported — the owner decided they
  do not help a restaurant (the order itself reaches them on WhatsApp). The
  visit rows still record device/browser/OS.
- `stats:rollup` prunes `menu_sessions` and `menu_events` after 6 months.

## Menu languages

Every menu is written in **English** plus, optionally, **one second language**
the owner picks on the dashboard's Features page (`PUT /api/menu-languages`,
`restaurants.second_locale`, null = English only) from `config('locales.menu')` — Arabic, French, Spanish, Turkish,
German, Italian, Russian, Chinese, Hindi, Portuguese. `default_locale` is what
the menu opens in: `en` or the second language. `config('locales.supported')`
is only the **portal's** UI list and has nothing to do with menus.

- `App\Services\Menu\MenuLanguages` owns it: `for()` (`['en', second?]`),
  `default()`, `text()` (that language, else English — never spatie's
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
  English-only menu has no switcher. `dir` and the extra Google Font
  (El Messiri, Noto Sans SC, Noto Sans Devanagari) come from the catalogue.
- The menu's own words live in `lang/{code}.json` (same keys as `ar.json`).
  The non-Arabic ones were written by Claude and still want a native read.
- The cart sends the guest's language with an order; the WhatsApp text, any
  error and the line names come back in it.
- A new restaurant gets Arabic as its second language at onboarding; the name
  typed there is saved in English.

A load whose referer is this same menu is **not** recorded as a visit — a
language switch is one visit continuing, not two.

## Opening hours

`restaurants.opening_hours` is one range per weekday (`null` = closed) and
`restaurants.timezone` is what makes "open now" mean anything.
`App\Services\Menu\OpeningHours` owns the logic, including a range whose
close is at or before its open, which runs past midnight.

## Not yet built

- **Realtime.** Nothing pushes. The dashboard's Orders page polls every 60s
  while it is open; WhatsApp is the notification.
- More template designs — only the `classic` view exists.
- **Taking payment.** Pro/Premium/Custom are requested, not bought: there is no
  checkout, no subscription and no billing provider. An admin assigns a package
  by hand.
- **What each package actually contains.** The numbers in
  `config/package.php` and the copy in `lang/{en,ar}/portal.php` are marked
  `TODO(packages)` placeholders and must be decided together.

## Testing

PHPUnit, ~770 tests. `tests/Unit/Services/<Group>/` mirrors `app/Services/<Group>/`
and holds only tests that extend `PHPUnit\Framework\TestCase` (no container,
no database); anything that boots the app or touches the DB is a Feature test.
`tests/Feature/<Area>/` groups by area — `Api/` is every `/api/*` endpoint, then
`Admin`, `Auth`, `Console`, `Journeys`, `Media`, `Menu`, `Onboarding`, `Orders`
(public ordering), `Packages`, `Portal`, `Security`. Names are `<Thing>Test` and
`<Thing>EdgeTest`, never `<Thing>ApiTest`. Shared fixtures: `Tests\Concerns\CreatesOwners` (`owner()`,
`ownerOn('pro')`, `published()`, `admin()`).
Run `php artisan test`; format with `vendor/bin/pint --dirty`. Switching `actingAs()`
users inside one test trips Filament's session-hash check — use separate tests.
