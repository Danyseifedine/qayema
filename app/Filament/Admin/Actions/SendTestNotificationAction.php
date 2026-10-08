<?php

namespace App\Filament\Admin\Actions;

use App\Enums\UserRole;
use App\Models\DeviceToken;
use App\Services\Push\AdminAlerts;
use App\Services\Push\PushSender;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

/**
 * A test notification to the admin app's phones, from the admin home: the
 * way to see that Firebase, the key on this server and a phone all work
 * together, and which part does not when it fails.
 */
class SendTestNotificationAction
{
    private const MINE = 'mine';

    private const EVERY_ADMIN = 'every_admin';

    public static function make(): Action
    {
        return Action::make('sendTestNotification')
            ->label('Send test notification')
            ->icon(Heroicon::OutlinedBellAlert)
            ->color('gray')
            ->modalHeading('Send a test notification')
            ->modalDescription(fn (): string => self::status())
            ->modalSubmitActionLabel('Send')
            ->schema([
                ToggleButtons::make('to')
                    ->label('Send to')
                    ->options([
                        self::MINE => 'My phones',
                        self::EVERY_ADMIN => "Every admin's phones",
                    ])
                    ->default(self::MINE)
                    ->inline()
                    ->required(),
                TextInput::make('title')
                    ->default('Test from Qayema')
                    ->required()
                    ->maxLength(100),
                Textarea::make('body')
                    ->label('Message')
                    ->default('If you can read this, notifications reach this phone.')
                    ->required()
                    ->rows(2)
                    ->maxLength(250),
            ])
            ->action(function (array $data): void {
                if (! PushSender::enabled()) {
                    Notification::make()
                        ->title('Notifications are off on this server')
                        ->body('Set FIREBASE_CREDENTIALS in .env to the Firebase key file, then run php artisan optimize.')
                        ->danger()
                        ->send();

                    return;
                }

                $result = app(AdminAlerts::class)->test(auth()->user(), $data['to'] === self::EVERY_ADMIN, $data['title'], $data['body']);

                self::report($result['phones'], $result['reached']);
            });
    }

    /** What the modal says before sending: on or off, and how many phones. */
    private static function status(): string
    {
        if (! PushSender::enabled()) {
            return 'Notifications are off on this server: FIREBASE_CREDENTIALS is not set.';
        }

        $mine = DeviceToken::query()->where('user_id', auth()->id())->count();
        $all = DeviceToken::query()->whereHas('user', fn (Builder $user) => $user->where('role', UserRole::Admin))->count();

        return "Notifications are on. Phones signed in to the admin app: {$mine} of yours, {$all} in all.";
    }

    private static function report(int $phones, int $reached): void
    {
        $notification = match (true) {
            $phones === 0 => Notification::make()
                ->title('No phone to send to')
                ->body('Sign in to the Qayema Admin app on a phone and allow notifications, then try again.')
                ->warning(),
            $reached === 0 => Notification::make()
                ->title('Firebase did not deliver it')
                ->body('Nothing reached the '.$phones.' '.str('phone')->plural($phones).'. The reason is in the log (Push notification not sent), or the phones were removed because Firebase no longer knew them.')
                ->danger(),
            $reached < $phones => Notification::make()
                ->title("Sent to {$reached} of {$phones} phones")
                ->body('Firebase no longer knew the others (the app removed or reinstalled), so they were forgotten.')
                ->warning(),
            default => Notification::make()
                ->title('Sent to '.$reached.' '.str('phone')->plural($reached))
                ->body('It should appear within a few seconds.')
                ->success(),
        };

        $notification->send();
    }
}
