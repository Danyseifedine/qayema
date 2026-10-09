<?php

namespace App\Services\Push;

use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\DeviceToken;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Menu\MenuLanguages;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * What the admins' phones are told, always naming the restaurant (never its
 * owner's name or email): a package request or a message from
 * the contact form as it arrives, a restaurant just opened by its owner, an
 * owner editing their menu (once an hour at most), and each morning the
 * packages about to end. `data.type` tells the app what to open when it is
 * tapped; `data.restaurant_id`, when there is one, opens that restaurant.
 */
class AdminAlerts
{
    public const PACKAGE_REQUEST = 'package_request';

    public const CONTACT_MESSAGE = 'contact_message';

    public const PACKAGES_ENDING = 'packages_ending';

    public const NEW_RESTAURANT = 'new_restaurant';

    public const MENU_EDITING = 'menu_editing';

    /** A test from /admin: opens nothing when tapped. */
    public const TEST = 'test';

    /** An owner editing for an afternoon is one notification an hour, not one per dish. */
    public const MENU_EDITING_QUIET_MINUTES = 60;

    /** Where the admins are: "today" and "tomorrow" follow its calendar. */
    public const TIMEZONE = 'Asia/Beirut';

    public function __construct(private readonly PushSender $sender) {}

    /**
     * Sent after the response, so the visitor never waits on Firebase.
     */
    public function contactReceived(ContactMessage $message): void
    {
        $this->later($this->forContact($message));
    }

    /** Sent after the response: nobody waits on Firebase. */
    private function later(PushMessage $message): void
    {
        defer(fn () => $this->sender->send($this->adminPhones(), $message));
    }

    /**
     * A test an admin sends from /admin to see that notifications reach the
     * phones: theirs, or every admin's. Sent now (not after the response)
     * so the admin sees how many it reached.
     *
     * @return array{phones: int, reached: int}
     */
    public function test(User $admin, bool $everyAdmin, string $title, string $body): array
    {
        $phones = $everyAdmin ? $this->adminPhones() : $admin->deviceTokens()->getQuery();
        $count = (clone $phones)->count();

        return [
            'phones' => $count,
            'reached' => $count === 0 ? 0 : $this->sender->send($phones, new PushMessage($title, $body, ['type' => self::TEST])),
        ];
    }

    /** An owner has just named their restaurant in onboarding. */
    public function newRestaurant(Restaurant $restaurant): void
    {
        $this->later(new PushMessage(
            title: 'New restaurant',
            body: $this->nameOf($restaurant).' just signed up.',
            data: ['type' => self::NEW_RESTAURANT, 'restaurant_id' => (string) $restaurant->id],
        ));
    }

    /**
     * An owner changed their menu. Told once, then quiet for
     * MENU_EDITING_QUIET_MINUTES for this restaurant however much more they
     * change. True when this one was sent.
     */
    public function menuEditing(Restaurant $restaurant): bool
    {
        $quiet = now()->addMinutes(self::MENU_EDITING_QUIET_MINUTES);
        if (! Cache::add("admin-alerts:menu-editing:{$restaurant->id}", true, $quiet)) {
            return false;
        }

        $this->later(new PushMessage(
            title: $this->nameOf($restaurant).' is editing its menu',
            body: 'More changes in the next hour stay quiet.',
            data: ['type' => self::MENU_EDITING, 'restaurant_id' => (string) $restaurant->id],
        ));

        return true;
    }

    /**
     * @param  Collection<int, Restaurant>  $restaurants  ending soon, the soonest first
     */
    public function packagesEnding(Collection $restaurants): int
    {
        if ($restaurants->isEmpty()) {
            return 0;
        }

        $names = $restaurants->take(3)
            ->map(fn (Restaurant $restaurant): string => $this->nameOf($restaurant).' ('.$this->when($restaurant->package_ends_at).')')
            ->implode(', ');
        $more = $restaurants->count() > 3 ? ' and '.($restaurants->count() - 3).' more' : '';

        return $this->sender->send($this->adminPhones(), new PushMessage(
            title: $restaurants->count() === 1 ? 'A package ends soon' : $restaurants->count().' packages end soon',
            body: $names.$more.'.',
            data: ['type' => self::PACKAGES_ENDING],
        ));
    }

    public function forContact(ContactMessage $message): PushMessage
    {
        if ($message->isPackageRequest()) {
            $restaurant = $message->user?->restaurant;

            return new PushMessage(
                title: 'Package request: '.$message->package?->name,
                // An owner who has not named their restaurant yet has no
                // name to show; theirs is never shown instead.
                body: ($restaurant === null ? 'An account with no restaurant yet' : $this->nameOf($restaurant))
                    .' asks for '.$message->package?->name.'.',
                data: array_filter([
                    'type' => self::PACKAGE_REQUEST,
                    'restaurant_id' => $restaurant === null ? null : (string) $restaurant->id,
                ]),
            );
        }

        return new PushMessage(
            title: 'New message from '.$message->name,
            body: str($message->message)->squish()->limit(120)->value(),
            data: ['type' => self::CONTACT_MESSAGE],
        );
    }

    private function nameOf(Restaurant $restaurant): string
    {
        return MenuLanguages::reader($restaurant, MenuLanguages::main($restaurant))($restaurant, 'name');
    }

    /** "today", "tomorrow", "in 3 days", by the admins' calendar. */
    private function when(CarbonInterface $end): string
    {
        $today = now(self::TIMEZONE)->startOfDay();
        $days = (int) max(0, $today->diffInDays($end->copy()->setTimezone(self::TIMEZONE)->startOfDay()));

        return match ($days) {
            0 => 'today',
            1 => 'tomorrow',
            default => "in {$days} days",
        };
    }

    /** @return Builder<DeviceToken> */
    private function adminPhones(): Builder
    {
        return DeviceToken::query()->whereHas('user', fn (Builder $user) => $user->where('role', UserRole::Admin));
    }
}
