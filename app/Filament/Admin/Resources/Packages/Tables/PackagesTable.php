<?php

namespace App\Filament\Admin\Resources\Packages\Tables;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Models\Package;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class PackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->badge()
                    ->color(fn (Package $record): string => $record->price_cents > 0 ? 'success' : 'gray')
                    // No price is "Contact us", a badge like the others rather
                    // than the faint placeholder text.
                    ->state(fn (Package $record): string => match (true) {
                        $record->price_cents === null => 'Contact us',
                        $record->price_cents === 0 => 'Free',
                        default => number_format($record->price_cents / 100, 2),
                    })
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled(fn (Package $record): bool => $record->is_default)
                    ->tooltip(fn (Package $record): ?string => $record->is_default ? 'The default package is always active. Make another package the default to make this one inactive.' : null),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->tooltip(fn (Package $record): ?string => $record->is_default ? 'New restaurants start on it.' : null),
                TextColumn::make('restaurants_count')
                    ->label('Restaurants')
                    ->counts('restaurants')
                    ->sortable(),
                ...self::featureColumns(),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Package $record): bool => PackageResource::canDelete($record))
                    ->modalDescription(fn (Package $record): string => PackageResource::deleteWarning($record)),
            ])
            // A column per feature makes the table far wider than the screen:
            // the actions go first, where they are always in view.
            ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
            ->defaultSort('sort_order');
    }

    /**
     * One column per feature, read straight off the JSON map so the table shows
     * what the packages differ by without a query per row.
     *
     * @return array<int, IconColumn|TextColumn>
     */
    private static function featureColumns(): array
    {
        $columns = [];

        foreach (Feature::cases() as $feature) {
            if ($feature->kind() === FeatureKind::Flag) {
                $columns[] = IconColumn::make("feature_{$feature->value}")
                    ->label($feature->label())
                    ->boolean()
                    ->getStateUsing(fn (Package $record): bool => ($record->featureValue($feature) ?? 1) > 0)
                    ->toggleable();

                continue;
            }

            $columns[] = TextColumn::make("feature_{$feature->value}")
                ->label($feature->label())
                ->toggleable()
                // The admin sees the number that holds, and when owners are
                // shown "Unlimited" instead.
                ->getStateUsing(fn (Package $record): string => match (true) {
                    $record->featureValue($feature) === null => '∞',
                    $record->isFairUse($feature) => $record->featureValue($feature).' (fair use)',
                    default => (string) $record->featureValue($feature),
                });
        }

        return $columns;
    }
}
