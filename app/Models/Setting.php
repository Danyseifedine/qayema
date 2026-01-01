<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    protected $fillable = [
        'title',
        'key',
        'description',
        'type',
    ];

    /**
     * Get all menu settings that use this setting.
     */
    public function menuSettings(): HasMany
    {
        return $this->hasMany(MenuSetting::class);
    }
}
