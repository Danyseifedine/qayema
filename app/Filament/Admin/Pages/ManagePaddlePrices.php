<?php

namespace App\Filament\Admin\Pages;

use App\Models\CoinPack;
use App\Services\Global\PaddlePrices;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * What each coin pack costs in real money. Paddle stays the billing source of
 * truth: this page reads the live amounts and pushes edits back through the
 * Paddle API, so checkout can never charge something different from what the
 * dashboard advertises.
 *
 * @property-read Schema $form
 */
class ManagePaddlePrices extends Page
{
    protected string $view = 'filament.admin.pages.manage-paddle-prices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Coin Pack Prices';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $title = 'Coin Pack Prices';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function getSubheading(): ?string
    {
        $environment = config('cashier.sandbox') ? 'sandbox' : 'live';

        return "Prices are stored on Paddle ({$environment} environment). Saving pushes the change to Paddle and it applies everywhere immediately.";
    }

    public function mount(PaddlePrices $prices): void
    {
        $amounts = $prices->amounts();

        $values = [];

        foreach ($this->packs() as $pack) {
            $values[$pack->slug] = isset($amounts[$pack->slug])
                ? number_format($amounts[$pack->slug]['amount'] / 100, 2, '.', '')
                : null;
        }

        $this->form->fill(['prices' => $values]);
    }

    public function form(Schema $schema): Schema
    {
        $amounts = app(PaddlePrices::class)->amounts();

        $inputs = [];

        foreach ($this->packs() as $pack) {
            $currency = $amounts[$pack->slug]['currency'] ?? 'USD';

            // Not required: a pack whose live amount couldn't be fetched mounts
            // as null, and a hard requirement would block saving the others.
            $inputs[] = TextInput::make("prices.{$pack->slug}")
                ->label($pack->name.' — '.$pack->coins.' coins')
                ->numeric()
                ->minValue(0.01)
                ->step(0.01)
                ->prefix($currency)
                ->helperText("What the customer pays for {$pack->coins} coins.");
        }

        if ($inputs === []) {
            $inputs[] = TextInput::make('prices.none')
                ->label('No sellable coin packs')
                ->disabled()
                ->helperText('No active pack has a Paddle price id for this environment yet.');
        }

        return $schema
            ->components([
                Form::make([
                    Section::make('Pack prices')
                        ->description('Amounts in the price currency. Changes are pushed straight to Paddle.')
                        ->columns(2)
                        ->schema($inputs),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save to Paddle')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(PaddlePrices $prices): void
    {
        $state = $this->form->getState();
        $amounts = $prices->amounts();

        $updated = 0;

        foreach ($this->packs() as $pack) {
            $dollars = $state['prices'][$pack->slug] ?? null;

            if ($dollars === null) {
                continue;
            }

            $cents = (int) round(((float) $dollars) * 100);

            if ($cents === ($amounts[$pack->slug]['amount'] ?? null)) {
                continue;
            }

            $prices->update($pack, $cents, $amounts[$pack->slug]['currency'] ?? 'USD');
            $updated++;
        }

        Notification::make()
            ->success()
            ->title($updated > 0 ? "Updated {$updated} price(s) on Paddle" : 'Nothing to update')
            ->send();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, CoinPack>
     */
    private function packs(): \Illuminate\Database\Eloquent\Collection
    {
        return CoinPack::query()->sellable()->get();
    }
}
