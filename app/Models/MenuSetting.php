<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuSetting extends Model
{
    protected $fillable = [
        'menu_id',
        'setting_id',
        'value',
    ];

    /**
     * Get the menu that owns the settings.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the setting definition.
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    /**
     * Cast the value based on the setting type.
     */
    public function getValueAttribute($value): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $this->setting?->type ?? 'string';

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set the value attribute, converting it to string for storage.
     */
    public function setValueAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['value'] = null;

            return;
        }

        // Get type from setting relationship
        $type = $this->setting?->type ?? $this->attributes['type'] ?? 'string';

        $this->attributes['value'] = match ($type) {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
