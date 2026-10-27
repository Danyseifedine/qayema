<?php

namespace App\Models;

use App\Enums\OfferDiscount;
use App\Enums\OfferPlacement;
use App\Enums\OfferRepeat;
use App\Models\Concerns\ClearsMenuCache;
use App\Services\Offers\OfferSchedule;
use App\Support\Price;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * An owner's offer on the guest menu (the `offers` flag, Premium): a title,
 * a line of text and a picture, shown in the places it names
 * (OfferPlacement), when its schedule says (OfferSchedule), with an
 * optional discount on the dishes it names. It may make a dish of its own
 * (its deal, deal()), on the menu only while it is live.
 */
class Offer extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use ClearsMenuCache, HasFactory, HasTranslations, InteractsWithMedia;

    /** Kept small enough for the menu page to stay light. */
    public const MAX_PER_RESTAURANT = 30;

    public const MAX_DISHES = 50;

    /** How often one guest sees a pop-up or an ad. */
    public const SHOW_AGAIN = ['every_visit', 'daily', 'once'];

    /** What a tap on the offer does. */
    public const ACTIONS = ['none', 'dishes', 'link'];

    /** A banner's shape: 2:1 or 1:1. */
    public const BANNER_SHAPES = ['rectangle', 'square'];

    /** A pop-up's shape: 1:1 or 3:4. An ad is always a rectangle. */
    public const POPUP_SHAPES = ['square', 'tall'];

    /** Each shape's width over its height (menu-classic.css draws them). */
    public const SHAPE_RATIOS = ['rectangle' => 2.0, 'square' => 1.0, 'tall' => 0.75];

    /**
     * How far a picture's proportions may be from its shape's and still be
     * cropped to fill it (a factor of 1 or more). Further off, it shows whole
     * on a blurred copy of itself. Words over it hide a little cropping; a
     * picture alone carries its own words, so it is barely cropped at all.
     * Twinned by fitPicture() in menu-offers.js and the dashboard's
     * picture-fit.ts.
     */
    public const CROPS_UP_TO = 1.35;

    public const PICTURE_ALONE_CROPS_UP_TO = 1.05;

    /** @var string[] */
    public array $translatable = ['title', 'description'];

    /** @var array<int, OfferPlacement>|null the places the package leaves it on the menu; null, all it names */
    private ?array $menuPlaces = null;

    /** Its discount is set aside: the package has no discounts. */
    private bool $discountOff = false;

    protected $fillable = [
        'restaurant_id',
        'title',
        'description',
        'show_text',
        'placements',
        'banner_shape',
        'popup_shape',
        'action',
        'action_url',
        'show_again',
        'delay_seconds',
        'ad_delay_seconds',
        'ad_seconds',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'repeat',
        'weekdays',
        'month_days',
        'time_windows',
        'is_active',
        'display_order',
    ];

    /** What a new row reads before it is fetched again (strict models). */
    protected $attributes = [
        'show_text' => true,
        'banner_shape' => 'rectangle',
        'popup_shape' => 'square',
        'action' => 'none',
        'show_again' => 'every_visit',
        'delay_seconds' => 0,
        'ad_delay_seconds' => 2,
        'ad_seconds' => 6,
        'discount_type' => 'none',
        'repeat' => 'always',
        'is_active' => true,
        'display_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'show_text' => 'boolean',
            'placements' => 'array',
            'delay_seconds' => 'integer',
            'ad_delay_seconds' => 'integer',
            'ad_seconds' => 'integer',
            'discount_type' => OfferDiscount::class,
            'discount_value' => 'decimal:2',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'repeat' => OfferRepeat::class,
            'weekdays' => 'array',
            'month_days' => 'array',
            'time_windows' => 'array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Its deal dish goes with it, through the model, so the dish's photo
     * leaves storage too (the column's nullOnDelete only guards a row
     * removed some other way).
     */
    protected static function booted(): void
    {
        static::deleting(function (Offer $offer): void {
            $offer->deal()->first()?->delete();
        });
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** @return BelongsToMany<Dish, $this> */
    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class);
    }

    /**
     * The dish this offer made for itself ("Family meal: 2 pizzas + drinks"),
     * one at most: also its only linked dish, so a tap on the offer opens it.
     *
     * @return HasOne<Dish, $this>
     */
    public function deal(): HasOne
    {
        return $this->hasOne(Dish::class);
    }

    /** @return array<int, OfferPlacement> */
    public function placementList(): array
    {
        $named = array_values(array_filter(array_map(
            fn (mixed $value): ?OfferPlacement => is_string($value) ? OfferPlacement::tryFrom($value) : null,
            (array) $this->placements,
        )));

        return $this->menuPlaces === null
            ? $named
            : array_values(array_filter($named, fn (OfferPlacement $placement): bool => in_array($placement, $this->menuPlaces, true)));
    }

    /**
     * Shown only in these of its places, the ones the package has
     * (OfferRules::applyTo()); never saved.
     *
     * @param  array<int, OfferPlacement>  $places
     */
    public function showOnlyIn(array $places): void
    {
        $this->menuPlaces = $places;
    }

    /** Shown without its discount, which the package lacks (OfferRules::applyTo()); never saved. */
    public function withoutDiscount(): void
    {
        $this->discountOff = true;
    }

    /**
     * It runs on a schedule rather than always: dates, a repeat on some
     * days, or hours. Twinned by StoreOfferRequest::sendsSchedule() and the
     * dashboard's hasSchedule().
     */
    public function hasSchedule(): bool
    {
        return $this->starts_at !== null
            || $this->ends_at !== null
            || $this->repeat !== OfferRepeat::Always
            || ! empty($this->time_windows);
    }

    public function showsIn(OfferPlacement $placement): bool
    {
        return in_array($placement, $this->placementList(), true);
    }

    /**
     * A picture alone: its words are drawn into it, so the menu shows the
     * picture whole and the picture is what a guest taps.
     */
    public function isPictureOnly(): bool
    {
        return ! $this->show_text;
    }

    /**
     * The shape it takes in a place: the owner's choice for a banner or a
     * pop-up, always a rectangle for an ad.
     *
     * @return 'rectangle'|'square'|'tall'
     */
    public function shapeIn(OfferPlacement $placement): string
    {
        $shape = match ($placement) {
            OfferPlacement::Banner => $this->banner_shape,
            OfferPlacement::Popup => $this->popup_shape,
            OfferPlacement::Ad => 'rectangle',
        };

        return in_array($shape, ['rectangle', 'square', 'tall'], true) ? $shape : 'rectangle';
    }

    public function hasDiscount(): bool
    {
        return ! $this->discountOff && $this->discount_type !== OfferDiscount::None && (float) $this->discount_value > 0;
    }

    /** Its picture's width over height, from the size kept at upload; null when unknown. */
    public function pictureRatio(): ?float
    {
        $picture = $this->getFirstMedia('image');
        $width = (int) $picture?->getCustomProperty('width');
        $height = (int) $picture?->getCustomProperty('height');

        return $width > 0 && $height > 0 ? $width / $height : null;
    }

    /**
     * Its picture shows whole in this shape (on a blurred copy of itself)
     * rather than cropped to fill it; null when its size is unknown, for the
     * guest's phone to judge once it has loaded.
     */
    public function showsPictureWhole(string $shape): ?bool
    {
        $ratio = $this->pictureRatio();

        if ($ratio === null) {
            return null;
        }

        $box = self::SHAPE_RATIOS[$shape] ?? 2.0;
        $apart = max($ratio / $box, $box / $ratio);

        return $apart > ($this->isPictureOnly() ? self::PICTURE_ALONE_CROPS_UP_TO : self::CROPS_UP_TO);
    }

    /**
     * A dish price (as stored, "12.50") after this offer's discount, as a
     * price again. An amount off is in the main currency: for a dish priced
     * in the second one (`$rate`, Restaurant::rateOf()) it takes off that
     * amount in the second currency (menu-offers.js does the same).
     */
    public function discounted(string $price, ?string $rate = null): string
    {
        $cents = (int) round((float) $price * 100);
        $value = $rate !== null && $this->discount_type === OfferDiscount::Amount
            ? Price::fromMain((string) $this->discount_value, $rate)
            : (string) $this->discount_value;

        return number_format($this->discount_type->apply($cents, (float) $value) / 100, 2, '.', '');
    }

    /** Its schedule in the restaurant's timezone, which the caller passes so no query is needed. */
    public function schedule(string $timezone): OfferSchedule
    {
        return new OfferSchedule(
            timezone: $timezone,
            startsAt: $this->starts_at,
            endsAt: $this->ends_at,
            repeat: $this->repeat,
            weekdays: array_values(array_map('strval', (array) $this->weekdays)),
            monthDays: array_values(array_map('intval', (array) $this->month_days)),
            windows: $this->time_windows === null ? null : array_values($this->time_windows),
        );
    }

    /** Switched on and live by its schedule at `$at`. */
    public function isLiveAt(CarbonImmutable $at, string $timezone): bool
    {
        return $this->is_active && $this->schedule($timezone)->isLiveAt($at);
    }

    /** live, scheduled (it will be), ended (it never will be again) or paused (switched off). */
    public function statusAt(CarbonImmutable $at, string $timezone): string
    {
        $schedule = $this->schedule($timezone);

        return match (true) {
            ! $this->is_active => 'paused',
            $schedule->isLiveAt($at) => 'live',
            $schedule->nextStart($at) !== null => 'scheduled',
            default => 'ended',
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * A small one for the dashboard's list and the blurred backdrop a card
     * shows while the picture loads, and two sizes for the menu's cards
     * (the phone picks one by its screen, `srcset`); all made with the
     * upload, like a dish's.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Queue settings first: the image calls after them are typed as the
        // image driver's, which has no nonQueued().
        $this->addMediaConversion('thumb')
            ->performOnCollections('image')
            ->nonQueued()
            ->fit(Fit::Max, 240, 240)
            ->format('webp')
            ->quality(76);

        $this->addMediaConversion('wide')
            ->performOnCollections('image')
            ->nonQueued()
            ->fit(Fit::Max, 960, 960)
            ->format('webp')
            ->quality(78);

        $this->addMediaConversion('large')
            ->performOnCollections('image')
            ->nonQueued()
            ->fit(Fit::Max, 1800, 1800)
            ->format('webp')
            ->quality(80);
    }
}
