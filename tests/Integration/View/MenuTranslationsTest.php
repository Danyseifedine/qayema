<?php

namespace Tests\Integration\View;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The menu's own words in every menu language (config('locales.menu')):
 * lang/{code}.json for each one but English, which is the keys themselves.
 * A string a guest can see that one language lacks shows up in English in
 * the middle of that menu, and a translation that drops a :placeholder
 * loses the dish, the table or the order number it was meant to carry.
 */
class MenuTranslationsTest extends TestCase
{
    /** What guests read besides the menu's views: its order errors, its SEO and the owner's WhatsApp message. */
    private const GUEST_CODE = [
        'app/Http/Controllers/PublicMenuController.php',
        'app/Http/Controllers/PublicOrderController.php',
        'app/Http/Middleware/VerifyMenuToken.php',
        'app/Http/Requests/PlaceOrderRequest.php',
        'app/Services/Orders/OrderPlacer.php',
        'app/Services/Orders/WhatsAppLink.php',
        'app/Services/Offers/OfferSchedule.php',
        'app/Services/Menu/MenuSeo.php',
    ];

    public function test_every_menu_language_has_its_words(): void
    {
        foreach ($this->languages() as $code) {
            $this->assertFileExists(lang_path("{$code}.json"));
            $this->assertIsArray($this->words($code), "lang/{$code}.json is not valid JSON");
        }
    }

    public function test_every_string_a_guest_can_see_is_translated_in_every_menu_language(): void
    {
        $missing = [];
        foreach ($this->languages() as $code) {
            $words = $this->words($code);
            foreach ($this->guestKeys() as $key) {
                if (! array_key_exists($key, $words)) {
                    $missing[] = "{$code}: {$key}";
                }
            }
        }

        $this->assertSame([], $missing);
    }

    /** Arabic also holds the dashboard API's words; the other languages are only the menu's. */
    public function test_the_menu_languages_share_one_set_of_keys(): void
    {
        $arabic = array_keys($this->words('ar'));
        $first = null;
        foreach ($this->languages() as $code) {
            if ($code === 'ar') {
                continue;
            }
            $keys = array_keys($this->words($code));
            sort($keys);
            $first ??= [$code, $keys];

            $this->assertSame($first[1], $keys, "lang/{$code}.json and lang/{$first[0]}.json differ");
            $this->assertSame([], array_values(array_diff($keys, $arabic)), "lang/{$code}.json has words lang/ar.json lacks");
        }
    }

    public function test_translations_keep_their_placeholders(): void
    {
        $guest = array_flip($this->guestKeys());
        $wrong = [];
        foreach ($this->languages() as $code) {
            foreach ($this->words($code) as $key => $text) {
                $wanted = $this->placeholders($key);
                $found = $this->placeholders($text);
                // Never one the key does not have; a plural line may leave
                // out :count ("one more error"), a guest string never.
                $kept = isset($guest[$key]) ? $found === $wanted : array_diff($found, $wanted) === [];
                if (! $kept) {
                    $wrong[] = "{$code}: {$key} => {$text}";
                }
            }
        }

        $this->assertSame([], $wrong);
    }

    /** @return list<string> */
    private function languages(): array
    {
        return array_values(array_diff(array_keys(config('locales.menu')), ['en']));
    }

    /** @return array<string, string> */
    private function words(string $code): array
    {
        return json_decode((string) file_get_contents(lang_path("{$code}.json")), true);
    }

    /**
     * Every literal key the menu's views and GUEST_CODE translate.
     *
     * @return list<string>
     */
    private function guestKeys(): array
    {
        $files = array_map(fn ($file): string => $file->getPathname(), File::allFiles(resource_path('views/menu')));
        foreach (self::GUEST_CODE as $path) {
            $files[] = base_path($path);
        }

        $keys = [];
        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            preg_match_all("/(?:__|trans_choice|\\\$say)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $matches);
            foreach ($matches[1] as $key) {
                $keys[stripslashes($key)] = true;
            }
        }

        return array_keys($keys);
    }

    /** @return list<string> */
    private function placeholders(string $text): array
    {
        preg_match_all('/:([a-z_]+)/', $text, $matches);
        $names = array_values(array_unique($matches[1]));
        sort($names);

        return $names;
    }
}
