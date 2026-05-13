<?php

namespace App\Filament\Admin\Resources\Tags\Pages;

use App\Filament\Admin\Resources\Tags\TagResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique('tags', 'slug'),

                Select::make('category')
                    ->required()
                    ->options([
                        'cuisine' => 'Cuisine',
                        'dietary' => 'Dietary',
                        'vibe' => 'Vibe',
                        'style' => 'Style',
                    ]),
            ]),
        ]);
    }
}
