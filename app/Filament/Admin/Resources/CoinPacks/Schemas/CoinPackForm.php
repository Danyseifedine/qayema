<?php

namespace App\Filament\Admin\Resources\CoinPacks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CoinPackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pack')
                ->description('What the customer gets. The cash price lives in Paddle and is edited on the Coin Pack Prices page.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->placeholder('e.g. Starter')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) ($state ?? '')))),
                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('coins')
                        ->label('Coins')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->suffix('coins')
                        ->helperText('Credited when Paddle confirms the payment.'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Pack is on sale')
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            Section::make('Paddle prices')
                ->description('The Paddle price id per environment. A pack with no id for the active environment is hidden from checkout.')
                ->columns(2)
                ->schema([
                    TextInput::make('paddle_price_id_sandbox')
                        ->label('Sandbox price id')
                        ->placeholder('pri_…')
                        ->maxLength(255),
                    TextInput::make('paddle_price_id_production')
                        ->label('Live price id')
                        ->placeholder('pri_…')
                        ->maxLength(255),
                ]),
        ]);
    }
}
