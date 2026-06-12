<?php

namespace App\Filament\Admin\Resources\BlockedIps\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlockedIpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('IP Block')
                    ->description('Block an IP address from accessing the application.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ip')
                            ->label('IP Address')
                            ->placeholder('e.g. 203.0.113.42')
                            ->required()
                            ->maxLength(45)
                            ->helperText('The IPv4 or IPv6 address to block.'),
                        DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->seconds(false)
                            ->native(false)
                            ->helperText('Leave empty for a permanent block.'),
                        TextInput::make('reason')
                            ->placeholder('e.g. Sustained abuse')
                            ->maxLength(255)
                            ->helperText('Optional. Why this IP was blocked.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
