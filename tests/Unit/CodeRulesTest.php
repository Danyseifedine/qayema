<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Rules the code keeps, checked on the source itself (its PHP tokens, so a
 * comment or a string never counts). Each one is a mistake that already cost
 * something once, or would only show in production:
 *
 * - `env()` outside config/: empty once production caches its config
 *   (php artisan optimize); the trusted proxies once broke that way.
 * - A debugging call left in (`dd`, `dump`, `ray`, `var_dump`).
 * - An HTTP call from a model: a save would wait on the network.
 * - A job that does not implement ShouldQueue runs inside the request.
 */
class CodeRulesTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    /** @return list<string> every PHP file under $directory */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::ROOT.'/'.$directory, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * The calls to plain functions named $names in $file, as "file:line name()".
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    private static function functionCalls(string $file, array $names): array
    {
        $tokens = array_values(array_filter(
            token_get_all((string) file_get_contents($file)),
            fn ($token): bool => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));
        $found = [];

        foreach ($tokens as $index => $token) {
            $next = $tokens[$index + 1] ?? null;
            $previous = $tokens[$index - 1] ?? null;

            $isName = is_array($token) && in_array($token[0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)
                && in_array(strtolower(ltrim($token[1], '\\')), $names, true);
            // A method of the same name ($x->dump(), Foo::env()) and a
            // declaration (function dump()) are not calls to the function.
            $isMethodOrDeclaration = is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true);

            if ($isName && $next === '(' && ! $isMethodOrDeclaration) {
                $found[] = str_replace(self::ROOT.'/', '', $file).':'.$token[2].' '.ltrim($token[1], '\\').'()';
            }
        }

        return $found;
    }

    /** @return array<string, array{0: string}> */
    public static function codeOutsideConfig(): array
    {
        return ['app' => ['app'], 'routes' => ['routes'], 'bootstrap' => ['bootstrap'], 'database' => ['database']];
    }

    #[DataProvider('codeOutsideConfig')]
    public function test_env_is_read_only_in_config(string $directory): void
    {
        $calls = array_merge(...array_map(
            fn (string $file): array => self::functionCalls($file, ['env']),
            array_filter(self::phpFiles($directory), fn (string $file): bool => ! str_contains($file, '/bootstrap/cache/')),
        ));

        $this->assertSame([], $calls, 'Read it in config/*.php and use config() here.');
    }

    public function test_no_debugging_call_is_left_in(): void
    {
        $calls = array_merge(...array_map(
            fn (string $file): array => self::functionCalls($file, ['dd', 'dump', 'ray', 'var_dump']),
            [...self::phpFiles('app'), ...self::phpFiles('routes')],
        ));

        $this->assertSame([], $calls);
    }

    public function test_no_model_calls_the_network(): void
    {
        $offending = array_values(array_filter(
            self::phpFiles('app/Models'),
            fn (string $file): bool => preg_match('/\bHttp::|Illuminate\\\\Support\\\\Facades\\\\Http\b/', (string) file_get_contents($file)) === 1,
        ));

        $this->assertSame([], $offending, 'A model saving must never wait on an HTTP call: defer it, or queue it.');
    }

    public function test_every_job_is_queued(): void
    {
        $jobs = is_dir(self::ROOT.'/app/Jobs') ? self::phpFiles('app/Jobs') : [];

        $this->assertSame([], array_values(array_filter(
            $jobs,
            fn (string $file): bool => preg_match('/\bimplements\b[^{]*\bShouldQueue\b/', (string) file_get_contents($file)) !== 1,
        )));
    }

    public function test_the_rules_catch_what_they_look_for(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'rules');
        file_put_contents($file, <<<'PHP'
            <?php
            // env('IN_A_COMMENT') is fine.
            $a = env('APP_KEY');
            $b = \env('FULLY');
            $c = $config->env('A_METHOD');
            $d = Str::dump('static');
            dd($a);
            function dump() {}
            PHP);

        try {
            $this->assertSame([str_replace(self::ROOT.'/', '', $file).':3 env()', str_replace(self::ROOT.'/', '', $file).':4 env()'], self::functionCalls($file, ['env']));
            $this->assertSame([str_replace(self::ROOT.'/', '', $file).':7 dd()'], self::functionCalls($file, ['dd', 'dump']));
        } finally {
            unlink($file);
        }
    }
}
