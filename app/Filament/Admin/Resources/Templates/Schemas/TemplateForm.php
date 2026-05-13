<?php

namespace App\Filament\Admin\Resources\Templates\Schemas;

use Filament\Forms\Components\ColorPicker;
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
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Identity')
                    ->description('Name and slug used to identify this template in the system.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. Simple, Elegant, Dark')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state)))
                            ->helperText('Human-readable name shown to admins.'),
                        TextInput::make('slug')
                            ->placeholder('simple')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-generated from name. Used in code to load the template.'),
                        Textarea::make('description')
                            ->placeholder('Describe what this template looks like and when to use it…')
                            ->rows(3)
                            ->helperText('Optional. Shown in the admin panel only.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Template is active (available to assign to restaurants)')
                            ->default(true),
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

                Section::make('Capabilities')
                    ->description('Define what this template supports. Disabling a capability hides that feature entirely for any restaurant using this template.')
                    ->schema([

                        // ── Restaurant Profile ────────────────────────────────────────────────
                        Section::make('Restaurant Profile')
                            ->columns(3)
                            ->schema([
                                Toggle::make('has_logo')
                                    ->label('Logo')
                                    ->default(true)
                                    ->helperText('Restaurant logo is displayed.'),

                                Toggle::make('has_cover_image')
                                    ->label('Cover Image')
                                    ->default(true)
                                    ->helperText('Hero/banner image at the top.'),

                                Toggle::make('has_description')
                                    ->label('Description')
                                    ->default(true)
                                    ->helperText('About / description section.'),

                                Toggle::make('has_phone')
                                    ->label('Phone Number')
                                    ->default(true)
                                    ->helperText('Phone number is displayed.'),

                                Toggle::make('has_address')
                                    ->label('Address (text)')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Location shown as a text string.'),

                                Toggle::make('has_map')
                                    ->label('Map Embed')
                                    ->default(true)
                                    ->hidden(fn (Get $get): bool => ! $get('has_address'))
                                    ->helperText('Embedded map (requires Address enabled).'),

                                Toggle::make('has_schedule')
                                    ->label('Opening Hours')
                                    ->default(true)
                                    ->helperText('Schedule / opening hours section.'),

                                Toggle::make('has_social_links')
                                    ->label('Social Links')
                                    ->default(true)
                                    ->helperText('Instagram, Facebook, TikTok etc.'),
                            ]),

                        // ── Menu Content ──────────────────────────────────────────────────────
                        Section::make('Menu Content')
                            ->columns(3)
                            ->schema([
                                Toggle::make('has_dish_images')
                                    ->label('Dish Images')
                                    ->default(true)
                                    ->helperText('Photo shown on each dish card.'),

                                Toggle::make('has_dish_ingredients')
                                    ->label('Dish Ingredients')
                                    ->default(true)
                                    ->helperText('Ingredient list shown on dishes.'),

                                Toggle::make('has_dish_prices')
                                    ->label('Dish Prices')
                                    ->default(true)
                                    ->helperText('Price is shown on dish cards.'),

                                Toggle::make('has_dish_tags')
                                    ->label('Dish Tags')
                                    ->default(true)
                                    ->helperText('Dietary / cuisine tags on dishes.'),

                                Toggle::make('has_category_images')
                                    ->label('Category Images')
                                    ->default(true)
                                    ->helperText('Image shown on category headers.'),

                                Toggle::make('has_category_description')
                                    ->label('Category Description')
                                    ->default(true)
                                    ->helperText('Description text under category name.'),
                            ]),

                        // ── UI / UX ───────────────────────────────────────────────────────────
                        Section::make('UI / UX')
                            ->columns(3)
                            ->schema([
                                Toggle::make('has_search')
                                    ->label('Search Bar')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Dish search bar visible to customers.'),

                                Toggle::make('has_search_title')
                                    ->label('Search Title')
                                    ->default(true)
                                    ->hidden(fn (Get $get): bool => ! $get('has_search'))
                                    ->helperText('Heading above the search bar (requires Search enabled).'),

                                Toggle::make('has_order_page_title')
                                    ->label('Order Page Title')
                                    ->default(true)
                                    ->helperText('Title heading on the order/cart page.'),

                                Toggle::make('has_final_price_show')
                                    ->label('Final Price')
                                    ->default(true)
                                    ->helperText('Total / final price shown to the customer.'),

                                Toggle::make('has_share_button')
                                    ->label('Share Button')
                                    ->default(true)
                                    ->helperText('Button to share the menu link.'),

                                Toggle::make('has_qr_code')
                                    ->label('QR Code')
                                    ->default(true)
                                    ->helperText('QR code download page.'),
                            ]),

                        // ── Direction ─────────────────────────────────────────────────────────
                        Section::make('Direction')
                            ->columns(2)
                            ->schema([
                                Select::make('default_direction')
                                    ->label('Default Direction')
                                    ->options(['ltr' => 'LTR — Left to Right', 'rtl' => 'RTL — Right to Left'])
                                    ->default('ltr')
                                    ->required()
                                    ->helperText('The text direction this template renders with by default.'),

                                Toggle::make('allows_direction_change')
                                    ->label('Allow Direction Switch')
                                    ->default(true)
                                    ->helperText('Whether the restaurant owner can flip the direction.'),
                            ]),

                    ]),

                Section::make('Settings Schema')
                    ->description('Define the settings this template exposes. Each row is one customisable field shown in the restaurant\'s settings form.')
                    ->schema([
                        Repeater::make('fields')
                            ->label('')
                            ->schema([

                                // ── 1. Key + Label ────────────────────────────────────────────────────────
                                TextInput::make('key')
                                    ->label('Setting Key')
                                    ->placeholder('e.g. accent_color')
                                    ->required()
                                    ->helperText('Unique snake_case identifier — used in Blade templates.'),

                                TextInput::make('label')
                                    ->label('Display Label')
                                    ->placeholder('e.g. Accent Color')
                                    ->required()
                                    ->helperText('Label the restaurant owner sees in their settings form.'),

                                // ── 2. Type selector (searchable / select2) ───────────────────────────────
                                Select::make('type')
                                    ->label('Input Type')
                                    ->options([
                                        'Text inputs' => [
                                            'string' => '📝  Text — single-line free text',
                                            'number' => '🔢  Number — numeric value',
                                            'textarea' => '📄  Textarea — multi-line text',
                                        ],
                                        'Toggle' => [
                                            'boolean' => '✅  Boolean — on / off toggle',
                                        ],
                                        'Pickers' => [
                                            'color' => '🎨  Color — colour picker',
                                        ],
                                        'Dropdowns' => [
                                            'select' => '📋  Select — single choice from a list',
                                            'multiselect' => '📋  Multi-select — multiple choices from a list',
                                        ],
                                        'Media' => [
                                            'image' => '🖼️  Image — media-library upload',
                                        ],
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('default', null))
                                    ->helperText('Controls which input the owner sees.')
                                    ->columnSpanFull(),

                                // ── 3a. Default — Text / Number / Textarea ────────────────────────────────
                                TextInput::make('default')
                                    ->label('Default Value')
                                    ->placeholder(fn (Get $get): string => match ($get('type')) {
                                        'number' => '0',
                                        'textarea' => 'Optional default text…',
                                        default => 'Optional fallback value',
                                    })
                                    ->numeric(fn (Get $get): bool => $get('type') === 'number')
                                    ->hidden(fn (Get $get): bool => ! \in_array($get('type'), ['string', 'number', 'textarea'], true))
                                    ->helperText('Used when the owner has never customised this setting.'),

                                // ── 3b. Default — Boolean toggle ──────────────────────────────────────────
                                Toggle::make('default')
                                    ->label('Default State')
                                    ->onIcon('heroicon-m-check')
                                    ->offIcon('heroicon-m-x-mark')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->hidden(fn (Get $get): bool => $get('type') !== 'boolean')
                                    ->helperText('The initial on/off state before the owner changes it.'),

                                // ── 3c. Default — Color picker ────────────────────────────────────────────
                                ColorPicker::make('default')
                                    ->label('Default Color')
                                    ->hidden(fn (Get $get): bool => $get('type') !== 'color')
                                    ->helperText('The colour used until the owner picks their own.'),

                                // ── 3d. Default — Select / Multi-select (built from accepted options) ─────
                                Select::make('default')
                                    ->label('Default Value')
                                    ->multiple(fn (Get $get): bool => $get('type') === 'multiselect')
                                    ->options(fn (Get $get): array => collect($get('accepted') ?? [])
                                        ->filter(fn ($item) => \is_array($item) && filled($item['value'] ?? null))
                                        ->mapWithKeys(fn ($item) => [$item['value'] => $item['label'] ?? $item['value']])
                                        ->toArray())
                                    ->searchable()
                                    ->live()
                                    ->hidden(fn (Get $get): bool => ! \in_array($get('type'), ['select', 'multiselect'], true))
                                    ->helperText('Automatically populated from the accepted options below. Add options first.'),

                                // ── 3e. Default — Image info note ─────────────────────────────────────────
                                TextInput::make('_image_info')
                                    ->label('Note')
                                    ->default('No default — the owner uploads their own image.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn (Get $get): bool => $get('type') !== 'image')
                                    ->helperText('References a media-library collection on the restaurant model.'),

                                // ── 4. Accepted options — Select / Multi-select only ──────────────────────
                                Repeater::make('accepted')
                                    ->label('Accepted Options')
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Display Label')
                                            ->placeholder('e.g. Serif')
                                            ->required()
                                            ->helperText('What the owner sees in the dropdown.'),
                                        TextInput::make('value')
                                            ->label('Stored Value')
                                            ->placeholder('e.g. serif')
                                            ->required()
                                            ->helperText('The value saved to the database.'),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('+ Add Option')
                                    ->live()
                                    ->reorderable()
                                    ->hidden(fn (Get $get): bool => ! \in_array($get('type'), ['select', 'multiselect'], true))
                                    ->helperText('Once you add options here the "Default Value" dropdown above will populate automatically.')
                                    ->columnSpanFull(),

                            ])
                            ->columns(2)
                            ->addActionLabel('+ Add Setting Field')
                            ->collapsible()
                            ->cloneable()
                            ->reorderable()
                            ->itemLabel(fn (array $state): ?string => match (true) {
                                filled($state['key'] ?? null) && filled($state['type'] ?? null) => strtoupper($state['type']).' › '.$state['key'].
                                        (filled($state['label'] ?? null) ? '  ('.$state['label'].')' : ''),
                                filled($state['key'] ?? null) => $state['key'],
                                default => null,
                            })
                            ->helperText('Drag ⠿ to reorder. The order here is the display order in the restaurant settings form.'),
                    ]),
            ]);
    }
}
