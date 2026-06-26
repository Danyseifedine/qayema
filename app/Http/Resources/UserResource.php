<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Intentionally explicit — only the fields the dashboard SPA needs are
     * exposed, never the full model (which could leak internal columns).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'has_completed_onboarding' => $this->hasCompletedOnboarding(),
            'restaurant' => $this->whenLoaded('restaurant', fn () => $this->restaurant
                ? ['id' => $this->restaurant->id, 'slug' => $this->restaurant->slug]
                : null),
        ];
    }
}
