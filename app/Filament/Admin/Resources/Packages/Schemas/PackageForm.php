<?php

namespace App\Filament\Admin\Resources\Packages\Schemas;

use App\Enums\Feature;
use App\Enums\PackageTier;
use App\Models\Package;
use App\Services\Ai\WritingHelpCredits;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * What a package contains. The limits and flags are rendered from
 * App\Enums\Feature, so adding a feature adds a field here with no edit.
 *
 * Saving flushes every restaurant's resolved entitlements through the model's
 * own hook, so a change lands immediately for everyone on the package.
 */
class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(['lg' => 3])
            ->components([
                // A wide column for what defines it, a narrow one for its settings.
                Group::make([
                    Section::make('Package')
                        ->description('How this plan is presented. The slug is chosen when the package is made and fixed after: package requests and seeds refer to it.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name.en')
                                ->label('Name (English)')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name.ar')
                                ->label('Name (Arabic)')
                                ->maxLength(255)
                                ->helperText('Owners reading the dashboard in Arabic see this; English otherwise.'),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(32)
                                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                ->unique(ignoreRecord: true)
                                ->disabledOn('edit')
                                ->dehydrated(fn (string $operation): bool => $operation === 'create')
                                ->helperText(fn (string $operation): string => $operation === 'create'
                                    ? 'Lowercase letters, numbers and dashes, e.g. "starter". Fixed once saved.'
                                    : 'Fixed: package requests and seeds refer to it.'),
                            TextInput::make('price_cents')
                                ->label('Price (cents)')
                                ->numeric()
                                ->minValue(0)
                                ->helperText('0 is free. Leave empty to show "Contact us" instead of a price.'),
                            TextInput::make('currency')
                                ->maxLength(3)
                                ->default('USD'),
                            Toggle::make('is_contact_only')
                                ->label('Owners must contact us to get this')
                                ->helperText('Shows a "Request this package" button instead of a price.'),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Order in the dashboard. The lowest package that includes a feature is the one a locked feature points owners to.'),
                            Select::make('tier')
                                ->label('Crown')
                                ->native(false)
                                ->options(PackageTier::options())
                                ->default(PackageTier::Bronze->value)
                                ->selectablePlaceholder(false)
                                ->required()
                                ->helperText('The metal of its crown in the owner\'s dashboard.'),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->disabled(fn (?Package $record): bool => (bool) $record?->is_default)
                                ->helperText(fn (?Package $record): string => $record?->is_default
                                    ? 'The default package is always active: every restaurant falls back on it. To make this one inactive, make another package the default first.'
                                    : 'Inactive: gone from the landing page, the dashboard\'s Package page and package requests. Restaurants already on it keep it until it ends.'),
                            Toggle::make('is_default')
                                ->label('Default for new restaurants')
                                ->disabled(fn (?Package $record): bool => (bool) $record?->is_default)
                                ->helperText(fn (?Package $record): string => $record?->is_default
                                    ? 'Every new restaurant starts on it, and one whose package has not started yet uses it. Make another package the default to change it.'
                                    : 'On: new restaurants start on this one instead, and it is always active. Restaurants already on the old default keep it.'),
                            Toggle::make('is_featured')
                                ->label('Mark as "Most popular"')
                                ->helperText('Highlighted on the dashboard\'s Package page and the landing page. One package at a time: marking this one unmarks the others.'),
                            Textarea::make('description.en')
                                ->label('Description (English)')
                                ->rows(2)
                                ->columnSpanFull(),
                            Textarea::make('description.ar')
                                ->label('Description (Arabic)')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                    Section::make('Card lines')
                        ->description('What the package card lists, on the landing page and the dashboard. Leave empty to list what the package adds to the one before it, worked out from its features.')
                        ->columns(2)
                        ->schema([
                            TagsInput::make('highlights.en')
                                ->label('Lines (English)')
                                ->placeholder('Add a line and press Enter')
                                ->reorderable(),
                            TagsInput::make('highlights.ar')
                                ->label('Lines (Arabic)')
                                ->placeholder('أضف سطراً واضغط Enter')
                                ->reorderable()
                                ->helperText('Empty: Arabic readers see the English lines.'),
                        ]),
                    Section::make('Included features')
                        ->description('Switch on what this package includes.')
                        ->columns(2)
                        ->schema(self::flagFields()),
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make('Limits')
                        ->description('How many of each item a restaurant on this package may create, and its AI writing help a day and a month. Grants on a single restaurant stack on top of these.')
                        ->schema([
                            ...self::limitFields(),
                            self::aiBudgetField(),
                            CheckboxList::make('fair_use')
                                ->label('Shown as unlimited (fair use)')
                                ->options(collect(self::countedLimits())->mapWithKeys(fn (Feature $feature): array => [$feature->value => $feature->label()])->all())
                                ->helperText('Owners see "Unlimited" for a ticked limit, while the number above still holds. The pricing page states the number under the packages, and the Terms explain fair use. Has no effect on a limit left empty, which is truly unlimited.'),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /**
     * @return array<int, TextInput>
     */
    private static function limitFields(): array
    {
        $fields = [];

        foreach (self::countedLimits() as $feature) {
            $fields[] = TextInput::make("features.{$feature->value}")
                ->label($feature->label())
                ->numeric()
                ->minValue(0)
                ->maxValue(100000)
                // Empty means unlimited, so this must reach the database as
                // null rather than an empty string or a zero.
                ->dehydrateStateUsing(fn ($state): ?int => $state === null || $state === '' ? null : (int) $state)
                ->helperText($feature->hint().' Leave empty for unlimited.');
        }

        return $fields;
    }

    /**
     * A package's stored features as the form holds them: every feature,
     * a key it does not carry yet on its default (never empty, which a
     * limit field would save as unlimited), flags as on/off. Its credits
     * come from the AI budget, a field of its own.
     *
     * @param  array<string, int|null>  $stored
     * @return array<string, int|bool|null>
     */
    public static function formFeatures(array $stored): array
    {
        $features = [];

        foreach (Feature::cases() as $feature) {
            if ($feature === Feature::WritingHelpCredits) {
                continue;
            }

            $value = array_key_exists($feature->value, $stored) ? $stored[$feature->value] : $feature->defaultValue();
            $features[$feature->value] = $feature->isLimit() ? $value : (bool) $value;
        }

        return $features;
    }

    /**
     * Every limit set as a number here: the AI credits come from the AI
     * budget instead (aiBudgetField()).
     *
     * @return array<int, Feature>
     */
    private static function countedLimits(): array
    {
        return array_values(array_filter(Feature::limits(), fn (Feature $feature): bool => $feature !== Feature::WritingHelpCredits));
    }

    /**
     * The package's AI a month, typed in dollars and kept in cents, with the
     * credits it gives shown under it as it is typed: owners only ever see
     * the credits.
     */
    private static function aiBudgetField(): TextInput
    {
        return TextInput::make('ai_budget_cents')
            ->label('AI budget a month')
            ->prefix('$')
            ->numeric()
            ->minValue(0)
            // What the column holds, ai_budget_cents decimal(10,4).
            ->maxValue(9999)
            ->step(0.01)
            ->live(onBlur: true)
            ->formatStateUsing(fn (?float $state): ?float => $state === null ? null : round($state / 100, 4))
            // Empty means no cap, so it must reach the database as null.
            ->dehydrateStateUsing(fn (mixed $state): ?float => is_numeric($state) ? round((float) $state * 100, 4) : null)
            ->helperText(fn (mixed $state): string => self::creditsLine($state));
    }

    /** "= 7,500 AI credits a month (a credit is worth 0.01¢)", or what empty and 0 mean. */
    private static function creditsLine(mixed $dollars): string
    {
        $worth = 'a credit is worth '.rtrim(rtrim(number_format(WritingHelpCredits::creditCents(), 4), '0'), '.').'¢, set in Settings, AI credits';

        if (! is_numeric($dollars)) {
            return "No cap: unlimited AI credits ({$worth}).";
        }

        $credits = (int) WritingHelpCredits::forBudget((float) $dollars * 100);

        return $credits === 0
            ? 'No AI credits: the AI buttons are off on this package.'
            : '= '.number_format($credits)." AI credits a month ({$worth}). Owners see the credits, never the dollars.";
    }

    /**
     * @return array<int, Toggle>
     */
    private static function flagFields(): array
    {
        $fields = [];

        foreach (Feature::flags() as $feature) {
            $fields[] = Toggle::make("features.{$feature->value}")
                ->label($feature->label())
                ->helperText($feature->hint())
                ->dehydrateStateUsing(fn ($state): int => (int) (bool) $state);
        }

        return $fields;
    }
}
