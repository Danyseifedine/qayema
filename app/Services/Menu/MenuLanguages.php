<?php

namespace App\Services\Menu;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The languages a restaurant's menu is written in: its main language (the
 * owner's choice, English unless they pick another), plus the one second
 * language they chose, if any (config('locales.menu')).
 *
 * Translations live in spatie JSON columns. Only the restaurant's *active*
 * languages are ever read or written through the API; text in a language the
 * owner has since switched away from stays in the JSON, hidden, and comes back
 * if they switch back.
 */
class MenuLanguages
{
    /** A new menu's main language, and the one used where there is no restaurant. */
    public const DEFAULT_MAIN = 'en';

    /**
     * @return array<string, array{name: string, flag: string, rtl: bool, script: string}>
     */
    public static function catalogue(): array
    {
        return config('locales.menu', []);
    }

    /**
     * Every language a menu can be written in, the main one included.
     *
     * @return array<int, string>
     */
    public static function choices(): array
    {
        return array_keys(self::catalogue());
    }

    /**
     * The menu's main language: the one every name is required in, and the
     * only one shown while the second language is off. English for a code
     * no longer in the catalogue.
     */
    public static function main(Restaurant $restaurant): string
    {
        $main = (string) $restaurant->main_locale;

        return in_array($main, self::choices(), true) ? $main : self::DEFAULT_MAIN;
    }

    /**
     * The language columns for a new main language. Choosing the current
     * second language swaps the two (both texts already exist, nothing is
     * copied); choosing another keeps the second one. The menu keeps opening
     * in the main language if it did.
     *
     * @return array{main_locale: string, second_locale: string|null, default_locale: string}
     */
    public static function withMain(Restaurant $restaurant, string $main): array
    {
        $oldMain = self::main($restaurant);
        $second = $restaurant->second_locale;
        $default = (string) $restaurant->default_locale;

        if ($second === $main) {
            $second = $oldMain === $main ? null : $oldMain;
        }

        return [
            'main_locale' => $main,
            'second_locale' => $second,
            'default_locale' => $default === $oldMain || ! in_array($default, [$main, $second], true) ? $main : $default,
        ];
    }

    /**
     * The languages the menu is shown in: the main one, then the second,
     * unless the package has no `multiple_languages` or the owner switched
     * "Menu languages" off on the Features page, which leaves the main
     * language alone without forgetting the second one.
     *
     * @return array<int, string>
     */
    public static function for(Restaurant $restaurant): array
    {
        return $restaurant->showsSecondLanguage()
            ? self::written($restaurant)
            : [self::main($restaurant)];
    }

    /**
     * The main language, then the second one the owner chose, whether or not
     * the feature is switched on right now.
     *
     * @return array<int, string>
     */
    public static function written(Restaurant $restaurant): array
    {
        $main = self::main($restaurant);
        $second = $restaurant->second_locale;

        return $second && $second !== $main && in_array($second, self::choices(), true)
            ? [$main, $second]
            : [$main];
    }

    /** The language the menu opens in: the owner's choice when it is still one of theirs. */
    public static function default(Restaurant $restaurant): string
    {
        $default = (string) $restaurant->default_locale;

        return in_array($default, self::for($restaurant), true) ? $default : self::main($restaurant);
    }

    /**
     * The signed-in owner's menu languages, for a dashboard request or
     * resource, the main one first. English alone when there is no restaurant.
     *
     * @return array<int, string>
     */
    public static function forOwner(?User $user): array
    {
        $restaurant = $user?->restaurant;

        return $restaurant === null ? [self::DEFAULT_MAIN] : self::for($restaurant);
    }

    /**
     * Validation for one translatable field: an entry per menu language, each
     * a string up to `$max`. The first language, the main one (`for()` and
     * `forOwner()` put it first), carries `$mainRule` ('required',
     * 'required_with:name', or 'nullable').
     *
     * @param  array<int, string>  $languages
     * @return array<string, array<int, string>>
     */
    public static function rules(string $field, array $languages, int $max, string $mainRule = 'nullable'): array
    {
        $rules = [];

        foreach (array_values($languages) as $index => $code) {
            $rules["{$field}.{$code}"] = [$index === 0 ? $mainRule : 'nullable', 'string', "max:{$max}"];
        }

        return $rules;
    }

    /**
     * The main language's own name for a message ("Français"): the language
     * a "required" error points the owner to.
     */
    public static function nameOf(string $code): string
    {
        return (string) (self::catalogue()[$code]['name'] ?? strtoupper($code));
    }

    /**
     * "<what> is required in <the main language>" for a translatable field,
     * keyed as the validator looks it up (`name.fr.required`).
     *
     * @param  array<int, string>  $languages  the menu's languages, the main one first
     * @param  array<int, string>  $rules  the rule names the message covers
     * @return array<string, string>
     */
    public static function requiredMessages(string $field, array $languages, string $message, array $rules = ['required']): array
    {
        $main = $languages[0] ?? self::DEFAULT_MAIN;
        $text = __($message, ['language' => self::nameOf($main)]);

        return collect($rules)->mapWithKeys(fn (string $rule): array => ["{$field}.{$main}.{$rule}" => $text])->all();
    }

    public static function isRtl(string $code): bool
    {
        return (bool) (self::catalogue()[$code]['rtl'] ?? false);
    }

    /**
     * A translatable field in one language, falling back to `$main` (the
     * menu's main language, the one every name is required in), then to
     * whatever language it was written in: after the main language changes,
     * a dish not yet written in the new one still shows its old name rather
     * than nothing. Never spatie's accessor, which falls back to the app
     * locale and so showed Arabic-only text as blank.
     */
    public static function text(Model $model, string $field, string $locale, ?string $main = null): string
    {
        // Every language at once: spatie's getTranslation() decodes the
        // column several times per call, and a menu asks hundreds of times.
        $all = $model->getTranslations($field);

        foreach ([$locale, $main] as $code) {
            if ($code !== null && ($all[$code] ?? '') !== '') {
                return (string) $all[$code];
            }
        }

        foreach ($all as $text) {
            if ((string) $text !== '') {
                return (string) $text;
            }
        }

        return '';
    }

    /**
     * How many categories and dishes have no name in the menu's main
     * language yet: after a switch to a new main language, what the owner
     * still has to write (guests see the old name until then).
     *
     * @return array{categories: int, dishes: int}
     */
    public static function missingInMain(Restaurant $restaurant): array
    {
        $main = self::main($restaurant);

        return [
            'categories' => $restaurant->categories()->whereNull("name->{$main}")->count(),
            'dishes' => $restaurant->dishes()->whereNull("name->{$main}")->count(),
        ];
    }

    /**
     * A field as `{code: text|null}` for the given languages: the shape the
     * dashboard reads and writes.
     *
     * @param  array<int, string>  $languages
     * @return array<string, string|null>
     */
    public static function map(Model $model, string $field, array $languages): array
    {
        $all = $model->getTranslations($field);
        $map = [];

        foreach ($languages as $code) {
            $map[$code] = ($all[$code] ?? null) ?: null;
        }

        return $map;
    }

    /**
     * A translatable field as the request sent it: null when it was not sent
     * (leave it alone), a blank entry per language when it was sent as null
     * (clear it), the map otherwise.
     *
     * @param  array<int, string>  $languages
     * @return array<string, mixed>|null
     */
    public static function input(Request $request, string $field, array $languages): ?array
    {
        if (! $request->exists($field)) {
            return null;
        }

        $value = $request->input($field);

        return is_array($value) ? $value : array_fill_keys($languages, '');
    }

    /**
     * Writes the given languages of a field from `{code: text}` input and leaves
     * every other language in the column alone. A language sent blank is
     * removed; one not sent at all is untouched.
     *
     * @param  array<string, mixed>|null  $input
     * @param  array<int, string>  $languages
     */
    public static function fill(Model $model, string $field, ?array $input, array $languages): void
    {
        foreach ($languages as $code) {
            if (! is_array($input) || ! array_key_exists($code, $input)) {
                continue;
            }

            $text = trim((string) $input[$code]);

            if ($text === '') {
                $model->forgetTranslation($field, $code);
            } else {
                $model->setTranslation($field, $code, $text);
            }
        }
    }
}
