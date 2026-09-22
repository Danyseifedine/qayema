<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\CoinTransactionType;
use App\Exceptions\InsufficientCoins;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The user's coin ledger. It is append-only, so there is no editing or
 * deleting here — coins are added or taken away by writing a new row, which is
 * what keeps the balance reconstructable.
 */
class CoinTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'coinTransactions';

    protected static ?string $title = 'Coins';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (CoinTransactionType $state): string => $state->label()),
                TextColumn::make('amount')
                    ->label('Change')
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').$state)
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),
                TextColumn::make('balance_after')
                    ->label('Balance')
                    ->numeric(),
                TextColumn::make('reference')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->headerActions([
                $this->grantAction(),
                $this->deductAction(),
            ]);
    }

    private function grantAction(): Action
    {
        return Action::make('grant')
            ->label('Give coins')
            ->icon('heroicon-o-plus-circle')
            ->schema([
                TextInput::make('amount')
                    ->label('Coins to give')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Textarea::make('note')
                    ->label('Note')
                    ->rows(2)
                    ->helperText('Stored with the ledger row for your own reference.'),
            ])
            ->action(function (array $data): void {
                $this->getOwnerRecord()->wallet()->credit(
                    (int) $data['amount'],
                    CoinTransactionType::AdminGrant,
                    null,
                    filled($data['note'] ?? null) ? ['note' => $data['note']] : null,
                );

                Notification::make()
                    ->success()
                    ->title("Gave {$data['amount']} coins")
                    ->send();
            });
    }

    private function deductAction(): Action
    {
        return Action::make('deduct')
            ->label('Take coins back')
            ->icon('heroicon-o-minus-circle')
            ->color('danger')
            ->schema([
                TextInput::make('amount')
                    ->label('Coins to remove')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Textarea::make('note')
                    ->label('Reason')
                    ->rows(2),
            ])
            ->action(function (array $data): void {
                try {
                    $this->getOwnerRecord()->wallet()->debit(
                        (int) $data['amount'],
                        CoinTransactionType::Spend,
                        null,
                        filled($data['note'] ?? null) ? ['note' => $data['note']] : null,
                    );
                } catch (InsufficientCoins $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Not enough coins')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title("Removed {$data['amount']} coins")
                    ->send();
            });
    }
}
