<?php

namespace App\Http\Requests;

use App\Models\Offer;

/**
 * An offer edited in the dashboard: the editor sends it whole, so the rules
 * are the new offer's (StoreOfferRequest), except that the picture it
 * already has counts unless it is being removed, and so does the deal dish
 * it has while the request leaves `deal` out.
 */
class UpdateOfferRequest extends StoreOfferRequest
{
    private ?bool $keepsDeal = null;

    protected function offerId(): ?int
    {
        $offer = $this->route('offer');

        return $offer instanceof Offer ? $offer->id : null;
    }

    protected function hasDeal(): bool
    {
        if (parent::hasDeal()) {
            return true;
        }

        $offer = $this->route('offer');

        return $this->keepsDeal ??= $offer instanceof Offer && ! $this->exists('deal') && $offer->deal()->exists();
    }

    protected function hasPicture(): bool
    {
        if (parent::hasPicture()) {
            return true;
        }

        $offer = $this->route('offer');

        return $offer instanceof Offer && ! $this->boolean('delete_image') && $offer->getFirstMedia('image') !== null;
    }
}
