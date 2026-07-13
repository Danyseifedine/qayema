<?php

namespace App\Filament\Admin\Pages;

use App\Services\Global\FeatureCatalog;
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
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Manage the Paddle prices behind the purchasable add-on catalog. Paddle stays
 * the billing source of truth; this page reads the live amounts and pushes
 * edits back through the Paddle API, so the SPA (which displays via
 * GET /api/catalog) can never drift from what checkout charges.
 *
 * @property-read Schema $form
 */
class ManagePaddlePrices extends Page
{
    protected string $view = 'filament.admin.pages.manage-paddle-prices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Add-on Prices';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $title = 'Add-on Prices';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function getSubheading(): ?string
    {
        $environment = config('cashier.sandbox') ? 'sandbox' : 'live';

        return "Prices are stored on Paddle ({$environment} environment). Saving pushes the change to Paddle and it applies everywhere immediately.";
    }

    public function mount(FeatureCatalog $catalog, PaddlePrices $prices): void
    {
        $amounts = $prices->amounts();

        $values = [];

        foreach ($catalog->all() as $id => $entry) {
            // Admins edit what the customer actually pays: the PACK price
            // (unit amount × step). Converted back to per-unit cents on save.
            $step = (int) ($entry['step'] ?? 1);

            $values[$id] = isset($amounts[$id])
                ? number_format($amounts[$id]['unit_amount'] * $step / 100, 2, '.', '')
                : null;
        }

        $this->form->fill(['prices' => $values]);
    }

    public function form(Schema $schema): Schema
    {
        $catalog = app(FeatureCatalog::class);
        $amounts = app(PaddlePrices::class)->amounts();

        $inputs = [];

        foreach ($catalog->all() as $id => $entry) {
            $step = (int) ($entry['step'] ?? 1);
            $currency = $amounts[$id]['currency'] ?? 'USD';

            // Not required: an entry whose live amount couldn't be fetched mounts
            // as null, and a hard requirement would then block saving the others.
            // Null values are simply skipped on save.
            $inputs[] = TextInput::make("prices.{$id}")
                ->label(Str::headline($id).' — '.$entry['slug'])
                ->numeric()
                ->minValue(0.01)
                ->step(0.01)
                ->prefix($currency)
                ->helperText(
                    $entry['kind'] === 'limit'
                        ? "What the customer pays for one pack of {$step}."
                        : 'One-time price for this add-on.'
                );
        }

        if ($inputs === []) {
            $inputs[] = TextInput::make('prices.none')
                ->label('No sellable add-ons')
                ->disabled()
                ->helperText('No catalog entry has a Paddle price id for this environment yet (config/paddle.php).');
        }

        return $schema
            ->components([
                Form::make([
                    Section::make('Unit prices')
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

    public function save(FeatureCatalog $catalog, PaddlePrices $prices): void
    {
        $state = $this->form->getState();
        $amounts = $prices->amounts();

        $updated = 0;

        foreach ($state['prices'] ?? [] as $id => $dollars) {
            $entry = $catalog->find($id);

            if ($dollars === null || $entry === null) {
                continue;
            }

            // The admin typed the pack price; Paddle stores per-unit cents.
            $step = (int) ($entry['step'] ?? 1);
            $cents = (int) round(((float) $dollars) * 100 / $step);

            if ($cents === ($amounts[$id]['unit_amount'] ?? null)) {
                continue;
            }

            $prices->update($id, $cents, $amounts[$id]['currency'] ?? 'USD');
            $updated++;
        }

        Notification::make()
            ->success()
            ->title($updated > 0 ? "Updated {$updated} price(s) on Paddle" : 'Nothing to update')
            ->send();
    }
}
