<?php

namespace App\Filament\Admin\Schemas\Components;

use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\PerRequest;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Menu text in the admin forms (restaurant, category, dish and its choices):
 * the admin edits the restaurant's main language, the one every name is
 * required in, whichever it is.
 *
 * There is one input per language and only the main one shows. A hidden
 * input is neither validated nor saved, so the other languages stay as they
 * are: KeepsTranslations merges them back on the record, and a repeater row
 * writes only the languages it sends.
 */
final class MenuTextInputs
{
    /**
     * @param  Closure(string, string): Field  $make  the input for one language, given its code and name ("Français")
     * @param  Closure(Get, mixed): ?string  $main  the main language of the restaurant the form is about
     * @return array<int, Field>
     */
    public static function make(Closure $make, Closure $main): array
    {
        return array_map(
            fn (string $code): Field => $make($code, MenuLanguages::nameOf($code))
                ->visible(fn (Get $get, mixed $livewire): bool => ($main($get, $livewire) ?? MenuLanguages::DEFAULT_MAIN) === $code),
            MenuLanguages::choices(),
        );
    }

    /**
     * The main language of the restaurant a category or dish form has chosen,
     * read from the page's state so it also answers inside a repeater row.
     *
     * @return Closure(Get, mixed): ?string
     */
    public static function ofChosenRestaurant(): Closure
    {
        return fn (Get $get, mixed $livewire): ?string => self::mainOf($livewire->data['restaurant_id'] ?? null);
    }

    private static function mainOf(mixed $restaurantId): ?string
    {
        $main = self::columnOf($restaurantId, 'main_locale');

        return $main === null ? null : MenuLanguages::validMain($main);
    }

    /** The chosen restaurant's currency, for a price field's prefix. */
    public static function currencyOf(mixed $restaurantId): ?string
    {
        return self::columnOf($restaurantId, 'currency');
    }

    /** The second currency a dish of the chosen restaurant may be priced in, if it has one. */
    public static function secondCurrencyOf(mixed $restaurantId): ?string
    {
        return self::columnOf($restaurantId, 'second_currency');
    }

    /**
     * Read once per restaurant, column and request: every input of the form
     * asks (eleven per text field), so only the one column, kept on the
     * request (never in the cache, where it would outlive a change).
     */
    private static function columnOf(mixed $restaurantId, string $column): ?string
    {
        if (! is_numeric($restaurantId)) {
            return null;
        }

        return PerRequest::remember("admin-restaurant:{$restaurantId}:{$column}", fn (): ?string => Restaurant::query()->whereKey((int) $restaurantId)->value($column));
    }
}
