<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Restaurant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PopularRestaurantsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Top Restaurants by Views';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Restaurant::query()
                    ->withCount('statistics')
                    ->withSum('statistics', 'page_views')
                    ->orderByDesc('statistics_sum_page_views')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Restaurant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('statistics_sum_page_views')
                    ->label('Page Views')
                    ->numeric()
                    ->sortable()
                    ->default(0),
                TextColumn::make('statistics_count')
                    ->label('Sessions')
                    ->numeric()
                    ->sortable()
                    ->default(0),
                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->counts('dishes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->defaultSort('statistics_sum_page_views', 'desc');
    }
}
