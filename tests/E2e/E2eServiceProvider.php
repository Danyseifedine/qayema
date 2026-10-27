<?php

namespace Tests\E2e;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Everything the Playwright suite (../qayema-dashboard/e2e) needs from this
 * app, registered by bootstrap/app.php only when APP_ENV=e2e, which also
 * makes Laravel read tests/E2e/.env.e2e instead of the root .env.
 *
 * Whatever the e2e server writes lands under storage/framework/testing/e2e:
 * the SQLite database, uploaded media, temp uploads and the log. Nothing
 * reaches the MySQL database or the real storage folders.
 */
class E2eServiceProvider extends ServiceProvider
{
    /** Sent by the writing help's e2e tests to switch it on for their requests. */
    public const WRITING_HELP_HEADER = 'X-E2E-Writing-Help';

    /**
     * The cache those requests use: on disk, so a day's AI uses and the
     * locks carry from one request to the next, where the suite's own array
     * cache forgets everything with each request.
     */
    public const WRITING_HELP_CACHE = 'e2e-writing-help';

    /** The pretend Gemini's category line: 60 to 70 characters, as WritingHelp holds them. */
    public const CATEGORY_LINE = 'Warm plates to share with friends at the start of every long meal.';

    /** The pretend Gemini's restaurant line: 100 to 110 characters. */
    public const RESTAURANT_LINE = 'Good food and good company come together here, for slow lunches and easy dinners with the people you love.';

    public static function root(string $path = ''): string
    {
        return storage_path('framework/testing/e2e'.($path === '' ? '' : '/'.$path));
    }

    public static function databasePath(): string
    {
        return self::root('database.sqlite');
    }

    public function register(): void
    {
        config([
            // Several server workers share one file: wait for a lock rather
            // than fail, let readers run beside a writer, and take the write
            // lock when a transaction begins: in WAL mode a transaction that
            // read first fails at once ("database is locked") if another
            // worker committed in between.
            'database.connections.sqlite' => [
                ...config('database.connections.sqlite'),
                'database' => self::databasePath(),
                'busy_timeout' => 10_000,
                'journal_mode' => 'wal',
                'transaction_mode' => 'IMMEDIATE',
            ],
            // MEDIA_DISK=e2e: uploaded images, served by routes.php.
            'filesystems.disks.e2e' => [
                'driver' => 'local',
                'root' => self::root('media'),
                'url' => rtrim((string) config('app.url'), '/').'/__e2e/media',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],
            // Temp uploads (MediaService::tempRoot()) and the log.
            'filesystems.disks.local.root' => self::root('local'),
            'cache.stores.'.self::WRITING_HELP_CACHE => ['driver' => 'file', 'path' => self::root('cache'), 'lock_path' => self::root('cache')],
            'logging.channels.single.path' => self::root('logs/e2e.log'),
        ]);

        // The dashboard's writing help runs its real path (limits, counting,
        // the length checks) on a pretend Gemini, for a test that asks for it
        // with the X-E2E-Writing-Help header; every other test sees it off,
        // as a server with no key does, so their pages and baselines hold.
        // Decided here, before any provider boots: the rate limiter is made
        // while the app boots, on whatever cache is the default by then.
        if ($this->app->bound('request') && $this->app['request']->hasHeader(self::WRITING_HELP_HEADER)) {
            config([
                'services.writing_help.provider' => 'gemini',
                'services.writing_help.model' => null,
                'ai.providers.gemini.key' => 'e2e-not-a-key',
                'cache.default' => self::WRITING_HELP_CACHE,
            ]);
        } else {
            // Off for every other request, whatever the developer's .env
            // holds: a real key there must never be spent by a test.
            config(['services.writing_help.provider' => null]);
        }
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/routes.php');

        $this->commands([E2eResetCommand::class]);

        // "Use my current location" asks OpenStreetMap for the street; the
        // suite never leaves the machine, so it gets the same street always,
        // and Gemini the answers below.
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => ['road' => 'Bliss Street', 'suburb' => 'Ras Beirut', 'city' => 'Beirut', 'country' => 'Lebanon'],
            ]),
            'generativelanguage.googleapis.com/*' => fn (Request $request) => Http::response(self::geminiAnswer($request->data())),
        ]);
    }

    /**
     * What the pretend Gemini answers, in its own shape: a translation reads
     * "Translated: <the text>" (each of a list's too), a category's line and the restaurant's are
     * fixed lines of the lengths WritingHelp holds them to.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public static function geminiAnswer(array $body): array
    {
        $instructions = (string) ($body['system_instruction'] ?? '');

        $last = self::lastText($body['input'] ?? []);
        // A list (a dish's save) reads "Translated: <the text>" for each.
        $list = json_decode($last, true);

        $answer = match (true) {
            str_contains($instructions, "a list of a restaurant's") => ['texts' => array_map(fn (mixed $text): string => 'Translated: '.$text, is_array($list) ? $list : [])],
            // A description writer answers with its versions (WritesVersions).
            str_contains($instructions, "category's title") => ['texts' => [self::CATEGORY_LINE]],
            str_contains($instructions, 'introduces itself') => ['texts' => [self::RESTAURANT_LINE]],
            default => ['text' => 'Translated: '.$last],
        };

        return [
            'status' => 'completed',
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => json_encode($answer)]]]],
        ];
    }

    /** The last text the request carries: the owner's own words. */
    private static function lastText(array $input): string
    {
        $texts = [];
        array_walk_recursive($input, function (mixed $value, string|int $key) use (&$texts): void {
            if ($key === 'text' && is_string($value)) {
                $texts[] = $value;
            }
        });

        return (string) end($texts);
    }
}
