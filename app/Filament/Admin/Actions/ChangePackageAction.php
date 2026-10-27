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
                // The package read once for every restaurant selected.
                $package = Package::query()->findOrFail($data['package_id']);
                $records->each(fn (Restaurant $restaurant) => self::apply($restaurant, $data, $package));

                Notification::make()->title('Package saved for '.$records->count().' restaurants')->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * On a package request: the sender's restaurant, pre-filled with the
     * package they asked for, starting now. Applying it marks the request
     * handled, so it leaves the open inbox.
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
            ->fillForm(fn (ContactMessage $record): array => self::requestState($record))
            ->action(function (ContactMessage $record, array $data): void {
                self::apply($record->user->restaurant, $data);
                $record->markHandled();

                Notification::make()->title('Package saved')->success()->send();
            });
    }

    /**
     * The form as a request opens it: the requested package from now. An end
     * still to come is kept (moving up mid-period); one already passed, a
     * renewal, opens on a month instead, so Save works as it opens.
     *
     * @return array<string, mixed>
     */
    public static function requestState(ContactMessage $record): array
    {
        $restaurant = $record->user->restaurant;
        $ended = $restaurant->package_ends_at?->isPast() ?? false;

        return [
            ...PackageFields::stateFor($restaurant),
            ...($ended ? ['duration' => PackageFields::MONTHS, 'months' => 1, 'package_ends_at' => null] : []),
            'package_id' => $record->package_id,
            'package_started_at' => now(),
            'note' => 'Requested on '.$record->created_at->toFormattedDateString(),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function apply(Restaurant $restaurant, array $data, ?Package $package = null): void
    {
        app(PackageAssigner::class)->assign(
            $restaurant,
            $package ?? Package::query()->findOrFail($data['package_id']),
            CarbonImmutable::parse($data['package_started_at'] ?? now()),
            PackageFields::endsAt($data),
            $data['note'] ?? null,
        );
    }
}
