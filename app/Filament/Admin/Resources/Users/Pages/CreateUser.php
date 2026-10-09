<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use App\Services\Portal\OnboardingService;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * The account, and with an owner the restaurant the form describes
     * (UserForm's Restaurant section), together or not at all.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $restaurant = $data['restaurant'] ?? [];
        unset($data['restaurant']);

        return DB::transaction(function () use ($data, $restaurant): User {
            $user = User::create($data);

            if ($user->isMenuOwner() && ($restaurant['create'] ?? false)) {
                app(OnboardingService::class)->openForOwner(
                    $user,
                    $restaurant['name'],
                    $restaurant['slug'],
                    (int) $restaurant['package_id'],
                    CarbonImmutable::parse($restaurant['package_started_at']),
                    PackageFields::endsAt($restaurant),
                    $restaurant['note'] ?? null,
                    $restaurant['main_locale'] ?? null,
                );
            }

            return $user;
        });
    }
}
