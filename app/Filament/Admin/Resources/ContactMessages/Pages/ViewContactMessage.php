<?php

namespace App\Filament\Admin\Resources\ContactMessages\Pages;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sender Details')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Full name'),

                    TextEntry::make('email')
                        ->label('Email address')
                        ->copyable()
                        ->url(fn ($record) => 'mailto:'.$record->email),

                    TextEntry::make('ip_address')
                        ->label('IP Address'),

                    TextEntry::make('created_at')
                        ->label('Received at')
                        ->dateTime(),
                ]),

            Section::make('Message')
                ->schema([
                    TextEntry::make('message')
                        ->label('')
                        ->prose()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
