<?php

namespace App\Filament\Admin\Resources\RestaurantSocialLinks\Schemas;

use App\Models\RestaurantSocialLink;
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
                            ->options(self::platformOptions())
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

    /**
     * Every platform the menu renders, by the name the menu shows; X is
     * named with its old name too, so an admin finds it either way.
     *
     * @return array<string, string>
     */
    public static function platformOptions(): array
    {
        return array_merge(RestaurantSocialLink::LABELS, ['x' => 'X (Twitter)']);
    }
}
