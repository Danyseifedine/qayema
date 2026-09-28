<?php

namespace App\Filament\Admin\Resources\Restaurants\Tables;

use App\Enums\PackageStatus;
use App\Filament\Admin\Actions\ChangePackageAction;
use App\Filament\Admin\Actions\ExtendPackageAction;
use App\Filament\Admin\Actions\ResetPackageAction;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Restaurant;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logo')
                    ->label('Logo')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('name')
                    ->placeholder('N/A')
                    // The whole JSON, so a name matches in whichever language
                    // the owner wrote it.
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('name', 'like', "%{$search}%"))
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('package.name')
                    ->label('Package')
                    ->placeholder('Default')
                    ->badge()
                    ->color(fn (Restaurant $record): string => match ($record->packageStatus()) {
                        PackageStatus::Expired => 'danger',
                        PackageStatus::Scheduled => 'info',
                        PackageStatus::Active => $record->package_ends_at !== null && $record->package_ends_at->lte(now()->addDays(7)) ? 'warning' : 'success',
                    })
                    ->description(fn (Restaurant $record): string => self::packageDates($record))
                    ->toggleable(),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->placeholder('None')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->counts('dishes')
                    ->sortable(),

                TextColumn::make('dish_limit')
                    ->label('Dish Limit')
                    ->getStateUsing(fn (Restaurant $record): string => $record->dish_limit === null ? '∞' : (string) $record->dish_limit)
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->placeholder('N/A')
                    ->toggleable(),

                TextColumn::make('total_views')
                    ->label('Total Views')
                    ->getStateUsing(fn (Restaurant $record): int => $record->menuSessions()->count())
                    ->badge()
                    ->color('info')
                    ->sortable(false),

                TextColumn::make('unique_visitors')
                    ->label('Unique Visitors')
                    ->getStateUsing(fn (Restaurant $record): int => $record->menuSessions()->distinct('session_id')->count('session_id'))
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('qr_scans')
                    ->label('QR Scans')
                    ->getStateUsing(fn (Restaurant $record): int => $record->menuSessions()->where('via_qr', true)->count())
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Active', 0 => 'Inactive']),

                SelectFilter::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_today')
                    ->label('Created today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),

                Filter::make('created_this_week')
                    ->label('Created this week')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfWeek())),

                Filter::make('has_views')
                    ->label('Has visitor traffic')
                    ->query(fn (Builder $query) => $query->whereHas('menuSessions')),

                SelectFilter::make('package')
                    ->label('Package in force')
                    ->options(fn (): array => PackageFields::packageOptions())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->onPackage((int) $data['value'])
                        : $query),

                SelectFilter::make('package_status')
                    ->label('Package dates')
                    ->options([
                        'active' => 'In force',
                        'forever' => 'Forever',
                        'ending_7' => 'Ends within 7 days',
                        'ending_30' => 'Ends within 30 days',
                        'scheduled' => 'Starts later',
                        'expired' => 'Ended',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'active' => $query->packageActive(),
                        'forever' => $query->packageActive()->whereNull('package_ends_at'),
                        'ending_7' => $query->packageEndingWithin(7),
                        'ending_30' => $query->packageEndingWithin(30),
                        'scheduled' => $query->packageScheduled(),
                        'expired' => $query->packageExpired(),
                        default => $query,
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ChangePackageAction::make(),
                    ExtendPackageAction::make(),
                    ResetPackageAction::make(),
                ])
                    ->label('Package')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->button()
                    ->color('gray'),
                ViewAction::make(),
                EditAction::make(),
                Action::make('impersonate')
                    ->label('Log in as owner')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (Restaurant $record): string => route('impersonate', $record->user_id))
                    ->visible(fn (Restaurant $record): bool => $record->user?->canBeImpersonated() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ChangePackageAction::bulk(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** "Until 12 Oct 2026", "Forever", "Starts 3 Oct", "Ended 1 Sep — on Free". */
    public static function packageDates(Restaurant $restaurant): string
    {
        return match ($restaurant->packageStatus()) {
            PackageStatus::Scheduled => 'Starts '.$restaurant->package_started_at->toFormattedDateString(),
            PackageStatus::Expired => 'Ended '.$restaurant->package_ends_at->toFormattedDateString().' — on '.$restaurant->effectivePackage()?->name,
            PackageStatus::Active => $restaurant->package_ends_at === null
                ? 'Forever'
                : 'Until '.$restaurant->package_ends_at->toFormattedDateString(),
        };
    }
}
