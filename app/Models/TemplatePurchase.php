<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A template unlocked with coins. Ownership is permanent, so switching between
 * owned templates is always free.
 */
class TemplatePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'template_id',
        'coin_transaction_id',
        'price_paid',
    ];

    protected function casts(): array
    {
        return [
            'price_paid' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
