<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A restaurant linked to one POS branch (Connect to POS). Qayema calls the
 * POS at `address` with `key`; the POS calls back signed with
 * `signing_secret` and names the link by `pos_uuid`. Both secrets are kept
 * encrypted and never leave the server. While a link exists the POS owns
 * the menu's categories and dishes (Restaurant::isConnectedToPos()).
 */
class PosConnection extends Model
{
    /** @use HasFactory<\Database\Factories\PosConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'address',
        'key',
        'signing_secret',
        'pos_uuid',
        'company',
        'branch',
        'currency',
        'locales',
        'sends_orders',
    ];

    protected $hidden = ['key', 'signing_secret'];

    protected $attributes = [
        'sends_orders' => false,
        'last_synced_at' => null,
        'last_attempt_at' => null,
        'last_error' => null,
        'problems' => null,
        'alerted_at' => null,
    ];

    /** How long the POS may go unanswered before the owner and the admins are told. */
    public const UNREACHABLE_AFTER_MINUTES = 60;

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'key' => 'encrypted',
            'signing_secret' => 'encrypted',
            'locales' => 'array',
            'sends_orders' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'problems' => 'array',
            'alerted_at' => 'datetime',
        ];
    }

    /**
     * Since when the POS has not answered: the last pull that worked (or
     * the link), while the latest one failed and that was over an hour ago.
     * Null while it answers.
     */
    public function unreachableSince(): ?\Carbon\CarbonInterface
    {
        $since = $this->last_synced_at ?? $this->created_at;

        return $this->last_error !== null && $since !== null && $since->lt(now()->subMinutes(self::UNREACHABLE_AFTER_MINUTES)) ? $since : null;
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
