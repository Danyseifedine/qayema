<?php

namespace App\Filament\Admin\Resources\Templates\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TemplateForm
{
    /**
     * Capability keys stored in templates.capabilities, grouped for display.
     *
     * @var array<string, array<string, string>>
     */
    private const CAPABILITIES = [
        'Restaurant Profile' => [
            'logo' => 'Logo',
            'cover_image' => 'Cover Image',
            'description' => 'Description',
            'phone' => 'Phone Number',
            'address' => 'Address (text)',
            'map' => 'Map Embed',
            'schedule' => 'Opening Hours',
            'social_links' => 'Social Links',
        ],
        'Menu Content' => [
            'dish_images' => 'Dish Images',
            'dish_ingredients' => 'Dish Ingredients',
            'dish_prices' => 'Dish Prices',
            'dish_tags' => 'Dish Tags',
            'category_images' => 'Category Images',
            'category_description' => 'Category Description',
        ],
        'UI / UX' => [
            'search' => 'Search Bar',
            'search_title' => 'Search Title',
            'order_page_title' => 'Order Page Title',
            'final_price_show' => 'Final Price',
            'share_button' => 'Share Button',
            'qr_code' => 'QR Code',
        ],
    ];

    public static function configure(Schema $schema): Schema
    {
        $capabilitySections = [];

        foreach (self::CAPABILITIES as $group => $keys) {
            $toggles = [];

            foreach ($keys as $key => $label) {
                $toggles[] = Toggle::make('capabilities.'.$key)
                    ->label($label)
                    ->default(true);
            }

            $capabilitySections[] = Section::make($group)
                ->columns(3)
                ->schema($toggles);
        }

        return $schema
            ->components([
                Section::make('Template Identity')
                    ->description('Name, slug, tier and ordering.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. Simple, Elegant, Dark')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug((string) ($state ?? ''))))
                            ->helperText('Human-readable name shown to admins and owners.'),
                        TextInput::make('slug')
                            ->placeholder('simple')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-generated from name. Used in code to load the template.'),
                        Select::make('tier')
                            ->options(['free' => 'Free', 'paid' => 'Paid'])
                            ->default('free')
                            ->required()
                            ->live()
                            ->helperText('Paid templates require an active subscription; expired menus go offline.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order in template pickers.'),
                        Textarea::make('description')
                            ->placeholder('Describe what this template looks like and when to use it…')
                            ->rows(3)
                            ->helperText('Optional. Shown in the admin panel only.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Template is active (available to assign to restaurants)')
                            ->default(true),
                    ]),

                Section::make('Pricing')
                    ->description('Subscription prices per billing period. Only used when the tier is Paid; USD is the platform base currency.')
                    ->hidden(fn (Get $get): bool => $get('tier') !== 'paid')
                    ->schema([
                        Repeater::make('prices')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Select::make('period')
                                    ->options([
                                        'monthly' => 'Monthly',
                                        'semiannual' => 'Every 6 months',
                                        'yearly' => 'Yearly',
                                    ])
                                    ->required(),
                                TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),
                                TextInput::make('currency')
                                    ->default('USD')
                                    ->required()
                                    ->maxLength(3),
                                Toggle::make('is_active')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->addActionLabel('+ Add Price'),
                    ]),

                Section::make('Thumbnail')
                    ->description('Preview image shown when selecting a template.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('thumbnail')
                            ->label('Thumbnail Image')
                            ->collection('thumbnail')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended 800×500 px. Max 5 MB.'),
                    ]),

                ...$capabilitySections,

                Section::make('Direction')
                    ->columns(2)
                    ->schema([
                        Select::make('capabilities.direction')
                            ->label('Default Direction')
                            ->options(['rtl' => 'RTL — Right to Left', 'ltr' => 'LTR — Left to Right'])
                            ->default('rtl')
                            ->required()
                            ->helperText('Arabic-first: RTL is the default; LTR is the variant.'),
                        Toggle::make('capabilities.direction_switchable')
                            ->label('Allow Direction Switch')
                            ->default(true)
                            ->helperText('Whether the restaurant owner can flip the direction.'),
                    ]),

                Section::make('Default Settings')
                    ->description('Key → default value map merged under each restaurant\'s own settings (e.g. accent_color → #C8A85A).')
                    ->schema([
                        KeyValue::make('default_settings')
                            ->label('')
                            ->keyLabel('Setting key')
                            ->valueLabel('Default value')
                            ->reorderable(),
                    ]),
            ]);
    }
}
