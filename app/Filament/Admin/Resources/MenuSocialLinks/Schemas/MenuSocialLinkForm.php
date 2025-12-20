<?php

namespace App\Filament\Admin\Resources\MenuSocialLinks\Schemas;

use App\Models\Menu;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter / X',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'linkedin' => 'LinkedIn',
                        'pinterest' => 'Pinterest',
                        'snapchat' => 'Snapchat',
                        'whatsapp' => 'WhatsApp',
                        'telegram' => 'Telegram',
                        'website' => 'Website',
                        'other' => 'Other',
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
                TextInput::make('display_order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('Lower numbers appear first'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active links will be displayed'),
            ]);
    }
}
