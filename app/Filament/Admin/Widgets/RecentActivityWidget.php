<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MenuStatistic;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivityWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Recent Menu Visits';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MenuStatistic::query()
                    ->with('menu')
                    ->latest('viewed_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('menu.name')
                    ->label('Menu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('viewed_at')
                    ->label('Visited At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('page_views')
                    ->label('Page Views')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('time_spent')
                    ->label('Time Spent')
                    ->formatStateUsing(fn ($state) => $state ? $this->formatTime($state) : 'N/A')
                    ->sortable(),
                TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->sortable(),
                TextColumn::make('browser')
                    ->label('Browser')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('viewed_at', 'desc');
    }

    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    }
}
