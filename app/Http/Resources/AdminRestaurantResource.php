<?php

namespace App\Http\Resources;

use App\Enums\PackageStatus;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\MediaUrl;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A restaurant as the admin phone app shows it: its menu link, whether it
 * is on, its owner, and where its package stands.
 *
 * @mixin \App\Models\Restaurant
 */
class AdminRestaurantResource extends JsonResource
{
    /** One restaurant's page also shows its visits and QR scans. */
    private bool $withStats = false;

    /**
     * A restaurant's own page: what the list shows, plus its counts, its
     * phone and its visits. Every answer about one restaurant is this, so
     * the page never loses part of itself after a change.
     */
    public static function detail(Restaurant $restaurant): self
    {
        $resource = new self($restaurant->load(['user', 'package', 'media'])->loadCount(['dishes', 'categories']));
        $resource->withStats = true;

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->packageStatus();
        $inForce = $this->effectivePackage();

        return [
            'id' => $this->id,
            'name' => MenuLanguages::text($this->resource, 'name', MenuLanguages::MAIN),
            'slug' => $this->slug,
            'is_active' => (bool) $this->is_active,
            'public_url' => rtrim((string) config('app.url'), '/').'/'.$this->slug,
            'logo_url' => MediaUrl::of($this->resource, 'logo'),
            'created_at' => $this->created_at?->toIso8601String(),
            'owner' => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'email' => $this->user->email,
            ],
            'package' => [
                // What the admin assigned, and where it stands between its
                // dates; `in_force` is what the limits come from now (the
                // default package once it ended or before it starts).
                'id' => $this->package_id,
                'name' => $this->package === null ? null : (string) $this->package->name,
                'status' => $status->value,
                'starts_at' => $this->package_started_at?->toIso8601String(),
                'ends_at' => $this->package_ends_at?->toIso8601String(),
                // Whole days left, rounded up ("ends today" is 0), like the
                // dashboard's; null when it runs forever or is not in force.
                'days_left' => $status === PackageStatus::Active && $this->package_ends_at !== null
                    ? (int) max(0, ceil(now()->diffInSeconds($this->package_ends_at) / 86400))
                    : null,
                'in_force' => $inForce === null ? null : ['id' => $inForce->id, 'name' => (string) $inForce->name],
            ],
            'phone' => $this->when($this->withStats, fn (): ?array => $this->phone === null ? null : [
                'number' => $this->phone,
                'country_code' => $this->country_code,
                'dial' => config("countries.{$this->country_code}.dial"),
            ]),
            'stats' => $this->when($this->withStats, fn (): array => $this->stats()),
            'dishes_count' => $this->whenCounted('dishes', fn (): int => (int) $this->dishes_count),
            'categories_count' => $this->whenCounted('categories', fn (): int => (int) $this->categories_count),
        ];
    }

    /**
     * Visits (today in the restaurant's own day, and ever) and QR scans:
     * the two counts the admin's user page reads, two queries.
     *
     * @return array<string, mixed>
     */
    private function stats(): array
    {
        $traffic = $this->trafficTotals();

        return [
            'views_today' => $traffic['today'],
            'views_total' => $traffic['views'],
            'visitors_total' => $traffic['visitors'],
            'last_visit_at' => $traffic['last'] === null ? null : CarbonImmutable::parse($traffic['last'], 'UTC')->toIso8601String(),
            'qr_scans' => $this->qrScans(),
        ];
    }
}
