<?php

namespace App\Filament\Admin\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Display name for this setting'),
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Unique identifier for this setting (e.g., theme_color, currency)')
                    ->alphaDash()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Optional description explaining what this setting does'),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'String',
                        'boolean' => 'Boolean',
                        'integer' => 'Integer',
                        'float' => 'Float',
                        'json' => 'JSON',
                    ])
                    ->default('string')
                    ->required()
                    ->helperText('Data type for the value when used in menu settings'),
            ]);
    }
}
