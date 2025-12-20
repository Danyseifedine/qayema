<?php

namespace App\Filament\Admin\Resources\MenuStatistics\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MenuStatisticForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('menu_id')
                    ->relationship('menu', 'name')
                    ->required(),
                TextInput::make('session_id')
                    ->required(),
                TextInput::make('ip_address'),
                Textarea::make('user_agent')
                    ->columnSpanFull(),
                TextInput::make('referrer'),
                TextInput::make('country'),
                TextInput::make('device_type'),
                TextInput::make('browser'),
                TextInput::make('os'),
                DateTimePicker::make('viewed_at')
                    ->required(),
                DateTimePicker::make('left_at'),
                TextInput::make('time_spent')
                    ->numeric(),
                TextInput::make('page_views')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('interactions'),
            ]);
    }
}
