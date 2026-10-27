<?php

namespace App\Services\Offers;

use App\Enums\Feature;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Services\Media\MediaService;
use App\Services\Menu\MenuLanguages;

/**
 * An offer's own dish (its "deal", Offer::deal()), made and edited from the
 * offer editor ("Family meal: 2 pizzas + drinks, $25"): written with the
 * offer's save, counted against the package's dishes like any new dish,
 * and photographed with its own upload or a copy of the offer's picture.
 * Guests meet it only while its offer is live (LiveOffers).
 */
class DealDish
{
    public function __construct(private readonly MediaService $media) {}

    /**
     * Make or edit the offer's deal dish from what the editor sent, inside
     * the offer's save (its transaction, the restaurant read under its row
     * lock). Only the menu's active languages are written; hidden ones stay.
     * A new one is available and last in its category, and so is one moved
     * to another category.
     *
     * @param  array<string, mixed>  $data  the request's `deal`, validated
     * @return array{0: Dish, 1: bool} the dish, and whether it was made now
     */
    public function save(Offer $offer, array $data, Restaurant $locked): array
    {
        $deal = $offer->deal()->first();
        $created = $deal === null;

        if ($deal === null) {
            $locked->ensureRoomFor(
                Feature::DishLimit,
                'deal.name',
                'You have reached your plan limit of :limit dishes.',
                'You have reached the fair-use limit of :limit dishes. Contact us if you need more.',
            );

            $deal = new Dish(['is_available' => true]);
            $deal->restaurant()->associate($locked);
            $deal->offer()->associate($offer);
        }

        $category = (int) $data['category_id'];

        if ($created || $deal->category_id !== $category) {
            $deal->display_order = (int) $locked->dishes()->where('category_id', $category)->max('display_order') + 1;
        }

        $deal->fill([
            'price' => $data['price'],
            'currency' => $locked->ownCurrency(isset($data['currency']) ? (string) $data['currency'] : null),
            'category_id' => $category,
        ]);

        $languages = $locked->menuLanguages();
        MenuLanguages::fill($deal, 'name', (array) $data['name'], $languages);

        if (array_key_exists('ingredients', $data)) {
            // Sent empty, its ingredients are cleared in every language shown.
            MenuLanguages::fill($deal, 'ingredients', is_array($data['ingredients']) ? $data['ingredients'] : array_fill_keys($languages, ''), $languages);
        }

        $deal->save();

        return [$deal, $created];
    }

    /**
     * The deal dish's photo, once the offer's own picture is saved: an
     * upload of its own when sent; otherwise, with `use_offer_image` (on by
     * default for a dish made now), a copy of the offer's picture, taken
     * again only when that picture changed in this save or the dish has no
     * photo. An offer without a picture gives none (and removing the
     * offer's picture removes the copy).
     *
     * @param  array<string, mixed>  $data  the request's `deal`, validated
     */
    public function syncPhoto(Dish $deal, Offer $offer, array $data, bool $created, bool $offerPictureChanged): void
    {
        if (filled($data['image_key'] ?? null)) {
            $this->media->sync($deal, (string) $data['image_key'], false, 'image', 'dish');

            return;
        }

        $useOfferImage = isset($data['use_offer_image']) ? (bool) $data['use_offer_image'] : $created;

        if (! $useOfferImage || (! $created && ! $offerPictureChanged && $deal->getFirstMedia('image') !== null)) {
            return;
        }

        $picture = $offer->getFirstMedia('image');

        if ($picture === null) {
            if ($offerPictureChanged) {
                $deal->clearMediaCollection('image');
            }

            return;
        }

        $this->media->copy($picture, $deal, 'image', 'dish');
    }

    /**
     * The offer's deal dish deleted through the model, so its photo goes
     * too; its id, or null when it had none.
     */
    public function drop(Offer $offer): ?int
    {
        $deal = $offer->deal()->first();
        $deal?->delete();

        return $deal?->id;
    }
}
