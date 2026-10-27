<?php

namespace App\Models;

use App\Enums\BuildShape;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A "build your own" on a dish (Your pizza, Your scoops): the guest picks
 * how many parts (one of `parts`) and a choice for each. Its choices are a
 * category's dishes when it names one (only those of `dish_ids` when it
 * lists some), else its own list.
 */
class DishBuild extends Model
{
    /** @use HasFactory<\Database\Factories\DishBuildFactory> */
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = [
        'dish_id',
        'name',
        'shape',
        'parts',
        'category_id',
        'dish_ids',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'dish_id' => 'integer',
            'category_id' => 'integer',
            'dish_ids' => 'array',
            'shape' => BuildShape::class,
            'parts' => 'array',
            'display_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Dish, $this> */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Its own choices, or, with a category, its dishes' part prices.
     *
     * @return HasMany<DishBuildChoice, $this>
     */
    public function choices(): HasMany
    {
        return $this->hasMany(DishBuildChoice::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * Whether the build offers this dish of its category: every one, or
     * only those ticked.
     */
    public function offersDish(int $dishId): bool
    {
        return $this->dish_ids === null || in_array($dishId, array_map('intval', $this->dish_ids), true);
    }

    /** @return list<int> the part counts the guest may pick, smallest first */
    public function partCounts(): array
    {
        $parts = array_values(array_unique(array_map('intval', (array) $this->parts)));
        sort($parts);

        return $parts;
    }
}
