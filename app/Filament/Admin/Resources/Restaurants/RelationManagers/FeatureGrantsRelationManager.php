<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Extra slots (or unlocked add-ons) for this one restaurant, stacked on top of
 * its package. Use this to give a single restaurant more room, or one add-on
 * for a while, without moving it to another package.
 */
class FeatureGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'featureGrants';

    protected static ?string $title = 'Extra slots & add-ons';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('feature')
                ->label('Feature')
                ->options(Feature::options())
                ->required()
                ->live()
                ->helperText('Which allowance this grant adds to.'),
            TextInput::make('value')
                ->label('Amount')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required()
                // A flag is simply on, so its amount is always 1.
                ->disabled(fn ($get): bool => Feature::tryFrom((string) $get('feature'))?->kind() === FeatureKind::Flag)
                ->dehydrated()
                ->helperText('Added on top of the package limit. Ignored for on/off add-ons, and for a package that is already unlimited.'),
            Select::make('source')
                ->options([
                    'admin' => 'Granted by admin',
                    'purchase' => 'Purchased',
                ])
                ->default('admin')
                ->required(),
            DateTimePicker::make('ends_at')
                ->label('Expires at')
                ->helperText('Leave empty for a grant that never expires.'),
            TextInput::make('note')
                ->label('Note')
                ->maxLength(255)
                ->placeholder('e.g. Trial of ordering for the summer')
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
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state, Model $record): string => $record->feature->kind() === FeatureKind::Flag
                        ? 'Unlocked'
                        : '+'.$state),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'admin' ? 'gray' : 'success'),
                TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('ends_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->label('Granted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Grant slots')
                    ->mutateDataUsing(function (array $data): array {
                        $data['value'] = Feature::tryFrom((string) $data['feature'])?->kind() === FeatureKind::Flag
                            ? 1
                            : (int) $data['value'];

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
