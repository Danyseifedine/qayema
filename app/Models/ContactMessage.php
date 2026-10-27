<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message from the public contact form, or an owner asking for a package
 * from the dashboard. The two share a table because they share a destination:
 * the admin inbox. A row carrying a `package_id` is a package request.
 */
class ContactMessage extends Model
{
    /** @use HasFactory<\Database\Factories\ContactMessageFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'message', 'ip_address', 'user_id', 'package_id'];

    /** A message just sent is open, and reads so before it is fetched again. */
    protected $attributes = ['handled_at' => null];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'package_id' => 'integer',
            'handled_at' => 'datetime',
        ];
    }

    /**
     * Messages no admin has dealt with yet.
     *
     * @param  Builder<ContactMessage>  $query
     * @return Builder<ContactMessage>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('handled_at');
    }

    public function isHandled(): bool
    {
        return $this->handled_at !== null;
    }

    /** Dealt with now; once only, so the first date stays. */
    public function markHandled(): void
    {
        if ($this->handled_at === null) {
            $this->forceFill(['handled_at' => now()])->save();
        }
    }

    /** Back in the open list. */
    public function reopen(): void
    {
        $this->forceFill(['handled_at' => null])->save();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isPackageRequest(): bool
    {
        return $this->package_id !== null;
    }
}
