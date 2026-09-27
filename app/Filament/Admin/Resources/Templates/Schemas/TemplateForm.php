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
 * settings its owner may change. Templates carry no price and grant nothing —
 * limits and features come from the restaurant's package. Publishing a new
 * template is this form plus a Blade view named after the slug.
 */
class TemplateForm
{
    /**
     * The setting types a template may expose. Only colours have an editor in
     * the dashboard (Colors & fonts); the others are read by the view at
     * their default until an owner-facing editor exists.
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
                        TextInput::make('name')
                            ->placeholder('e.g. Simple, Elegant, Dark')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) ($state ?? ''))))
                            ->helperText('Human-readable name shown to admins and owners.'),
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
                        Textarea::make('description')
                            ->placeholder('Describe what this template looks like and when to use it…')
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
                    ->description('What the owner may customize on this template. Each colour shows on the dashboard\'s Colors & fonts page with its label, and reaches the view as a CSS variable: primary_color becomes var(--primary-color). Leave empty to make the design fixed.')
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
