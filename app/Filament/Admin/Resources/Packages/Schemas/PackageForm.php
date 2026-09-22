<?php

namespace App\Filament\Admin\Resources\Packages\Schemas;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
        return $schema->components([
            Section::make('Package')
                ->description('How this plan is presented. The slug is fixed: code and seeds refer to it.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
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
                        ->helperText('Order on the pricing page and in the dashboard.'),
                    Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Limits')
                ->description('How many of each item a restaurant on this package may create. Grants on a single restaurant stack on top of these.')
                ->columns(2)
                ->schema(self::limitFields()),

            Section::make('Included features')
                ->description('Switch on what this package includes.')
                ->columns(2)
                ->schema(self::flagFields()),
        ]);
    }

    /**
     * @return array<int, TextInput>
     */
    private static function limitFields(): array
    {
        $fields = [];

        foreach (Feature::cases() as $feature) {
            if ($feature->kind() !== FeatureKind::Limit) {
                continue;
            }

            $fields[] = TextInput::make("features.{$feature->value}")
                ->label($feature->label())
                ->numeric()
                ->minValue(0)
                ->maxValue(100000)
                // Empty means unlimited, so this must reach the database as
                // null rather than an empty string or a zero.
                ->dehydrateStateUsing(fn ($state): ?int => $state === null || $state === '' ? null : (int) $state)
                ->helperText('Leave empty for unlimited.');
        }

        return $fields;
    }

    /**
     * @return array<int, Toggle>
     */
    private static function flagFields(): array
    {
        $fields = [];

        foreach (Feature::cases() as $feature) {
            if ($feature->kind() !== FeatureKind::Flag) {
                continue;
            }

            $fields[] = Toggle::make("features.{$feature->value}")
                ->label($feature->label())
                ->dehydrateStateUsing(fn ($state): int => (int) (bool) $state);
        }

        return $fields;
    }
}
