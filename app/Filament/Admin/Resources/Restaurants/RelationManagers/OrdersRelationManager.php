<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The orders guests sent this restaurant, as the owner sees them on the
 * dashboard. An order is written once by the guest, so an admin reads or
 * deletes one but never adds or edits it.
 */
class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('items'))
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('placed_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => ucfirst($state->value))
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Placed => 'warning',
                        OrderStatus::Accepted => 'info',
                        OrderStatus::Ready => 'primary',
                        OrderStatus::Done => 'success',
                        OrderStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('channel')
                    ->label('Came in')
                    ->badge()
                    ->formatStateUsing(fn (OrderChannel $state): string => self::channelLabel($state))
                    ->color(fn (OrderChannel $state): string => $state === OrderChannel::Menu ? 'info' : 'gray'),
                TextColumn::make('items')
                    ->label('What was ordered')
                    ->state(fn (Order $record): string => $record->items
                        ->map(fn (OrderItem $item): string => $item->quantity.' × '.$item->name
                            .($item->choices() === [] ? '' : ' ('.implode(', ', $item->choices()).')'))
                        ->implode('; '))
                    ->wrap(),
                TextColumn::make('total')
                    ->state(fn (Order $record): string => $record->total.' '.$record->currency)
                    ->alignEnd(),
                TextColumn::make('fulfilment')
                    ->formatStateUsing(fn (Fulfilment $state): string => ucfirst(str_replace('_', '-', $state->value)))
                    ->placeholder('-'),
                TextColumn::make('table_name')
                    ->label('Table')
                    ->placeholder('-'),
                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->placeholder('-'),
                TextColumn::make('guest_phone')
                    ->label('Phone')
                    ->placeholder('-')
                    ->copyable(),
                TextColumn::make('address')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Came in')
                    ->options(collect(OrderChannel::cases())->mapWithKeys(fn (OrderChannel $channel): array => [$channel->value => self::channelLabel($channel)])->all()),
                SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [$status->value => ucfirst($status->value)])->all()),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->modalDescription('The order and everything on it are deleted for good. The owner no longer sees it either.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('placed_at', 'desc');
    }

    private static function channelLabel(OrderChannel $channel): string
    {
        return match ($channel) {
            OrderChannel::WhatsApp => 'Sent to WhatsApp',
            OrderChannel::Menu => 'In the menu',
        };
    }
}
