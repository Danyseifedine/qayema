<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Services\Ai\WritingHelpCredits;
use App\Services\Ai\WritingHelpPrice;
use App\Services\Packages\RestaurantFeatures;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Number;

/**
 * The restaurant page's Features section: every switch and number the
 * restaurant has now, grouped as an admin thinks of them, each saying what
 * its package gives. Changed and saved, it is this restaurant's own
 * (RestaurantFeatures::save()).
 */
final class FeatureFields
{
    /** Every feature, once, in the groups the section shows. */
    public const GROUPS = [
        'Menu' => [
            Feature::DishLimit, Feature::CategoryLimit, Feature::SocialLinkLimit, Feature::WritingHelpLimit,
            Feature::WritingHelpCredits, Feature::MultipleLanguages, Feature::Variants, Feature::Addons, Feature::BuildYourOwn,
            Feature::BuildLimit,
        ],
        'Look and QR' => [Feature::Appearance, Feature::PremiumDesigns, Feature::QrStudio],
        'Orders' => [Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::DineIn, Feature::DineInWhatsApp],
        'Offers' => [
            Feature::Offers, Feature::OfferBannerLimit, Feature::OfferPopupLimit, Feature::OfferAdLimit,
            Feature::OfferSchedules, Feature::OfferDiscounts,
        ],
        'Numbers' => [Feature::Analytics, Feature::AdvancedAnalytics],
    ];

    public static function section(): Section
    {
        return Section::make('Features')
            ->description('What this restaurant has now. Switch something on or off, or change a number, and save: it becomes this restaurant\'s own, whatever its package. Set it back to what the package gives to follow the package again. Extras that end on a date, or are added on top of the package, are on the Extras & overrides tab.')
            ->visible(fn (string $operation): bool => $operation === 'edit')
            ->columnSpanFull()
            ->schema(array_map(
                fn (string $group, array $features): Fieldset => Fieldset::make($group)
                    ->columns(['md' => 2, 'xl' => 3])
                    ->schema(array_map(self::field(...), $features)),
                array_keys(self::GROUPS),
                self::GROUPS,
            ));
    }

    private static function field(Feature $feature): Toggle|TextInput
    {
        $hint = fn (?Restaurant $record): ?string => $record === null ? null : self::hint($record, $feature);

        if (! $feature->isLimit()) {
            return Toggle::make("features.{$feature->value}")
                ->label($feature->label())
                ->helperText($hint);
        }

        $field = TextInput::make("features.{$feature->value}")
            ->label($feature->label())
            ->numeric()
            ->minValue(0)
            ->maxValue(100000)
            ->placeholder('Unlimited')
            ->helperText($hint);

        if ($feature !== Feature::WritingHelpCredits) {
            return $field;
        }

        // What the credits typed are worth, as they are typed: the admin's
        // side of a number the owner only ever sees as credits.
        return $field
            ->maxValue(100_000_000)
            ->live(onBlur: true)
            ->helperText(fn (?Restaurant $record, mixed $state): ?string => $record === null ? null : self::hint($record, $feature).'. '.self::worth($state));
    }

    /** "Worth $0.75 of AI a month", or no cap. */
    private static function worth(mixed $credits): string
    {
        return is_numeric($credits)
            ? 'Worth '.WritingHelpPrice::format(WritingHelpCredits::dollarsOf((int) $credits)).' of AI a month'
            : 'No cap on its AI a month';
    }

    /** "Package: 40" or, when this restaurant has its own, "Changed here (package: 40)". */
    private static function hint(Restaurant $restaurant, Feature $feature): string
    {
        $package = RestaurantFeatures::reading($feature, RestaurantFeatures::fromPackage($restaurant, $feature));
        $now = RestaurantFeatures::state($restaurant)[$feature->value];
        $said = match (true) {
            ! $feature->isLimit() => $package === 1 ? 'on' : 'off',
            $package === null => 'unlimited',
            default => Number::format($package),
        };

        return $now === $package ? "Package: {$said}" : "Changed for this restaurant (package: {$said})";
    }
}
