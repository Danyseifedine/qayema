<?php

namespace App\Http\Resources;

use App\Services\Global\MenuLanguages;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the category for the dashboard SPA. Translatable fields come
     * as one entry per menu language — English, then the second one — null
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
            'display_order' => $this->display_order,
            'dishes_count' => $this->whenCounted('dishes'),
        ];
    }
}
