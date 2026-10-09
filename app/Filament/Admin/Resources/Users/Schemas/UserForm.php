<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\User;
use App\Rules\AvailableSlug;
use App\Rules\Username;
use App\Services\Menu\MenuLanguages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->description('How the account signs in: an email, a username, or both. An owner who signed up with a username may have no email; to get them back in, type a new password here.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('John Doe')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->placeholder('beit.rami')
                            ->maxLength(30)
                            ->live(onBlur: true)
                            ->rules(fn (?User $record): array => [new Username($record?->id)])
                            ->helperText('Signs in with this at /get-started. Lowercase letters, numbers, dots, dashes or underscores.'),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->placeholder('john@example.com')
                            // An admin signs in to /admin with an email; an
                            // owner needs an email or a username.
                            ->required(fn (Get $get): bool => self::isAdmin($get('role')) || blank($get('username')))
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Leave empty for an owner who signs in with a username. Admins need one.'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->placeholder('Min. 8 characters')
                            ->required(fn ($livewire) => $livewire instanceof \App\Filament\Admin\Resources\Users\Pages\CreateUser)
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->helperText('Leave blank to keep the current password when editing.')
                            ->columnSpanFull(),
                        Select::make('role')
                            ->options([
                                UserRole::Admin->value => 'Admin',
                                UserRole::MenuOwner->value => 'Menu Owner',
                            ])
                            ->searchable()
                            ->required()
                            ->default(UserRole::MenuOwner->value)
                            ->live()
                            ->helperText('Admins have full access to the panel.')
                            ->columnSpanFull(),
                    ]),
                self::restaurantSection(),
            ]);
    }

    /**
     * Only when creating an owner: their restaurant and its package, made
     * with the account (CreateUser), so the owner starts on the package
     * agreed and the wizard opens on its second step (contact and logo).
     * Off, the owner names the restaurant in onboarding as usual.
     */
    private static function restaurantSection(): Section
    {
        return Section::make('Restaurant')
            ->description('Create the owner\'s restaurant now, on the package you agreed. They finish the rest (phone, currency, logo) when they first sign in.')
            ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && ! self::isAdmin($get('role')))
            ->statePath('restaurant')
            ->schema([
                Toggle::make('create')
                    ->label('Create the restaurant with the account')
                    ->default(true)
                    ->live(),
                Group::make()
                    ->visible(fn (Get $get): bool => (bool) $get('create'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Restaurant name')
                            ->placeholder('The Golden Spoon')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) ($state ?? ''))))
                            ->helperText('In the menu\'s main language, chosen beside it.'),
                        Select::make('main_locale')
                            ->label('Menu language')
                            ->options(collect(MenuLanguages::choices())->mapWithKeys(fn (string $code): array => [$code => MenuLanguages::nameOf($code)])->all())
                            ->default(MenuLanguages::DEFAULT_MAIN)
                            ->required()
                            ->helperText('Every dish is written in it. The owner can add a second one, or change it, in the dashboard.'),
                        TextInput::make('slug')
                            ->label('Menu link')
                            ->placeholder('the-golden-spoon')
                            ->prefix(url('/').'/')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->rules([new AvailableSlug])
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) ($state ?? ''))))
                            ->dehydrateStateUsing(fn ($state) => Str::slug((string) ($state ?? '')))
                            ->helperText('Lowercase letters, numbers and hyphens. It is on the QR code.'),
                        ...PackageFields::components(),
                    ]),
            ]);
    }

    /** The role field holds the enum on an edit page and its value on create. */
    private static function isAdmin(mixed $role): bool
    {
        return ($role instanceof UserRole ? $role : UserRole::tryFrom((string) $role)) === UserRole::Admin;
    }
}
