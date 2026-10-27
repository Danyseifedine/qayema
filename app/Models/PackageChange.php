<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a restaurant's package history. Written once, never edited.
 */
class PackageChange extends Model
{
    /** @use HasFactory<\Database\Factories\PackageChangeFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'restaurant_id',
        'from_package_id',
        'to_package_id',
        'starts_at',
        'ends_at',
        'price_cents',
        'changed_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'from_package_id' => 'integer',
            'to_package_id' => 'integer',
            'changed_by' => 'integer',
            'price_cents' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Package, $this> */
    public function fromPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'from_package_id');
    }

    /** @return BelongsTo<Package, $this> */
    public function toPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'to_package_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
