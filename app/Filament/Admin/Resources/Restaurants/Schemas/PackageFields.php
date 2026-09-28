<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

/**
 * Which package, from when, for how long, and why: the same fields on the
 * restaurant form and in every Change package modal (table row, bulk,
 * ending-soon widget, a package request), so they always read one way.
 */
class PackageFields
{
    public const FOREVER = 'forever';

    public const MONTHS = 'months';

    public const UNTIL = 'until';

    /** The keys these fields add that are not restaurant columns. */
    public const EXTRA = ['duration', 'months', 'note'];

    /** @return array<int, Component> */
    public static function components(): array
    {
        return [
            Select::make('package_id')
                ->label('Package')
                ->options(fn (): array => self::packageOptions())
                ->required()
                ->default(fn (): ?int => Package::default()?->id),
            DateTimePicker::make('package_started_at')
                ->label('Starts')
                ->default(fn () => now())
                ->required()
                ->helperText('A date still to come schedules it: the default package applies until then.'),
            ToggleButtons::make('duration')
                ->label('For how long')
                ->options([
                    self::FOREVER => 'Forever',
                    self::MONTHS => 'A number of months',
                    self::UNTIL => 'Until a date',
                ])
                ->default(self::FOREVER)
                ->inline()
                ->required()
                ->live(),
            Select::make('months')
                ->label('Months')
                ->options(self::monthOptions())
                ->default(1)
                ->visible(fn (Get $get): bool => $get('duration') === self::MONTHS)
                ->required(fn (Get $get): bool => $get('duration') === self::MONTHS)
                ->helperText('Counted from the start date.'),
            DateTimePicker::make('package_ends_at')
                ->label('Ends')
                ->visible(fn (Get $get): bool => $get('duration') === self::UNTIL)
                ->required(fn (Get $get): bool => $get('duration') === self::UNTIL)
                ->after('package_started_at')
                ->helperText('After this the restaurant is back on the default package.'),
            TextInput::make('note')
                ->label('Note')
                ->maxLength(255)
                ->placeholder('e.g. Paid 3 months by bank transfer')
                ->helperText('Kept in the package history.'),
        ];
    }

    /**
     * The end the fields describe: null for forever.
     *
     * @param  array<string, mixed>  $data
     */
    public static function endsAt(array $data): ?CarbonImmutable
    {
        $start = CarbonImmutable::parse($data['package_started_at'] ?? now());

        return match ($data['duration'] ?? self::FOREVER) {
            self::MONTHS => $start->addMonthsNoOverflow((int) ($data['months'] ?? 1)),
            self::UNTIL => CarbonImmutable::parse($data['package_ends_at']),
            default => null,
        };
    }

    /**
     * The fields' state for a restaurant's current package, so a form opens
     * on what is there.
     *
     * @return array<string, mixed>
     */
    public static function stateFor(Restaurant $restaurant): array
    {
        return [
            'package_id' => $restaurant->package_id,
            'package_started_at' => $restaurant->package_started_at ?? now(),
            'duration' => $restaurant->package_ends_at === null ? self::FOREVER : self::UNTIL,
            'months' => 1,
            'package_ends_at' => $restaurant->package_ends_at,
            'note' => null,
        ];
    }

    /** @return array<int, string> 1, 3, 6 and 12 months. */
    public static function monthOptions(): array
    {
        return collect(PackageAssigner::DURATIONS)
            ->mapWithKeys(fn (int $months): array => [$months => $months.' '.Str::plural('month', $months)])
            ->all();
    }

    /** @return array<int, string> */
    public static function packageOptions(): array
    {
        return Package::query()->orderBy('sort_order')->orderBy('id')->get()
            ->mapWithKeys(fn (Package $package): array => [$package->id => (string) $package->name])
            ->all();
    }
}
