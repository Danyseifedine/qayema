<?php

namespace App\Models;

use App\Models\Concerns\ClearsMenuCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantSocialLink extends Model
{
    use ClearsMenuCache, HasFactory;

    /**
     * The platforms the menu can render an icon for, with the name it shows.
     * Mirrored by the dashboard's `SOCIAL_PLATFORMS`; each needs an icon in
     * `MenuIcons`, and a colour in the menu's stylesheet unless its mark keeps
     * the text colour (X, TikTok, Threads, Snapchat).
     */
    public const LABELS = [
        'instagram' => 'Instagram',
        'x' => 'X',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'snapchat' => 'Snapchat',
        'threads' => 'Threads',
        'telegram' => 'Telegram',
        'pinterest' => 'Pinterest',
        'tripadvisor' => 'Tripadvisor',
    ];

    /** @return list<string> */
    public static function platforms(): array
    {
        return array_keys(self::LABELS);
    }

    protected $fillable = [
        'restaurant_id',
        'platform',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
