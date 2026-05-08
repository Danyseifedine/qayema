<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RestaurantStatistic;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivityWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Recent Menu Visits';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RestaurantStatistic::query()
                    ->with('restaurant')
                    ->latest('viewed_at')
                    ->limit(15)
            )
            ->columns([
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('viewed_at')
                    ->label('Visited At')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('page_views')
                    ->label('Page Views')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('time_spent')
                    ->label('Time Spent')
                    ->formatStateUsing(fn ($state) => $state ? $this->formatTime((int) $state) : '—')
                    ->sortable(),
                TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'mobile' => 'info',
                        'tablet' => 'warning',
                        'desktop' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('browser')
                    ->label('Browser')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('os')
                    ->label('OS')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('viewed_at', 'desc');
    }

    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = (int) floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = (int) floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    }
}
