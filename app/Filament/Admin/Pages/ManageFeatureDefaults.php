<?php

namespace App\Filament\Admin\Pages;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use App\Models\FeatureDefault;
use App\Models\Restaurant;
use App\Services\Global\Package;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The limits every restaurant starts with. These are the floor: a restaurant's
 * effective allowance is this value plus whatever grants it has been given (or
 * has bought), so raising a number here raises it for every restaurant at once.
 *
 * @property-read Schema $form
 */
class ManageFeatureDefaults extends Page
{
    protected string $view = 'filament.admin.pages.manage-feature-defaults';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Plan Limits';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $title = 'Plan Limits';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function getSubheading(): ?string
    {
        return 'The baseline every restaurant gets. Extra slots granted or purchased on top of these are managed per restaurant.';
    }

    public function mount(): void
    {
        $values = [];

        foreach (Feature::cases() as $feature) {
            $current = FeatureDefault::for($feature);

            $values[$feature->value] = $feature->kind() === FeatureKind::Flag
                ? $current > 0
                : $current;
        }

        $this->form->fill(['defaults' => $values]);
    }

    public function form(Schema $schema): Schema
    {
        $limits = [];
        $flags = [];

        foreach (Feature::cases() as $feature) {
            if ($feature->kind() === FeatureKind::Flag) {
                $flags[] = Toggle::make("defaults.{$feature->value}")
                    ->label($feature->label())
                    ->helperText('On means every restaurant has this without paying for it.');

                continue;
            }

            $limits[] = TextInput::make("defaults.{$feature->value}")
                ->label($feature->label())
                ->numeric()
                ->minValue(0)
                ->maxValue(100000)
                ->required()
                ->helperText("How many {$feature->label()} a new restaurant can create.");
        }

        $sections = [
            Section::make('Limits')
                ->description('The number of each item a restaurant may create before it needs more slots.')
                ->columns(2)
                ->schema($limits),
        ];

        if ($flags !== []) {
            $sections[] = Section::make('Included features')
                ->description('Normally off — these are the paid add-ons. Turning one on gives it to everyone for free.')
                ->columns(2)
                ->schema($flags);
        }

        return $schema
            ->components([
                Form::make($sections)
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save limits')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $changed = 0;

        foreach (Feature::cases() as $feature) {
            $submitted = $state['defaults'][$feature->value] ?? null;

            if ($submitted === null) {
                continue;
            }

            $value = $feature->kind() === FeatureKind::Flag
                ? (int) ((bool) $submitted)
                : (int) $submitted;

            if ($value === FeatureDefault::for($feature)) {
                continue;
            }

            FeatureDefault::set($feature, $value);
            $changed++;
        }

        if ($changed > 0) {
            // Defaults sit underneath every restaurant's resolved package, so a
            // change here has to invalidate all of them, not just one.
            $this->flushEveryRestaurantsPackage();
        }

        Notification::make()
            ->success()
            ->title($changed > 0 ? "Updated {$changed} limit(s)" : 'Nothing to update')
            ->body($changed > 0 ? 'Every restaurant picks this up immediately.' : null)
            ->send();
    }

    private function flushEveryRestaurantsPackage(): void
    {
        FeatureDefault::flush();

        Restaurant::query()->select('id')->chunkById(500, function ($restaurants): void {
            foreach ($restaurants as $restaurant) {
                Package::flush($restaurant->id);
            }
        });
    }
}
