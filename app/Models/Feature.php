<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Feature extends Model
{
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'kind',
        'is_addon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_addon' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(FeaturePrice::class);
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'template_feature')->withPivot('value');
    }

    public function isLimit(): bool
    {
        return $this->kind === 'limit';
    }
}
