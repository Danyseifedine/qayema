<?php

namespace App\Models;

use App\Enums\MenuScanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MenuScan extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'restaurant_id',
        'status',
        'result',
        'error',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('scan')
            ->singleFile()
            ->useDisk('local')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    protected function casts(): array
    {
        return [
            'status' => MenuScanStatus::class,
            'result' => 'array',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function isPending(): bool
    {
        return $this->status === MenuScanStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === MenuScanStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === MenuScanStatus::Failed;
    }

    public function markProcessing(): void
    {
        $this->update(['status' => MenuScanStatus::Processing, 'error' => null]);
    }

    public function markCompleted(array $result): void
    {
        $this->update(['status' => MenuScanStatus::Completed, 'result' => $result]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => MenuScanStatus::Failed, 'error' => $error]);
    }
}
