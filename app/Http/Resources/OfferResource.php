<?php

namespace App\Http\Resources;

use App\Http\Requests\StoreOfferRequest;
use App\Models\Dish;
use App\Models\Offer;
use App\Services\Menu\MenuLanguages;
use App\Services\Offers\OfferRules;
use App\Support\MediaUrl;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An offer for the dashboard: what the editor fills itself from (dates in
 * the restaurant's local time, as it sends them), plus where it stands now
 * (`status`, `next_start`) and its deal dish (`deal`). The controller hands
 * each offer its restaurant, so nothing here is a query.
 *
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $languages = MenuLanguages::forOwner($request->user());
        $timezone = $this->restaurant->localTimezone();
        $local = fn (?CarbonImmutable $at): ?string => self::local($at, $timezone);
        // Switched on but left off the menu by what the package lacks.
        $keptOff = OfferRules::for($this->restaurant)->keepsOff($this->resource);
        $standing = self::standing($this->resource, $timezone, $keptOff);

        return [
            'id' => $this->id,
            'title' => MenuLanguages::map($this->resource, 'title', $languages),
            'description' => MenuLanguages::map($this->resource, 'description', $languages),
            'show_text' => $this->show_text,
            'image_url' => MediaUrl::of($this->resource, 'image'),
            'placements' => array_map(fn ($placement): string => $placement->value, $this->placementList()),
            'banner_shape' => $this->banner_shape,
            'popup_shape' => $this->popup_shape,
            'action' => $this->action,
            'action_url' => $this->action_url,
            'show_again' => $this->show_again,
            'delay_seconds' => $this->delay_seconds,
            'ad_delay_seconds' => $this->ad_delay_seconds,
            'ad_seconds' => $this->ad_seconds,
            'discount_type' => $this->discount_type->value,
            'discount_value' => $this->discount_value === null ? null : (string) $this->discount_value,
            'dish_ids' => $this->dishes->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'starts_at' => $local($this->starts_at),
            'ends_at' => $local($this->ends_at),
            'repeat' => $this->repeat->value,
            'weekdays' => array_values((array) $this->weekdays),
            'month_days' => array_map('intval', array_values((array) $this->month_days)),
            'time_windows' => $this->time_windows === null ? null : array_values($this->time_windows),
            // On the menu all the time, its deal working only in its hours.
            'shows_outside_hours' => $this->shows_outside_hours,
            'is_active' => $this->is_active,
            // live, scheduled, ended, paused, or off (kept off the menu, `kept_off`).
            'status' => $standing['status'],
            'kept_off' => $keptOff,
            'next_start' => $standing['next_start'],
            'deal' => $this->deal === null ? null : $this->dealOf($this->deal, $languages),
        ];
    }

    /**
     * Where an offer stands now: live, scheduled, ended, paused, or off
     * when `$keptOff` (OfferRules::keepsOff()) says the package keeps it off
     * the menu; and its next start, as the editor writes a date. A deal
     * dish's `offer` block reads the same (DishResource).
     *
     * @return array{status: string, next_start: string|null}
     */
    public static function standing(Offer $offer, string $timezone, ?string $keptOff = null): array
    {
        $now = CarbonImmutable::now();

        return [
            'status' => $offer->is_active && $keptOff !== null ? 'off' : $offer->statusAt($now, $timezone),
            'next_start' => self::local($offer->schedule($timezone)->nextStart($now), $timezone),
        ];
    }

    /** An instant as the editor writes it: the restaurant's local "2026-11-01T18:00". */
    private static function local(?CarbonImmutable $at, string $timezone): ?string
    {
        return $at?->setTimezone($timezone)->format(StoreOfferRequest::LOCAL_FORMAT);
    }

    /**
     * The offer's deal dish as the editor fills itself from: `currency` is
     * the dish's own (null: the menu's main one), as the request sends it.
     *
     * @param  array<int, string>  $languages
     * @return array<string, mixed>
     */
    private function dealOf(Dish $deal, array $languages): array
    {
        $photo = $deal->getFirstMedia('image');
        $ingredients = MenuLanguages::map($deal, 'ingredients', $languages);

        return [
            'id' => $deal->id,
            'name' => MenuLanguages::map($deal, 'name', $languages),
            // Null when none is written in the languages shown.
            'ingredients' => array_filter($ingredients) === [] ? null : $ingredients,
            'price' => (string) $deal->price,
            'currency' => $deal->currency,
            'category_id' => $deal->category_id,
            'image_url' => MediaUrl::of($deal, 'image'),
            'thumb_url' => $photo === null ? null : url($photo->getAvailableUrl(['thumb'])),
            'is_available' => (bool) $deal->is_available,
        ];
    }
}
