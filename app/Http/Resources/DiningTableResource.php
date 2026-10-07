<?php

namespace App\Http\Resources;

use App\Models\DiningTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DiningTable
 */
class DiningTableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            // What the table's QR code encodes.
            'url' => $this->menuUrl(),
        ];
    }
}
