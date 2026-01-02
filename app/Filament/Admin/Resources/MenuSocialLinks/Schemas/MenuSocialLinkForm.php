<?php

namespace App\Filament\Admin\Resources\MenuSocialLinks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MenuSocialLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('platform')
                    ->options([
                        'instagram' => 'Instagram',
                        'x' => 'X (Twitter)',
                        'facebook' => 'Facebook',
                        'tiktok' => 'TikTok',
                    ])
                    ->required()
                    ->searchable(),
                TextInput::make('url')
                    ->label('URL')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->helperText('Full URL to the social media profile or page')
                    ->columnSpanFull(),
            ]);
    }
}
