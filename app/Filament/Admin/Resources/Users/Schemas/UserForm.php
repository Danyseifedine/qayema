<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->description('Basic login credentials and access role.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('John Doe')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->placeholder('john@example.com')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
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
                            ->helperText('Admins have full access to the panel.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
