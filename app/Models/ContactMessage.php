<?php

namespace App\Models;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isPackageRequest(): bool
    {
        return $this->package_id !== null;
    }
}
