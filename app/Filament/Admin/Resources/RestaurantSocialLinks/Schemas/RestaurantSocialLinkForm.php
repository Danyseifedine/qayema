<?php

namespace App\Filament\Admin\Resources\RestaurantSocialLinks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantSocialLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Social Link')
                    ->description('Link a social media profile to a restaurant.')
                    ->columns(2)
                    ->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('The restaurant this link belongs to.'),
                        Select::make('platform')
                            ->options([
                                'instagram' => 'Instagram',
                                'x' => 'X (Twitter)',
                                'facebook' => 'Facebook',
                                'tiktok' => 'TikTok',
                            ])
                            ->required()
                            ->searchable()
                            ->helperText('Platform determines the icon shown on the menu.'),
                        TextInput::make('url')
                            ->label('Profile URL')
                            ->url()
                            ->placeholder('https://instagram.com/yourpage')
                            ->required()
                            ->maxLength(500)
                            ->helperText('Full URL including https://.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
