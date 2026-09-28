<?php

namespace App\Filament\Admin\Actions;

use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\ContactMessage;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Put a restaurant (or every selected one) on a package, from a date, for
 * a while or forever, from the table, a bulk selection or a package
 * request. All go through PackageAssigner, which leaves a line
 * in each restaurant's package history.
 */
class ChangePackageAction
{
    public static function make(string $name = 'changePackage'): Action
    {
        return Action::make($name)
            ->label('Change package')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->modalHeading(fn (Restaurant $record): string => 'Change the package of '.$record->name)
            ->modalSubmitActionLabel('Save package')
            ->schema(PackageFields::components())
            ->fillForm(fn (Restaurant $record): array => PackageFields::stateFor($record))
            ->action(function (Restaurant $record, array $data): void {
                self::apply($record, $data);

                Notification::make()->title('Package saved')->success()->send();
            });
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('changePackages')
            ->label('Change package')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->modalHeading('Change the package of the selected restaurants')
            ->modalSubmitActionLabel('Save package')
            ->schema(PackageFields::components())
            ->action(function (Collection $records, array $data): void {
                $records->each(fn (Restaurant $restaurant) => self::apply($restaurant, $data));

                Notification::make()->title('Package saved for '.$records->count().' restaurants')->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * On a package request: the sender's restaurant, pre-filled with the
     * package they asked for, starting now.
     */
    public static function forRequest(): Action
    {
        return Action::make('applyPackage')
            ->label('Apply this package')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->visible(fn (ContactMessage $record): bool => $record->isPackageRequest() && $record->user?->restaurant !== null)
            ->modalHeading(fn (ContactMessage $record): string => 'Put '.$record->user->restaurant->name.' on '.$record->package->name)
            ->modalSubmitActionLabel('Save package')
            ->schema(PackageFields::components())
            ->fillForm(fn (ContactMessage $record): array => [
                ...PackageFields::stateFor($record->user->restaurant),
                'package_id' => $record->package_id,
                'package_started_at' => now(),
                'note' => 'Requested on '.$record->created_at->toFormattedDateString(),
            ])
            ->action(function (ContactMessage $record, array $data): void {
                self::apply($record->user->restaurant, $data);

                Notification::make()->title('Package saved')->success()->send();
            });
    }

    /** @param  array<string, mixed>  $data */
    public static function apply(Restaurant $restaurant, array $data): void
    {
        app(PackageAssigner::class)->assign(
            $restaurant,
            Package::query()->findOrFail($data['package_id']),
            CarbonImmutable::parse($data['package_started_at'] ?? now()),
            PackageFields::endsAt($data),
            $data['note'] ?? null,
        );
    }
}
