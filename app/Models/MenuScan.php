<?php

namespace App\Models;

use App\Enums\MenuScanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuScan extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'restaurant_id',
        'status',
        'image_path',
        'result',
        'error',
    ];

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
