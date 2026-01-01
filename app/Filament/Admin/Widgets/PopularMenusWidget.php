<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Menu;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PopularMenusWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Most Popular Menus';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Menu::query()
                    ->withCount('statistics')
                    ->withSum('statistics', 'page_views')
                    ->orderBy('statistics_sum_page_views', 'desc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Menu Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('statistics_sum_page_views')
                    ->label('Total Views')
                    ->numeric()
                    ->sortable()
                    ->default(0),
                TextColumn::make('statistics_count')
                    ->label('Visits')
                    ->numeric()
                    ->sortable()
                    ->default(0),
                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->counts('dishes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->defaultSort('statistics_sum_page_views', 'desc');
    }
}
