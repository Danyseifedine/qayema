<?php

namespace App\Rules;

use App\Models\PreviousSlug;
use App\Models\Restaurant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A menu link a restaurant may take: not one of the app's own addresses, not
 * another restaurant's, and not another restaurant's former link (that one
 * still forwards its printed QR codes). A restaurant may take back its own
 * former link.
 */
class AvailableSlug implements ValidationRule
{
    public const MAX_LENGTH = 100;

    /** Letters, numbers and dashes, starting and ending with a letter or number. */
    public const FORMAT = '/^[a-z0-9][a-z0-9-]*[a-z0-9]$/';

    public function __construct(private readonly ?int $restaurantId = null) {}

    /**
     * Every rule a menu link must pass, the one list behind onboarding, the
     * owner's API, the admin API and the admin panel, so none of them takes a
     * link another refuses.
     *
     * @param  int|null  $restaurantId  the restaurant the link is for, which may keep or take back its own
     * @return array<int, mixed>
     */
    public static function rules(?int $restaurantId = null): array
    {
        return [
            'required', 'string', 'min:2', 'max:'.self::MAX_LENGTH,
            'regex:'.self::FORMAT,
            new self($restaurantId),
        ];
    }

    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isFree($value, $this->restaurantId)) {
            $fail(__('That link is already taken. Try another one.'));
        }
    }

    public static function isFree(string $slug, ?int $restaurantId = null): bool
    {
        if (in_array($slug, Restaurant::RESERVED_SLUGS, true)) {
            return false;
        }

        $takenNow = Restaurant::query()
            ->where('slug', $slug)
            ->when($restaurantId, fn ($query) => $query->whereKeyNot($restaurantId))
            ->exists();

        $heldBefore = PreviousSlug::query()
            ->where('slug', $slug)
            ->when($restaurantId, fn ($query) => $query->where('restaurant_id', '!=', $restaurantId))
            ->exists();

        return ! $takenNow && ! $heldBefore;
    }
}
