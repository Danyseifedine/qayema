<?php

namespace App\Http\Resources;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Template
 */
class TemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'tier' => $this->tier,
            'name' => [
                'en' => $this->getTranslation('name', 'en', false),
                'ar' => $this->getTranslation('name', 'ar', false),
            ],
            'description' => [
                'en' => $this->getTranslation('description', 'en', false),
                'ar' => $this->getTranslation('description', 'ar', false),
            ],
        ];
    }
}
