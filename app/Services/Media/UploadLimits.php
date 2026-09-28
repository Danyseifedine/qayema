<?php

namespace App\Services\Media;

/**
 * How large an upload this installation can actually take.
 *
 * The app asks for 20 MB — a phone photo straight off the camera — and every
 * upload is then cut down to a small WebP by MediaService. But PHP refuses a
 * file larger than `upload_max_filesize` before a single line of Laravel runs,
 * so the real ceiling is the smaller of the two. Telling an owner "images must
 * be 20 MB or smaller" while the server drops everything over 2 MB sends them
 * looking for a problem in their photo that is not there.
 *
 * The server side is set in three places, all to the same numbers:
 * `public/.user.ini` (PHP-FPM in production), `composer serve` (local), and
 * the web server's own body limit (nginx `client_max_body_size 25m`).
 */
class UploadLimits
{
    /** What the validation rules ask for. */
    public const APP_MAX_BYTES = 20 * 1024 * 1024;

    /** The smallest of the app's limit and PHP's two. */
    public static function effectiveBytes(): int
    {
        return min(self::APP_MAX_BYTES, self::serverBytes());
    }

    /** What PHP will accept, whatever the app asks for. */
    public static function serverBytes(): int
    {
        $limits = array_filter([
            self::toBytes((string) ini_get('upload_max_filesize')),
            self::toBytes((string) ini_get('post_max_size')),
        ], static fn (int $bytes): bool => $bytes > 0);

        return $limits === [] ? self::APP_MAX_BYTES : min($limits);
    }

    /** True when PHP is the binding constraint, which is a misconfiguration. */
    public static function serverIsBelowApp(): bool
    {
        return self::serverBytes() < self::APP_MAX_BYTES;
    }

    /** The effective ceiling as a person would write it, e.g. "20 MB". */
    public static function describe(): string
    {
        $megabytes = self::effectiveBytes() / (1024 * 1024);

        return rtrim(rtrim(number_format($megabytes, 1, '.', ''), '0'), '.').' MB';
    }

    /**
     * One sentence for an upload the server would not take, naming the limit
     * that actually applies. In local development it also names the cause,
     * because a 2 MB ceiling there is a misconfigured dev server rather than
     * a deliberate policy.
     */
    public static function tooLargeMessage(): string
    {
        $limit = self::describe();

        if (self::serverIsBelowApp() && app()->environment('local')) {
            return __('This server only accepts images up to :limit. Start the API with `composer serve` to raise it.', ['limit' => $limit]);
        }

        return __('That image is too large. Images must be :limit or smaller.', ['limit' => $limit]);
    }

    /** `ini_get` returns shorthand like `8M`; this is its byte value. */
    public static function toBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
