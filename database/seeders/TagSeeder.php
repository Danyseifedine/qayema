<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Cuisine (20)
            ['name' => 'Lebanese',       'slug' => 'lebanese',       'category' => 'cuisine'],
            ['name' => 'Italian',        'slug' => 'italian',        'category' => 'cuisine'],
            ['name' => 'Mediterranean',  'slug' => 'mediterranean',  'category' => 'cuisine'],
            ['name' => 'Asian',          'slug' => 'asian',          'category' => 'cuisine'],
            ['name' => 'Fast Food',      'slug' => 'fast-food',      'category' => 'cuisine'],
            ['name' => 'Sushi',          'slug' => 'sushi',          'category' => 'cuisine'],
            ['name' => 'Pizza',          'slug' => 'pizza',          'category' => 'cuisine'],
            ['name' => 'Burgers',        'slug' => 'burgers',        'category' => 'cuisine'],
            ['name' => 'Seafood',        'slug' => 'seafood',        'category' => 'cuisine'],
            ['name' => 'Mexican',        'slug' => 'mexican',        'category' => 'cuisine'],
            ['name' => 'Indian',         'slug' => 'indian',         'category' => 'cuisine'],
            ['name' => 'Turkish',        'slug' => 'turkish',        'category' => 'cuisine'],
            ['name' => 'Greek',          'slug' => 'greek',          'category' => 'cuisine'],
            ['name' => 'Chinese',        'slug' => 'chinese',        'category' => 'cuisine'],
            ['name' => 'Japanese',       'slug' => 'japanese',       'category' => 'cuisine'],
            ['name' => 'Korean',         'slug' => 'korean',         'category' => 'cuisine'],
            ['name' => 'French',         'slug' => 'french',         'category' => 'cuisine'],
            ['name' => 'American',       'slug' => 'american',       'category' => 'cuisine'],
            ['name' => 'BBQ',            'slug' => 'bbq',            'category' => 'cuisine'],
            ['name' => 'Thai',           'slug' => 'thai',           'category' => 'cuisine'],
            ['name' => 'Vietnamese',     'slug' => 'vietnamese',     'category' => 'cuisine'],
            ['name' => 'Moroccan',       'slug' => 'moroccan',       'category' => 'cuisine'],
            ['name' => 'Spanish',        'slug' => 'spanish',        'category' => 'cuisine'],
            ['name' => 'Fusion',         'slug' => 'fusion',         'category' => 'cuisine'],
            ['name' => 'Breakfast',      'slug' => 'breakfast',      'category' => 'cuisine'],
            ['name' => 'Sandwiches',     'slug' => 'sandwiches',     'category' => 'cuisine'],
            ['name' => 'Shawarma',       'slug' => 'shawarma',       'category' => 'cuisine'],
            ['name' => 'Desserts',       'slug' => 'desserts',       'category' => 'cuisine'],
            ['name' => 'Wraps',          'slug' => 'wraps',          'category' => 'cuisine'],
            ['name' => 'Pasta',          'slug' => 'pasta',          'category' => 'cuisine'],

            // Dietary (20)
            ['name' => 'Halal',          'slug' => 'halal',          'category' => 'dietary'],
            ['name' => 'Vegan',          'slug' => 'vegan',          'category' => 'dietary'],
            ['name' => 'Vegetarian',     'slug' => 'vegetarian',     'category' => 'dietary'],
            ['name' => 'Gluten-Free',    'slug' => 'gluten-free',    'category' => 'dietary'],
            ['name' => 'Kosher',         'slug' => 'kosher',         'category' => 'dietary'],
            ['name' => 'Dairy-Free',     'slug' => 'dairy-free',     'category' => 'dietary'],
            ['name' => 'Nut-Free',       'slug' => 'nut-free',       'category' => 'dietary'],
            ['name' => 'Organic',        'slug' => 'organic',        'category' => 'dietary'],
            ['name' => 'Low-Carb',       'slug' => 'low-carb',       'category' => 'dietary'],
            ['name' => 'Keto',           'slug' => 'keto',           'category' => 'dietary'],
            ['name' => 'Paleo',          'slug' => 'paleo',          'category' => 'dietary'],
            ['name' => 'Plant-Based',    'slug' => 'plant-based',    'category' => 'dietary'],
            ['name' => 'Sugar-Free',     'slug' => 'sugar-free',     'category' => 'dietary'],
            ['name' => 'Pescatarian',    'slug' => 'pescatarian',    'category' => 'dietary'],
            ['name' => 'Egg-Free',       'slug' => 'egg-free',       'category' => 'dietary'],
            ['name' => 'Soy-Free',       'slug' => 'soy-free',       'category' => 'dietary'],
            ['name' => 'Lactose-Free',   'slug' => 'lactose-free',   'category' => 'dietary'],
            ['name' => 'Raw',            'slug' => 'raw',            'category' => 'dietary'],
            ['name' => 'High-Protein',   'slug' => 'high-protein',   'category' => 'dietary'],
            ['name' => 'Whole30',        'slug' => 'whole30',        'category' => 'dietary'],

            // Vibe (20)
            ['name' => 'Fine Dining',    'slug' => 'fine-dining',    'category' => 'vibe'],
            ['name' => 'Casual',         'slug' => 'casual',         'category' => 'vibe'],
            ['name' => 'Street Food',    'slug' => 'street-food',    'category' => 'vibe'],
            ['name' => 'Family',         'slug' => 'family',         'category' => 'vibe'],
            ['name' => 'Takeaway',       'slug' => 'takeaway',       'category' => 'vibe'],
            ['name' => 'Brunch',         'slug' => 'brunch',         'category' => 'vibe'],
            ['name' => 'Romantic',       'slug' => 'romantic',       'category' => 'vibe'],
            ['name' => 'Rooftop',        'slug' => 'rooftop',        'category' => 'vibe'],
            ['name' => 'Beachside',      'slug' => 'beachside',      'category' => 'vibe'],
            ['name' => 'Sports Bar',     'slug' => 'sports-bar',     'category' => 'vibe'],
            ['name' => 'Late Night',     'slug' => 'late-night',     'category' => 'vibe'],
            ['name' => 'Fast Service',   'slug' => 'fast-service',   'category' => 'vibe'],
            ['name' => 'Cozy',           'slug' => 'cozy',           'category' => 'vibe'],
            ['name' => 'Trendy',         'slug' => 'trendy',         'category' => 'vibe'],
            ['name' => 'Artisan',        'slug' => 'artisan',        'category' => 'vibe'],
            ['name' => 'Lounge',         'slug' => 'lounge',         'category' => 'vibe'],
            ['name' => 'Bistro',         'slug' => 'bistro',         'category' => 'vibe'],
            ['name' => 'Buffet',         'slug' => 'buffet',         'category' => 'vibe'],
            ['name' => 'Delivery Only',  'slug' => 'delivery-only',  'category' => 'vibe'],
            ['name' => 'Pop-Up',         'slug' => 'pop-up',         'category' => 'vibe'],

            // Style (20)
            ['name' => 'Dark',           'slug' => 'dark',           'category' => 'style'],
            ['name' => 'Light',          'slug' => 'light',          'category' => 'style'],
            ['name' => 'Minimal',        'slug' => 'minimal',        'category' => 'style'],
            ['name' => 'Modern',         'slug' => 'modern',         'category' => 'style'],
            ['name' => 'Bold',           'slug' => 'bold',           'category' => 'style'],
            ['name' => 'Elegant',        'slug' => 'elegant',        'category' => 'style'],
            ['name' => 'Classic',        'slug' => 'classic',        'category' => 'style'],
            ['name' => 'Rustic',         'slug' => 'rustic',         'category' => 'style'],
            ['name' => 'Colorful',       'slug' => 'colorful',       'category' => 'style'],
            ['name' => 'Playful',        'slug' => 'playful',        'category' => 'style'],
            ['name' => 'Vintage',        'slug' => 'vintage',        'category' => 'style'],
            ['name' => 'Industrial',     'slug' => 'industrial',     'category' => 'style'],
            ['name' => 'Traditional',    'slug' => 'traditional',    'category' => 'style'],
            ['name' => 'Contemporary',   'slug' => 'contemporary',   'category' => 'style'],
            ['name' => 'Luxurious',      'slug' => 'luxurious',      'category' => 'style'],
            ['name' => 'Warm',           'slug' => 'warm',           'category' => 'style'],
            ['name' => 'Vibrant',        'slug' => 'vibrant',        'category' => 'style'],
            ['name' => 'Chic',           'slug' => 'chic',           'category' => 'style'],
            ['name' => 'Retro',          'slug' => 'retro',          'category' => 'style'],
            ['name' => 'Artistic',       'slug' => 'artistic',       'category' => 'style'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }
    }
}
