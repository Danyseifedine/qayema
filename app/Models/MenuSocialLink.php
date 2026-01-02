<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuSocialLink extends Model
{
    protected $fillable = [
        'menu_id',
        'platform',
        'url',
    ];

    /**
     * Get the menu that owns the social link.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
