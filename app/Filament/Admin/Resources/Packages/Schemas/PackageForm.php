<?php

namespace App\Filament\Admin\Resources\Packages\Schemas;

use App\Enums\Feature;
use App\Models\Package;
use Filament\Forms\Components\CheckboxList;
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
                        ->description('How this plan is presented. The slug is fixed: code and seeds refer to it.')
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
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Set in config/package.php.'),
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
                            Toggle::make('is_active')
                                ->label('Offered')
                                ->default(true)
                                ->disabled(fn (?Package $record): bool => (bool) $record?->is_default)
                                ->helperText(fn (?Package $record): string => $record?->is_default
                                    ? 'The default package is always offered: every restaurant falls back on it.'
                                    : 'Off: gone from the landing page, the dashboard\'s Package page and package requests. Restaurants already on it keep it until it ends.'),
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
                    Section::make('Included features')
                        ->description('Switch on what this package includes.')
                        ->columns(2)
                        ->schema(self::flagFields()),
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make('Limits')
                        ->description('How many of each item a restaurant on this package may create. Grants on a single restaurant stack on top of these.')
                        ->schema([
                            ...self::limitFields(),
                            CheckboxList::make('fair_use')
                                ->label('Shown as unlimited (fair use)')
                                ->options(collect(Feature::limits())->mapWithKeys(fn (Feature $feature): array => [$feature->value => $feature->label()])->all())
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

        foreach (Feature::limits() as $feature) {
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
