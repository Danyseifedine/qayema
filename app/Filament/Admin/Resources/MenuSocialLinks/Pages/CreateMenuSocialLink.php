<?php

namespace App\Filament\Admin\Resources\MenuSocialLinks\Pages;

use App\Filament\Admin\Resources\MenuSocialLinks\MenuSocialLinkResource;
use App\Models\Menu;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMenuSocialLink extends CreateRecord
{
    protected static string $resource = MenuSocialLinkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $menu = Menu::find($data['menu_id'] ?? null);

        if ($menu && $menu->hasReachedSocialLinkLimit()) {
            throw ValidationException::withMessages([
                'menu_id' => "This menu has reached the maximum limit of {$menu->social_link_limit} social links.",
            ]);
        }

        return $data;
    }
}
