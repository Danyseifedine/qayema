<?php

namespace App\Filament\Admin\Resources\Packages\Pages;

use App\Enums\PackageTier;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Packages\Schemas\PackageForm;
use App\Models\Package;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/**
 * A new package, opened on the default package's limits, features and AI
 * budget, so it never starts unlimited or with AI that costs without end;
 * last in the order, active, bronze. Nobody is on it until an admin
 * assigns it or an owner asks for it.
 */
class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $default = Package::default();

        $this->form->fill([
            'currency' => $default->currency ?? 'USD',
            'price_cents' => 0,
            'sort_order' => (int) Package::query()->max('sort_order') + 1,
            'tier' => PackageTier::Bronze->value,
            'is_active' => true,
            'features' => PackageForm::formFeatures((array) ($default->features ?? [])),
            'ai_budget_cents' => $default?->ai_budget_cents,
        ]);

        $this->callHook('afterFill');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Package created')
            ->body('It shows on the landing page and the dashboard while it is active. Assign it from a restaurant\'s Package section.');
    }
}
