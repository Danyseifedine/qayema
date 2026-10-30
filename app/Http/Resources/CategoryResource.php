<?php

namespace App\Http\Resources;

use App\Services\Menu\MenuLanguages;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the category for the dashboard SPA. Translatable fields come
     * as one entry per menu language (English, then the second one), null
     * when unset, so the client never has to probe which keys exist.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => MenuLanguages::map($this->resource, 'name', MenuLanguages::forOwner($request->user())),
            'description' => MenuLanguages::map($this->resource, 'description', MenuLanguages::forOwner($request->user())),
            'icon' => $this->icon,
            'dishes_count' => $this->whenCounted('dishes', fn (int|string $count): int => (int) $count),
            // Connect to POS: made by the POS (its name and description are
            // the POS's while connected), and gone from it since (hidden).
            'from_pos' => $this->pos_id !== null,
            'pos_removed' => $this->pos_removed_at !== null,
        ];
    }
}
