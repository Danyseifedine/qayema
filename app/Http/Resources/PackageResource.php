<?php

namespace App\Http\Resources;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Package
 */
class PackageResource extends JsonResource
{
    /**
     * One plan as the dashboard's package page draws it. Limits come through
     * as numbers or null for unlimited; flags come through as booleans, so the
     * SPA never has to know that a flag is stored as 0 or 1.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => [
                'en' => $this->getTranslation('name', 'en', false) ?: null,
                'ar' => $this->getTranslation('name', 'ar', false) ?: null,
            ],
            'description' => [
                'en' => $this->getTranslation('description', 'en', false) ?: null,
                'ar' => $this->getTranslation('description', 'ar', false) ?: null,
            ],
            // The card's own lines, written by the admin; empty lists mean the
            // card lists what the package adds from its features.
            'highlights' => [
                'en' => $this->resource->writtenHighlights('en'),
                'ar' => $this->resource->writtenHighlights('ar'),
            ],
            // Null means the price is not published; the owner has to ask.
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'is_contact_only' => (bool) $this->is_contact_only,
            'is_default' => (bool) $this->is_default,
            // Marked "Most popular" on the dashboard.
            'is_featured' => (bool) $this->is_featured,
            'features' => $this->features(),
        ];
    }

    /**
     * @return array<string, int|bool|null>
     */
    private function features(): array
    {
        $features = [];

        foreach (Feature::cases() as $feature) {
            // As owners are shown it: a fair-use limit reads as unlimited.
            $value = $this->shownValue($feature);

            $features[$feature->value] = $feature->kind() === FeatureKind::Flag
                ? ($value === null || $value > 0)
                : $value;
        }

        return $features;
    }
}
