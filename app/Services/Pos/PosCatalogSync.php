<?php

namespace App\Services\Pos;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishAddonGroup;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\Ingredients;
use App\Support\MenuTextLimits;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Makes the menu's categories and dishes what the POS catalog says (the
 * snapshot of GET /api/v1/menu/catalog), writing only what changed, so the
 * menu's cache is cleared only when something did.
 *
 * - Categories and dishes are matched by `pos_id`. On the first sync a row
 *   the owner made with the same name in the main language is taken over
 *   (it keeps its photo, icon and place). Everything else the POS sends is
 *   created, last in its list.
 * - From the POS: names and descriptions in the menu's languages (a
 *   language it leaves empty keeps what the menu had), the ingredients line
 *   (its recipe, else its description), price, currency, category, whether
 *   it can be sold, its photo, and its choices. A required pick-one group
 *   becomes a variant; any other group an add-on group with its limits.
 *   Qayema keeps the order, icons and everything about how the menu looks.
 * - Gone from the POS: hidden, never deleted (orders keep their lines).
 * - What cannot be applied (a third currency, over the package's limits, a
 *   photo that would not download) is listed as a problem and skipped; it
 *   never stops the rest.
 */
class PosCatalogSync
{
    private bool $changed = false;

    /** @var list<array{type: string, message: string}> */
    private array $problems = [];

    public function __construct(private readonly PosImages $images) {}

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{changed: bool, problems: list<array{type: string, message: string}>}
     */
    public function apply(Restaurant $restaurant, array $snapshot): array
    {
        $this->changed = false;
        $this->problems = [];

        if (! is_array($snapshot['categories'] ?? null) || ! is_array($snapshot['products'] ?? null) || ! is_string($snapshot['currency'] ?? null)) {
            throw new PosException(__('Your POS sent a catalog this menu cannot read.'), retry: false);
        }

        if ($snapshot['currency'] !== $restaurant->currency) {
            throw new PosException(__('Your POS prices in :pos but your menu is in :menu. Set the same main currency on both.', ['pos' => $snapshot['currency'], 'menu' => $restaurant->currency]), retry: false);
        }

        $languages = $restaurant->menuLanguages();
        $main = MenuLanguages::main($restaurant);
        $firstSync = ! $restaurant->categories()->whereNotNull('pos_id')->exists() && ! $restaurant->dishes()->whereNotNull('pos_id')->exists();

        $photos = [];

        DB::transaction(function () use ($restaurant, $snapshot, $languages, $main, $firstSync, &$photos): void {
            $categories = $this->categories($restaurant, $snapshot['categories'], $languages, $main, $firstSync);
            $photos = $this->dishes($restaurant, $snapshot['products'], $categories, $languages, $main, $firstSync);
        });

        // Photos are fetched outside the transaction: a slow download never
        // holds the menu's rows.
        foreach ($photos as [$dish, $image, $name]) {
            $this->photo($dish, $image, $name);
        }

        return ['changed' => $this->changed, 'problems' => $this->problems];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @param  array<int, string>  $languages
     * @return array<string, int> POS category id => menu category id
     */
    private function categories(Restaurant $restaurant, array $rows, array $languages, string $main, bool $firstSync): array
    {
        $saved = $restaurant->categories()->get();
        $byPosId = $saved->whereNotNull('pos_id')->keyBy('pos_id');
        $unlinked = $saved->whereNull('pos_id');
        $room = $this->room($restaurant, Feature::CategoryLimit, $byPosId->count());
        $order = (int) $saved->max('display_order');
        $seen = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }

            $posId = (string) $row['id'];
            $seen[$posId] = true;
            $category = $byPosId->get($posId) ?? ($firstSync ? $this->takeByName($unlinked, $row['name'] ?? [], $main) : null);

            if ($category === null) {
                if ($room !== null && $room <= 0) {
                    $this->problem('limit', __('":name" was not added: your package allows :limit categories.', ['name' => $this->posName($row), 'limit' => $restaurant->limitOf(Feature::CategoryLimit)]));

                    continue;
                }

                $room = $room === null ? null : $room - 1;
                $category = new Category(['display_order' => ++$order]);
                $category->restaurant()->associate($restaurant);
            }

            $category->pos_id = $posId;
            $category->pos_removed_at = null;
            $this->fillText($category, 'name', $row['name'] ?? null, $languages, MenuTextLimits::CATEGORY_NAME);
            $this->fillText($category, 'description', $row['description'] ?? null, $languages, MenuTextLimits::CATEGORY_DESCRIPTION);
            $this->save($category);
            $byPosId->put($posId, $category);
        }

        foreach ($byPosId as $posId => $category) {
            if (! isset($seen[$posId]) && $category->pos_removed_at === null) {
                $category->pos_removed_at = now();
                $this->save($category);
            }
        }

        return $byPosId->reject(fn (Category $category): bool => $category->pos_removed_at !== null)->map(fn (Category $category): int => $category->id)->all();
    }

    /**
     * @param  array<int, mixed>  $rows
     * @param  array<string, int>  $categories
     * @param  array<int, string>  $languages
     * @return list<array{0: Dish, 1: array<string, mixed>|null, 2: string}> the photos to bring up to date
     */
    private function dishes(Restaurant $restaurant, array $rows, array $categories, array $languages, string $main, bool $firstSync): array
    {
        $saved = $restaurant->dishes()->whereNull('offer_id')->with(['variants.options', 'addonGroups', 'addons'])->get();
        $byPosId = $saved->whereNotNull('pos_id')->keyBy('pos_id');
        $unlinked = $saved->whereNull('pos_id');
        $room = $this->room($restaurant, Feature::DishLimit, $byPosId->count());
        $orders = [];
        $seen = [];
        $photos = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }

            $posId = (string) $row['id'];
            $name = $this->posName($row);
            $seen[$posId] = true;
            $categoryId = $categories[(string) ($row['category_id'] ?? '')] ?? null;
            $currency = $this->currency($restaurant, (string) ($row['currency'] ?? $restaurant->currency));

            if ($categoryId === null) {
                $this->problem('category', __('":name" is not on the menu: it has no category in your POS.', ['name' => $name]));

                continue;
            }

            if ($currency === false) {
                $this->problem('currency', __('":name" is not on the menu: it is priced in :currency, which your menu does not use.', ['name' => $name, 'currency' => $row['currency']]));

                continue;
            }

            $dish = $byPosId->get($posId) ?? ($firstSync ? $this->takeByName($unlinked, $row['name'] ?? [], $main) : null);

            if ($dish === null) {
                if ($room !== null && $room <= 0) {
                    $this->problem('limit', __('":name" was not added: your package allows :limit dishes.', ['name' => $name, 'limit' => $restaurant->limitOf(Feature::DishLimit)]));

                    continue;
                }

                $room = $room === null ? null : $room - 1;
                $orders[$categoryId] ??= (int) $restaurant->dishes()->where('category_id', $categoryId)->max('display_order');
                $dish = new Dish(['display_order' => ++$orders[$categoryId]]);
                $dish->restaurant()->associate($restaurant);
            }

            $dish->pos_id = $posId;
            $dish->pos_removed_at = null;
            $dish->category_id = $categoryId;
            $dish->currency = $currency;
            $dish->price = $this->money($row['price'] ?? '0');
            $dish->is_available = (bool) ($row['available'] ?? false);
            $this->fillText($dish, 'name', $row['name'] ?? null, $languages, MenuTextLimits::DISH_NAME);
            $this->fillText($dish, 'ingredients', $this->ingredients($row, $languages), $languages, MenuTextLimits::INGREDIENTS);
            $this->save($dish);
            $byPosId->put($posId, $dish);

            $this->choices($restaurant, $dish, is_array($row['modifier_groups'] ?? null) ? $row['modifier_groups'] : [], $languages, $name);

            $image = is_array($row['image'] ?? null) ? $row['image'] : null;

            if (($image['version'] ?? null) !== $dish->pos_image_version) {
                $photos[] = [$dish, $image, $name];
            }
        }

        foreach ($byPosId as $posId => $dish) {
            if (! isset($seen[$posId]) && $dish->pos_removed_at === null) {
                $dish->pos_removed_at = now();
                $dish->is_available = false;
                $this->save($dish);
            }
        }

        return $photos;
    }

    /**
     * A dish's choices as the POS groups them. The POS owns them all: a
     * choice the owner added before the link is replaced.
     *
     * @param  array<int, mixed>  $groups
     * @param  array<int, string>  $languages
     */
    private function choices(Restaurant $restaurant, Dish $dish, array $groups, array $languages, string $dishName): void
    {
        $rate = $dish->currency === null ? null : $restaurant->exchange_rate;
        $variants = $dish->exists && $dish->relationLoaded('variants') ? $dish->variants : new Collection;
        $addonGroups = $dish->exists && $dish->relationLoaded('addonGroups') ? $dish->addonGroups : new Collection;
        $addons = $dish->exists && $dish->relationLoaded('addons') ? $dish->addons : new Collection;
        $keptVariants = [];
        $keptGroups = [];
        $keptAddons = [];

        foreach (array_values($groups) as $index => $group) {
            if (! is_array($group) || ! isset($group['id']) || ! is_array($group['options'] ?? null) || $group['options'] === []) {
                continue;
            }

            $posId = (string) $group['id'];
            $options = array_values(array_filter($group['options'], fn (mixed $option): bool => is_array($option) && isset($option['id'])));
            $min = max(0, (int) ($group['min'] ?? 0));
            $max = isset($group['max']) ? max(1, (int) $group['max']) : null;

            // A guest picks exactly one: a variant. With a single option there
            // is nothing to pick, so it stays a group the guest takes as is.
            if (($group['selection'] ?? null) === 'single' && $min >= 1 && count($options) >= 2) {
                $variant = $variants->firstWhere('pos_id', $posId) ?? new DishVariant(['dish_id' => $dish->id]);
                $variant->pos_id = $posId;
                $variant->display_order = $index + 1;
                $this->fillText($variant, 'name', $group['name'] ?? null, $languages, MenuTextLimits::CHOICE_NAME);
                $this->save($variant);
                $keptVariants[] = $variant->id;
                $this->variantOptions($variant, $options, $languages, $rate, $dishName);

                continue;
            }

            $addonGroup = $addonGroups->firstWhere('pos_id', $posId) ?? new DishAddonGroup(['dish_id' => $dish->id]);
            $addonGroup->pos_id = $posId;
            $addonGroup->min_select = min($min, count($options));
            $addonGroup->max_select = $max;
            $addonGroup->display_order = $index + 1;
            $this->fillText($addonGroup, 'name', $group['name'] ?? null, $languages, MenuTextLimits::CHOICE_NAME);
            $this->save($addonGroup);
            $keptGroups[] = $addonGroup->id;

            foreach ($options as $position => $option) {
                $addon = $addons->first(fn (DishAddon $addon): bool => $addon->pos_id === (string) $option['id'] && $addon->dish_addon_group_id === $addonGroup->id)
                    ?? new DishAddon(['dish_id' => $dish->id]);
                $addon->dish_addon_group_id = $addonGroup->id;
                $addon->pos_id = (string) $option['id'];
                $addon->price = $this->delta($option['price_delta'] ?? '0', $rate, $dishName);
                $addon->display_order = $position + 1;
                $this->fillText($addon, 'name', $option['name'] ?? null, $languages, MenuTextLimits::CHOICE_NAME);
                $this->save($addon);
                $keptAddons[] = $addon->id;
            }
        }

        $this->deleteOthers($variants, $keptVariants);
        $this->deleteOthers($addons, $keptAddons);
        $this->deleteOthers($addonGroups, $keptGroups);
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @param  array<int, string>  $languages
     */
    private function variantOptions(DishVariant $variant, array $options, array $languages, ?string $rate, string $dishName): void
    {
        $saved = $variant->relationLoaded('options') ? $variant->options : new Collection;
        $kept = [];

        foreach ($options as $position => $option) {
            $row = $saved->firstWhere('pos_id', (string) $option['id']) ?? new DishVariantOption(['dish_variant_id' => $variant->id]);
            $row->pos_id = (string) $option['id'];
            $row->price = $this->delta($option['price_delta'] ?? '0', $rate, $dishName);
            $row->display_order = $position + 1;
            $this->fillText($row, 'name', $option['name'] ?? null, $languages, MenuTextLimits::CHOICE_NAME);
            $this->save($row);
            $kept[] = $row->id;
        }

        $this->deleteOthers($saved, $kept);
    }

    /**
     * The ingredients line in each menu language: the recipe's ingredients
     * joined the language's way, else the description as a list.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $languages
     * @return array<string, string|null>
     */
    private function ingredients(array $row, array $languages): array
    {
        $lines = [];

        foreach ($languages as $code) {
            $items = $row['ingredients'][$code] ?? null;
            $lines[$code] = is_array($items) && $items !== []
                ? Ingredients::join(array_map('strval', $items), $code)
                : Ingredients::tidy(is_string($row['description'][$code] ?? null) ? $row['description'][$code] : null, $code);
        }

        return $lines;
    }

    /**
     * Writes the languages the POS gave; a language it left empty keeps what
     * the row had, so text written in Qayema is not lost.
     *
     * @param  mixed  $texts  {code: text|null}
     * @param  array<int, string>  $languages
     */
    private function fillText(Model $row, string $field, mixed $texts, array $languages, int $limit): void
    {
        if (! is_array($texts)) {
            return;
        }

        $given = [];

        foreach ($languages as $code) {
            $text = is_string($texts[$code] ?? null) ? trim($texts[$code]) : '';

            if ($text !== '') {
                $given[$code] = Str::limit($text, $limit, '');
            }
        }

        MenuLanguages::fill($row, $field, $given, $languages);
    }

    /**
     * The row the owner made with this name in the main language, taken out
     * of the list so no other POS row takes it too.
     *
     * @template TModel of Category|Dish
     *
     * @param  \Illuminate\Support\Collection<int, TModel>  $unlinked
     * @return TModel|null
     */
    private function takeByName(\Illuminate\Support\Collection $unlinked, mixed $names, string $main): ?Model
    {
        $wanted = is_array($names) && is_string($names[$main] ?? null) ? mb_strtolower(trim($names[$main])) : '';

        if ($wanted === '') {
            return null;
        }

        $key = $unlinked->search(fn (Model $row): bool => mb_strtolower(trim((string) ($row->getTranslations('name')[$main] ?? ''))) === $wanted);

        if ($key === false) {
            return null;
        }

        $row = $unlinked->get($key);
        $unlinked->forget($key);

        return $row;
    }

    /**
     * The dish's currency as Qayema keeps it: null for the main one, the
     * second one's code, or false for one the menu does not use.
     */
    private function currency(Restaurant $restaurant, string $currency): string|false|null
    {
        return match ($currency) {
            $restaurant->currency => null,
            (string) $restaurant->second_currency => $currency,
            default => false,
        };
    }

    /**
     * An option's price change, in the dish's currency. The POS keeps it in
     * its main currency; a dish priced in the second one takes it at the
     * menu's rate (main = second x rate).
     */
    private function delta(mixed $amount, ?string $rate, string $dishName): string
    {
        $amount = $this->money($amount);

        if ($rate === null) {
            return $amount;
        }

        if (bccomp($rate, '0', 12) <= 0) {
            $this->problem('rate', __('The options of ":name" could not be priced: set an exchange rate for your second currency.', ['name' => $dishName]));

            return $amount;
        }

        return number_format((float) bcdiv($amount, $rate, 6), 2, '.', '');
    }

    private function money(mixed $amount): string
    {
        return number_format(max(0, (float) $amount), 2, '.', '');
    }

    /**
     * How many more rows the package lets the POS add, or null for no limit.
     */
    private function room(Restaurant $restaurant, Feature $limit, int $linked): ?int
    {
        $allowed = $restaurant->limitOf($limit);

        return $allowed === null ? null : $allowed - $linked;
    }

    /**
     * @param  array<string, mixed>|null  $image
     */
    private function photo(Dish $dish, ?array $image, string $name): void
    {
        if ($image === null) {
            // The POS took its photo away: the one it gave goes too. A photo
            // the owner chose (no POS version) stays.
            if ($dish->pos_image_version !== null) {
                $dish->clearMediaCollection('image');
                $dish->pos_image_version = null;
                $this->save($dish);
            }

            return;
        }

        try {
            $this->images->store($dish, (string) ($image['url'] ?? ''), $name);
            $dish->pos_image_version = Str::limit((string) ($image['version'] ?? ''), 64, '');
            $this->save($dish);
            $this->changed = true;
        } catch (PosException $exception) {
            $this->problem('photo', __('The photo of ":name" could not be fetched: :reason', ['name' => $name, 'reason' => $exception->getMessage()]));
        }
    }

    /**
     * @param  Collection<int, covariant Model>  $saved
     * @param  array<int, int>  $kept
     */
    private function deleteOthers(Collection $saved, array $kept): void
    {
        $gone = $saved->reject(fn (Model $row): bool => in_array($row->getKey(), $kept, true));

        foreach ($gone as $row) {
            $row->delete();
            $this->changed = true;
        }
    }

    /** Saves only a row that changed, and notes that something did. */
    private function save(Model $row): void
    {
        if (! $row->exists || $row->isDirty()) {
            $row->save();
            $this->changed = true;
        }
    }

    /** @param  array<string, mixed>  $row */
    private function posName(array $row): string
    {
        $names = is_array($row['name'] ?? null) ? array_filter($row['name'], 'is_string') : [];

        return (string) (reset($names) ?: $row['id']);
    }

    private function problem(string $type, string $message): void
    {
        $this->problems[] = ['type' => $type, 'message' => $message];
    }
}
