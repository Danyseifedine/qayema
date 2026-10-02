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
                    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'success')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Free' : number_format($state / 100, 2))
                    // A null price is never formatted: Filament shows the placeholder instead.
                    ->placeholder('Contact us')
                    ->sortable(),
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
                    ->getStateUsing(fn (Package $record): bool => ($record->featureValue($feature) ?? 1) > 0);

                continue;
            }

            $columns[] = TextColumn::make("feature_{$feature->value}")
                ->label($feature->label())
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
