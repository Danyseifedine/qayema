<?php

namespace App\Filament\Admin\Resources\Restaurants;

use App\Filament\Admin\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\CategoriesRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\DishesRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\FeatureGrantsRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\OffersRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\PackageChangesRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\SocialLinksRelationManager;
use App\Filament\Admin\Resources\Restaurants\Schemas\RestaurantForm;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Models\Restaurant;
use App\Support\PerRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Restaurants';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return RestaurantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantsTable::configure($table);
    }

    /** What deleting a restaurant takes with it, for every delete button. */
    public const DELETE_WARNING = 'This deletes the menu and the owner\'s account for good: every category, dish and photo, the logo and cover, social links, orders, statistics and package history, and the account they sign in with. This cannot be undone.';

    /** The restaurant's page: its details, then a tab for each of these. */
    public static function getRelations(): array
    {
        return [
            FeatureGrantsRelationManager::class,
            PackageChangesRelationManager::class,
            OrdersRelationManager::class,
            DishesRelationManager::class,
            CategoriesRelationManager::class,
            SocialLinksRelationManager::class,
            OffersRelationManager::class,
        ];
    }

    /** Packages to renew: ending within a week, or ended with the menu still in its grace days. */
    public static function getNavigationBadge(): ?string
    {
        $due = self::toRenewCount();

        return $due === 0 ? null : (string) $due;
    }

    /** Packages to renew: the menu's badge and the To renew tab's, counted once a request. */
    public static function toRenewCount(): int
    {
        return PerRequest::remember('restaurants-to-renew', fn (): int => self::toRenew(Restaurant::query())->count());
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Packages to renew';
    }

    /**
     * Restaurants whose package needs the admin soon: ending in the next 7
     * days, or ended and still online in its grace days.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public static function toRenew(Builder $query): Builder
    {
        return $query->where(fn (Builder $due): Builder => $due
            ->packageEndingWithin(7)
            ->orWhere(fn (Builder $ended): Builder => $ended->packageExpired()->menuOnline()));
    }

    /** Found from the top bar by its name or its link, with its owner and package beside it. */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user', 'package']);
    }

    /** @param  Restaurant  $record */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'Link' => $record->slug,
            'Owner' => $record->user->email ?? $record->user->username,
            'Package' => $record->package?->name,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurants::route('/'),
            'create' => CreateRestaurant::route('/create'),
            'edit' => EditRestaurant::route('/{record}/edit'),
        ];
    }
}
