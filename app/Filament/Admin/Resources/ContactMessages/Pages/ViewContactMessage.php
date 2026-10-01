<?php

namespace App\Filament\Admin\Resources\ContactMessages\Pages;

use App\Filament\Admin\Actions\ChangePackageAction;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChangePackageAction::forRequest(),
            DeleteAction::make(),
        ];
    }

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

                    TextEntry::make('user.name')
                        ->label('Signed-in owner')
                        ->placeholder('Not signed in')
                        ->url(fn ($record): ?string => $record->user?->restaurant === null
                            ? null
                            : RestaurantResource::getUrl('edit', ['record' => $record->user->restaurant])),

                    TextEntry::make('package.name')
                        ->label('Requested package')
                        ->placeholder('Not a package request')
                        ->badge()
                        ->color('warning'),

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
