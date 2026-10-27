<?php

namespace App\Filament\Admin\Resources\Restaurants\Tables;

use App\Enums\Feature;
use App\Enums\PackageStatus;
use App\Filament\Admin\Actions\ChangePackageAction;
use App\Filament\Admin\Actions\ExtendPackageAction;
use App\Filament\Admin\Actions\ResetPackageAction;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Restaurant;
use App\Services\Ai\WritingHelpLog;
use App\Services\Ai\WritingHelpPrice;
use App\Services\Analytics\MenuTraffic;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class RestaurantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Everything a row shows, read with the page rather than once
            // per row (a page of ten restaurants was sixty queries). The
            // views and scans add the days added up in `menu_days` to the
            // last raw rows (MenuTraffic), so they neither scan months of
            // visits nor drop when old ones are deleted.
            ->modifyQueryUsing(fn (Builder $query): Builder => MenuTraffic::withTotals($query
                // The grants in force too, for the dish limit (Entitlements).
                ->with(['user', 'package', 'template', 'activeFeatureGrants']))
                ->addSelect([
                    'writing_help_uses' => WritingHelpLog::lastDaysOf('uses'),
                    'writing_help_tokens' => WritingHelpLog::lastDaysOf('input_tokens + output_tokens'),
                    'writing_help_cost' => WritingHelpLog::lastDaysOf('cost'),
                ]))
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logo')
                    ->label('Logo')
                    ->circular()
                    // The name beside it already links to the record; a
                    // second, textless link on the picture helps nobody.
                    ->disabledClick()
                    ->extraImgAttributes(fn (Restaurant $record): array => ['alt' => 'Logo of '.$record->name])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->placeholder('N/A')
                    ->description(fn (Restaurant $record): string => '/'.$record->slug)
                    // The whole JSON, so a name matches in whichever language
                    // the owner wrote it.
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('name', 'like', "%{$search}%"))
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->placeholder('N/A')
                    ->description(fn (Restaurant $record): ?string => $record->user->email ?? $record->user->username)
                    ->searchable(['name', 'email', 'username'])
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

                // What it pays a month: its own price, marked, or the package's.
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->state(fn (Restaurant $record): ?int => $record->monthlyPriceCents())
                    ->formatStateUsing(fn (?int $state, Restaurant $record): string => Number::currency($state / 100, $record->effectivePackage()->currency ?? 'USD'))
                    ->placeholder('-')
                    // Under it: an own price, and paying through Whish.
                    ->description(fn (Restaurant $record): ?string => implode(' · ', array_filter([
                        $record->price_cents !== null ? 'Custom' : null,
                        $record->pays_with_whish ? 'Whish' : null,
                    ])) ?: null)
                    ->toggleable(),

                // A tick for Whish, a quiet dash otherwise: not paying with
                // Whish is not a problem to flag in red.
                IconColumn::make('pays_with_whish')
                    ->label('Whish')
                    ->boolean()
                    ->trueColor('success')
                    ->falseIcon(Heroicon::Minus)
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                // The dashboard's AI buttons: who leans on them, heaviest
                // first when sorted, with their tokens underneath; what that
                // cost is the next column.
                TextColumn::make('writing_help_uses')
                    ->label('AI uses (30 days)')
                    ->getStateUsing(fn (Restaurant $record): int => (int) $record->writing_help_uses)
                    ->formatStateUsing(fn (int $state): string => Number::format($state))
                    ->description(fn (Restaurant $record): ?string => (int) $record->writing_help_tokens === 0
                        ? null
                        : Number::format((int) $record->writing_help_tokens).' tokens')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'primary')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('writing_help_uses', $direction))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('writing_help_cost')
                    ->label('AI cost (30 days)')
                    ->getStateUsing(fn (Restaurant $record): float => (float) $record->getAttribute('writing_help_cost'))
                    ->formatStateUsing(fn (float $state): string => WritingHelpPrice::format($state))
                    ->color(fn (float $state): string => $state === 0.0 ? 'gray' : 'primary')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('writing_help_cost', $direction))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->placeholder('None')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->counts('dishes')
                    ->sortable(),

                TextColumn::make('dish_limit')
                    ->label('Dish Limit')
                    ->getStateUsing(fn (Restaurant $record): string => (string) ($record->limitOf(Feature::DishLimit) ?? '∞'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_views')
                    ->label('Total Views')
                    ->getStateUsing(fn (Restaurant $record): int => (int) $record->total_views)
                    ->badge()
                    ->color('info')
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('qr_scans')
                    ->label('QR Scans')
                    ->getStateUsing(fn (Restaurant $record): int => (int) $record->qr_scans)
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    // Days added up count too: raw visits go after 6 months.
                    ->query(fn (Builder $query) => $query->where(fn (Builder $any) => $any->whereHas('menuSessions')->orWhereHas('menuDays'))),

                TernaryFilter::make('pays_with_whish')
                    ->label('Pays with Whish'),

                Filter::make('custom_price')
                    ->label('Has its own price')
                    ->query(fn (Builder $query) => $query->whereNotNull('price_cents')),

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
                EditAction::make(),
                // The rarer ones behind "more", so a row stays one line.
                ActionGroup::make([
                    Action::make('menu')
                        ->label('Open menu')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (Restaurant $record): string => route('public.menu', $record->slug))
                        ->openUrlInNewTab(),
                    Action::make('impersonate')
                        ->label('Log in as owner')
                        ->icon(Heroicon::OutlinedUserCircle)
                        ->url(fn (Restaurant $record): string => route('impersonate', $record->user_id))
                        ->visible(fn (Restaurant $record): bool => $record->user?->canBeImpersonated() ?? false),
                    DeleteAction::make()
                        ->modalHeading('Delete restaurant and owner')
                        ->modalDescription(RestaurantResource::DELETE_WARNING),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ChangePackageAction::bulk(),
                    DeleteBulkAction::make()
                        ->modalHeading('Delete restaurants and their owners')
                        ->modalDescription(RestaurantResource::DELETE_WARNING),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** "Until 12 Oct 2026", "Forever", "Starts 3 Oct", "Ended 1 Sep, menu offline 4 Sep". */
    public static function packageDates(Restaurant $restaurant): string
    {
        return match ($restaurant->packageStatus()) {
            PackageStatus::Scheduled => 'Starts '.$restaurant->package_started_at->toFormattedDateString(),
            PackageStatus::Expired => 'Ended '.$restaurant->package_ends_at->toFormattedDateString().', menu offline '.$restaurant->offlineAt()->toFormattedDateString(),
            PackageStatus::Active => $restaurant->package_ends_at === null
                ? 'Forever'
                : 'Until '.$restaurant->package_ends_at->toFormattedDateString(),
        };
    }
}
