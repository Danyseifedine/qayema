<?php

namespace App\Services\Menu;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The languages a restaurant's menu is written in: English, always, plus the
 * one second language the owner chose, if any (config('locales.menu')).
 *
 * Translations live in spatie JSON columns. Only the restaurant's *active*
 * languages are ever read or written through the API; text in a language the
 * owner has since switched away from stays in the JSON, hidden, and comes back
 * if they switch back.
 */
class MenuLanguages
{
    public const MAIN = 'en';

    /**
     * @return array<string, array{name: string, english: string, flag: string, rtl: bool, font: string|null}>
     */
    public static function catalogue(): array
    {
        return config('locales.menu', []);
    }

    /**
     * Every language an owner can pick as the second one.
     *
     * @return array<int, string>
     */
    public static function secondChoices(): array
    {
        return array_values(array_diff(array_keys(self::catalogue()), [self::MAIN]));
    }

    /**
     * The languages the menu is shown in: English, then the second language —
     * unless the owner switched "Menu languages" off on the Features page,
     * which makes the menu English-only without forgetting the second one.
     *
     * @return array<int, string>
     */
    public static function for(Restaurant $restaurant): array
    {
        return $restaurant->isSwitchedOff('languages')
            ? [self::MAIN]
            : self::written($restaurant);
    }

    /**
     * English, then the second language the owner chose, whether or not the
     * feature is switched on right now.
     *
     * @return array<int, string>
     */
    public static function written(Restaurant $restaurant): array
    {
        $second = $restaurant->second_locale;

        return $second && in_array($second, self::secondChoices(), true)
            ? [self::MAIN, $second]
            : [self::MAIN];
    }

    /** The language the menu opens in: the owner's choice when it is still one of theirs. */
    public static function default(Restaurant $restaurant): string
    {
        $default = (string) $restaurant->default_locale;

        return in_array($default, self::for($restaurant), true) ? $default : self::MAIN;
    }

    /**
     * The signed-in owner's menu languages, for a dashboard request or
     * resource. English alone when there is no restaurant.
     *
     * @return array<int, string>
     */
    public static function forOwner(?User $user): array
    {
        $restaurant = $user?->restaurant;

        return $restaurant === null ? [self::MAIN] : self::for($restaurant);
    }

    /**
     * Validation for one translatable field: an entry per menu language, each
     * a string up to `$max`. English carries `$englishRule` ('required',
     * 'required_with:name', or 'nullable').
     *
     * @param  array<int, string>  $languages
     * @return array<string, array<int, string>>
     */
    public static function rules(string $field, array $languages, int $max, string $englishRule = 'nullable'): array
    {
        $rules = [];

        foreach ($languages as $code) {
            $rules["{$field}.{$code}"] = [$code === self::MAIN ? $englishRule : 'nullable', 'string', "max:{$max}"];
        }

        return $rules;
    }

    public static function isRtl(string $code): bool
    {
        return (bool) (self::catalogue()[$code]['rtl'] ?? false);
    }

    public static function font(string $code): ?string
    {
        return self::catalogue()[$code]['font'] ?? null;
    }

    /**
     * A translatable field in one language, falling back to English — the one
     * language every name is required in. Never spatie's accessor, which falls
     * back to the app locale and so showed Arabic-only text as blank.
     */
    public static function text(Model $model, string $field, string $locale): string
    {
        return (string) ($model->getTranslation($field, $locale, false)
            ?: $model->getTranslation($field, self::MAIN, false));
    }

    /**
     * A field as `{code: text|null}` for the given languages — the shape the
     * dashboard reads and writes.
     *
     * @param  array<int, string>  $languages
     * @return array<string, string|null>
     */
    public static function map(Model $model, string $field, array $languages): array
    {
        $map = [];

        foreach ($languages as $code) {
            $map[$code] = $model->getTranslation($field, $code, false) ?: null;
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
