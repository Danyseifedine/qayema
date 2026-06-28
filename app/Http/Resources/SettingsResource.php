<?php

namespace App\Http\Resources;

use App\Models\Restaurant;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Restaurant
 */
class SettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // The display name is editable; the slug is read-only. `default_locale`
            // tells the SPA which name translation the owner actually manages.
            'name' => [
                'en' => $this->getTranslation('name', 'en', false),
                'ar' => $this->getTranslation('name', 'ar', false),
            ],
            'default_locale' => $this->default_locale ?: 'ar',
            'slug' => $this->slug,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'currency' => $this->currency,
            'logo_url' => $this->getFirstMediaUrl('logo') ?: null,
            'cover_url' => $this->getFirstMediaUrl('cover_image') ?: null,
            // Only the owner-selectable (cuisine/dietary) tags — matching the
            // picker and the update rules, so the SPA can safely resubmit them.
            // (Onboarding also attaches vibe/style tags the owner can't edit.)
            'tag_ids' => $this->whenLoaded(
                'tags',
                fn () => $this->tags->whereIn('category', Tag::OWNER_CATEGORIES)->pluck('id')->values()->all(),
                [],
            ),
        ];
    }
}
