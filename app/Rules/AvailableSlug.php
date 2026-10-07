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
    public function __construct(private readonly ?int $restaurantId = null) {}

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
