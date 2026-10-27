<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Models\FeatureGrant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * What this one restaurant gets beyond, or instead of, its package. Added:
 * more room, or an add-on unlocked for a while. Set: the feature is exactly
 * this for the restaurant whatever the package says, a lower or higher
 * limit, unlimited, or an on/off feature turned off or on (Entitlements).
 */
class FeatureGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'featureGrants';

    protected static ?string $title = 'Extras & overrides';

    protected static ?string $modelLabel = 'extra or override';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('feature')
                ->label('Feature')
                ->options(Feature::options())
                ->required()
                ->live(),
            ToggleButtons::make('mode')
                ->label('How')
                ->options(collect(GrantMode::cases())->mapWithKeys(fn (GrantMode $mode): array => [$mode->value => $mode->label()])->all())
                ->default(GrantMode::Add->value)
                ->inline()
                ->required()
                ->live(),
            TextInput::make('value')
                ->label(fn (Get $get): string => self::isSet($get) ? 'Limit' : 'Amount')
                ->numeric()
                ->minValue(fn (Get $get): int => self::isSet($get) ? 0 : 1)
                ->default(1)
                ->required(fn (Get $get): bool => ! self::isUnlimited($get))
                ->visible(fn (Get $get): bool => self::isLimit($get) && ! self::isUnlimited($get))
                ->helperText(fn (Get $get): string => self::isSet($get)
                    ? 'The limit for this restaurant, lower or higher than its package. Rows already over it stay; no new ones until under it.'
                    : 'Added on top of the package limit. Nothing to add to a package that is already unlimited.'),
            Toggle::make('unlimited')
                ->label('Unlimited')
                ->visible(fn (Get $get): bool => self::isLimit($get) && self::isSet($get))
                ->live()
                ->dehydrated(),
            ToggleButtons::make('state')
                ->label('For this restaurant')
                ->options([1 => 'On', 0 => 'Off'])
                ->colors([1 => 'success', 0 => 'danger'])
                ->default(0)
                ->inline()
                ->visible(fn (Get $get): bool => ! self::isLimit($get) && self::isSet($get))
                ->dehydrated()
                ->helperText('Off: the owner is told it is turned off for their restaurant, and nothing is deleted.'),
            Select::make('source')
                ->options([
                    'admin' => 'Granted by admin',
                    'purchase' => 'Purchased',
                ])
                ->default('admin')
                ->required(),
            DateTimePicker::make('ends_at')
                ->label('Until')
                ->helperText('Leave empty to keep it until you remove it.'),
            TextInput::make('note')
                ->label('Note')
                ->maxLength(255)
                ->placeholder('e.g. Ordering paused until the kitchen reopens')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('feature')
            ->columns([
                TextColumn::make('feature')
                    ->label('Feature')
                    ->formatStateUsing(fn (Feature $state): string => $state->label())
                    ->badge(),
                TextColumn::make('value')
                    ->label('What it does')
                    ->state(fn (FeatureGrant $record): string => self::describe($record))
                    ->badge()
                    ->color(fn (FeatureGrant $record): string => $record->mode === GrantMode::Set && ! $record->feature->isLimit() && (int) $record->value === 0 ? 'danger' : 'gray'),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'admin' ? 'gray' : 'success'),
                TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('ends_at')
                    ->label('Until')
                    ->dateTime()
                    ->placeholder('Removed by hand'),
                TextColumn::make('created_at')
                    ->label('Given')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add extra or override')
                    ->modalHeading('Add an extra or an override')
                    ->mutateDataUsing(fn (array $data): array => self::toRecord($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Change this extra or override')
                    ->mutateRecordDataUsing(fn (array $data): array => self::toForm($data))
                    ->mutateDataUsing(fn (array $data): array => self::toRecord($data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * The form's fields as the row stores them: one `value` (null for
     * unlimited, 0 or 1 for a set on/off feature, 1 for an added one).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function toRecord(array $data): array
    {
        $feature = Feature::from((string) $data['feature']);
        $set = ($data['mode'] ?? GrantMode::Add->value) === GrantMode::Set->value;

        $data['value'] = match (true) {
            ! $feature->isLimit() => $set ? (int) ($data['state'] ?? 0) : 1,
            $set && ($data['unlimited'] ?? false) => null,
            default => (int) $data['value'],
        };

        unset($data['unlimited'], $data['state']);

        return $data;
    }

    /**
     * A row as the form edits it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function toForm(array $data): array
    {
        $data['unlimited'] = $data['value'] === null;
        $data['state'] = (int) ($data['value'] ?? 0);

        return $data;
    }

    /** What the grant does, in a few words. */
    public static function describe(FeatureGrant $grant): string
    {
        $limit = $grant->feature->isLimit();

        return match (true) {
            $grant->mode === GrantMode::Add => $limit ? '+'.$grant->value : 'Unlocked',
            $limit => $grant->value === null ? 'Set to unlimited' : 'Set to '.$grant->value,
            default => (int) $grant->value === 0 ? 'Turned off' : 'Turned on',
        };
    }

    private static function isSet(Get $get): bool
    {
        $mode = $get('mode');

        return ($mode instanceof GrantMode ? $mode->value : $mode) === GrantMode::Set->value;
    }

    private static function isLimit(Get $get): bool
    {
        return Feature::tryFrom((string) $get('feature'))?->isLimit() ?? true;
    }

    private static function isUnlimited(Get $get): bool
    {
        return self::isSet($get) && self::isLimit($get) && (bool) $get('unlimited');
    }
}
