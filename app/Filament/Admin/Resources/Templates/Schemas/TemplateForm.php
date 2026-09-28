<?php

namespace App\Filament\Admin\Resources\Templates\Schemas;

use App\Models\Template;
use App\Support\Color;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Everything that makes a template a template: its identity and the list of
 * settings its owner may change. A template grants nothing; one marked premium
 * needs a package with premium designs. Publishing a new
 * template is this form plus a Blade view named after the slug.
 */
class TemplateForm
{
    /**
     * The setting types a template may expose, each with its own field on the
     * dashboard's Appearance page (Template::SETTING_TYPES).
     *
     * @var array<string, string>
     */
    private const SETTING_TYPES = [
        'color' => 'Color',
        'text' => 'Text',
        'boolean' => 'Toggle',
        'select' => 'Select',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Identity')
                    ->description('Name, slug and ordering.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.en')
                            ->label('Name (English)')
                            ->placeholder('e.g. Simple, Elegant, Dark')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) ($state ?? ''))))
                            ->helperText('Shown to owners on the Design page.'),
                        TextInput::make('name.ar')
                            ->label('Name (Arabic)')
                            ->maxLength(255)
                            ->helperText('Owners reading the dashboard in Arabic see this; English otherwise.'),
                        TextInput::make('slug')
                            ->placeholder('simple')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Used to load the Blade view: resources/views/menu/templates/{slug}.blade.php'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order in the template picker.'),
                        Toggle::make('is_active')
                            ->label('Template is active (owners can pick it)')
                            ->default(true),
                        Toggle::make('is_premium')
                            ->label('Premium design')
                            ->helperText('Only packages with "Premium designs" can use it. A restaurant that loses the package keeps its choice, and its menu shows the first free design until it is back.'),
                        Textarea::make('description.en')
                            ->label('Description (English)')
                            ->placeholder('Describe what this design looks like and when to use it…')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('description.ar')
                            ->label('Description (Arabic)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Thumbnail')
                    ->description('Preview image shown in the template picker.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('thumbnail')
                            ->label('Thumbnail Image')
                            ->collection('thumbnail')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended 800×500 px. Max 5 MB.'),
                    ]),

                Section::make('Owner Settings')
                    ->description('What the owner may customize on this template. Each setting shows on the dashboard\'s Appearance page with its label; the view reads it as $settings[\'key\'], and a colour also becomes a CSS variable (primary_color → var(--primary-color)). Leave empty to make the design fixed.')
                    ->schema([
                        Repeater::make('settings_schema')
                            ->label('')
                            ->addActionLabel('Add a setting')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label']['en'] ?? $state['key'] ?? null)
                            ->columns(3)
                            ->schema([
                                TextInput::make('key')
                                    ->placeholder('primary_color')
                                    ->required()
                                    ->regex(Template::SETTING_KEY_PATTERN)
                                    ->validationMessages(['regex' => 'Lowercase letters, digits and underscores, starting with a letter.'])
                                    ->helperText('Referenced from the Blade view.'),
                                Select::make('type')
                                    ->options(self::SETTING_TYPES)
                                    ->default('color')
                                    ->live()
                                    ->required(),
                                TextInput::make('default')
                                    ->placeholder(Template::DEFAULT_PRIMARY_COLOR)
                                    // An on/off default is written "true" or "false".
                                    ->rules(fn ($get): array => $get('type') === 'color' ? [Color::RULE] : [])
                                    ->helperText('Applied when the owner has not chosen one.'),
                                TextInput::make('label.en')
                                    ->label('Label (English)')
                                    ->placeholder('Main colour')
                                    ->helperText('What the owner reads. The key is shown when empty.'),
                                TextInput::make('label.ar')
                                    ->label('Label (Arabic)')
                                    ->placeholder('اللون الرئيسي'),
                                TextInput::make('contrast_with')
                                    ->label('Read against')
                                    ->placeholder('background_color')
                                    ->visible(fn ($get): bool => $get('type') === 'color')
                                    ->helperText('Another colour key: the dashboard warns when the two are hard to read together.'),
                                TagsInput::make('options')
                                    ->label('Choices')
                                    ->placeholder('Add a choice')
                                    ->visible(fn ($get): bool => $get('type') === 'select')
                                    ->helperText('The only values the owner may pick.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
