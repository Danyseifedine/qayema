<?php

namespace App\Filament\Admin\Pages;

use App\Enums\WritingHelpAction;
use App\Models\AppSetting;
use App\Services\Ai\WritingHelpCredits;
use App\Services\Ai\WritingHelpLog;
use App\Services\Ai\WritingHelpPrice;
use App\Services\Packages\Entitlements;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

/**
 * What an AI credit is worth and what each action costs in credits
 * (WritingHelpCredits), with what they really cost over the last 30 days
 * to check the prices against. A package's credits are its AI budget at
 * this worth, so saving moves every package's credits; a restaurant given
 * its own number keeps it.
 *
 * @property-read Schema $form
 */
class AiCredits extends Page
{
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'AI credits';

    protected static ?string $title = 'AI credits';

    protected static ?string $slug = 'ai-credits';

    protected static ?int $navigationSort = 2;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'credit_cents' => WritingHelpCredits::creditCents(),
            ...collect(WritingHelpAction::cases())
                ->mapWithKeys(fn (WritingHelpAction $action): array => [$action->value => $action->credits()])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('What a credit is worth')
                    ->description('Owners see their AI as credits a month, never as money. A package\'s credits are its AI budget divided by this, so changing it changes every package\'s credits; a restaurant given its own number keeps it.')
                    ->schema([
                        TextInput::make('credit_cents')
                            ->label('One credit, in US cents')
                            ->suffix('¢')
                            ->numeric()
                            ->required()
                            ->minValue(0.0001)
                            ->maxValue(100)
                            ->step(0.0001)
                            ->live(onBlur: true)
                            ->helperText(fn (mixed $state): string => is_numeric($state) && $state > 0
                                ? '$1 buys '.Number::format((int) WritingHelpCredits::forBudget(100, (float) $state)).' credits.'
                                : 'More than 0.'),
                    ]),
                Section::make('What each action costs')
                    ->description('Taken from the month\'s credits when the owner gets the text; a failed request costs nothing.')
                    ->columns(2)
                    ->schema(array_map(
                        fn (WritingHelpAction $action): TextInput => TextInput::make($action->value)
                            ->label($action->label())
                            ->suffix('credits')
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->maxValue(1000),
                        WritingHelpAction::cases(),
                    )),
                Section::make('What it really costs, last 30 days')
                    ->description(WritingHelpPrice::describe())
                    ->columns(3)
                    ->schema($this->realCosts()),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Save')->submit('save')->keyBindings(['mod+s'])]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AppSetting::write([
            'ai_credit_cents' => (float) $data['credit_cents'],
            ...collect(WritingHelpAction::cases())
                ->mapWithKeys(fn (WritingHelpAction $action): array => ["ai_credits_{$action->value}" => (int) $data[$action->value]])
                ->all(),
        ]);

        // Every package's credits are worked out from the worth.
        Entitlements::flushAll();

        Notification::make()->success()->title('Saved')->send();
    }

    /**
     * Credits taken against the real cost, and what a translation and a
     * description really cost against their price: the numbers to set the
     * prices by.
     *
     * @return array<int, TextEntry>
     */
    private function realCosts(): array
    {
        $usage = app(WritingHelpLog::class)->overall();
        $worth = WritingHelpCredits::dollarsOf(1);
        $entry = fn (string $name, string $label, string $state): TextEntry => TextEntry::make($name)->label($label)->state($state);
        $average = function (WritingHelpAction $action) use ($usage, $worth): string {
            $uses = $usage["{$action->value}_uses"];

            if ($uses === 0) {
                return 'None yet';
            }

            $cost = $usage["{$action->value}_cost"] / $uses;

            return WritingHelpPrice::format($cost).' each, '.Number::format($cost / $worth, maxPrecision: 2)." credits' worth (priced at {$action->credits()})";
        };

        return [
            $entry('used_credits', 'Credits taken', Number::format($usage['credits'])),
            $entry('real_cost', 'Real cost', WritingHelpPrice::format($usage['cost'])),
            $entry('per_credit', 'Real cost of a credit', $usage['credits'] === 0
                ? 'None yet (worth '.WritingHelpPrice::format($worth).')'
                : WritingHelpPrice::format($usage['cost'] / $usage['credits']).' (worth '.WritingHelpPrice::format($worth).')'),
            ...array_map(
                fn (WritingHelpAction $action): TextEntry => $entry("{$action->value}_average", $action->label(), $average($action)),
                WritingHelpAction::cases(),
            ),
        ];
    }
}
