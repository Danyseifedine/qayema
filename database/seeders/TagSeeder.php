<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Cuisine (30)
            ['name' => ['en' => 'Lebanese', 'ar' => 'لبناني'], 'slug' => 'lebanese', 'category' => 'cuisine'],
            ['name' => ['en' => 'Italian', 'ar' => 'إيطالي'], 'slug' => 'italian', 'category' => 'cuisine'],
            ['name' => ['en' => 'Mediterranean', 'ar' => 'متوسطي'], 'slug' => 'mediterranean', 'category' => 'cuisine'],
            ['name' => ['en' => 'Asian', 'ar' => 'آسيوي'], 'slug' => 'asian', 'category' => 'cuisine'],
            ['name' => ['en' => 'Fast Food', 'ar' => 'وجبات سريعة'], 'slug' => 'fast-food', 'category' => 'cuisine'],
            ['name' => ['en' => 'Sushi', 'ar' => 'سوشي'], 'slug' => 'sushi', 'category' => 'cuisine'],
            ['name' => ['en' => 'Pizza', 'ar' => 'بيتزا'], 'slug' => 'pizza', 'category' => 'cuisine'],
            ['name' => ['en' => 'Burgers', 'ar' => 'برغر'], 'slug' => 'burgers', 'category' => 'cuisine'],
            ['name' => ['en' => 'Seafood', 'ar' => 'مأكولات بحرية'], 'slug' => 'seafood', 'category' => 'cuisine'],
            ['name' => ['en' => 'Mexican', 'ar' => 'مكسيكي'], 'slug' => 'mexican', 'category' => 'cuisine'],
            ['name' => ['en' => 'Indian', 'ar' => 'هندي'], 'slug' => 'indian', 'category' => 'cuisine'],
            ['name' => ['en' => 'Turkish', 'ar' => 'تركي'], 'slug' => 'turkish', 'category' => 'cuisine'],
            ['name' => ['en' => 'Greek', 'ar' => 'يوناني'], 'slug' => 'greek', 'category' => 'cuisine'],
            ['name' => ['en' => 'Chinese', 'ar' => 'صيني'], 'slug' => 'chinese', 'category' => 'cuisine'],
            ['name' => ['en' => 'Japanese', 'ar' => 'ياباني'], 'slug' => 'japanese', 'category' => 'cuisine'],
            ['name' => ['en' => 'Korean', 'ar' => 'كوري'], 'slug' => 'korean', 'category' => 'cuisine'],
            ['name' => ['en' => 'French', 'ar' => 'فرنسي'], 'slug' => 'french', 'category' => 'cuisine'],
            ['name' => ['en' => 'American', 'ar' => 'أمريكي'], 'slug' => 'american', 'category' => 'cuisine'],
            ['name' => ['en' => 'BBQ', 'ar' => 'مشويات'], 'slug' => 'bbq', 'category' => 'cuisine'],
            ['name' => ['en' => 'Thai', 'ar' => 'تايلندي'], 'slug' => 'thai', 'category' => 'cuisine'],
            ['name' => ['en' => 'Vietnamese', 'ar' => 'فيتنامي'], 'slug' => 'vietnamese', 'category' => 'cuisine'],
            ['name' => ['en' => 'Moroccan', 'ar' => 'مغربي'], 'slug' => 'moroccan', 'category' => 'cuisine'],
            ['name' => ['en' => 'Spanish', 'ar' => 'إسباني'], 'slug' => 'spanish', 'category' => 'cuisine'],
            ['name' => ['en' => 'Fusion', 'ar' => 'فيوجن'], 'slug' => 'fusion', 'category' => 'cuisine'],
            ['name' => ['en' => 'Breakfast', 'ar' => 'فطور'], 'slug' => 'breakfast', 'category' => 'cuisine'],
            ['name' => ['en' => 'Sandwiches', 'ar' => 'ساندويتشات'], 'slug' => 'sandwiches', 'category' => 'cuisine'],
            ['name' => ['en' => 'Shawarma', 'ar' => 'شاورما'], 'slug' => 'shawarma', 'category' => 'cuisine'],
            ['name' => ['en' => 'Desserts', 'ar' => 'حلويات'], 'slug' => 'desserts', 'category' => 'cuisine'],
            ['name' => ['en' => 'Wraps', 'ar' => 'لفائف'], 'slug' => 'wraps', 'category' => 'cuisine'],
            ['name' => ['en' => 'Pasta', 'ar' => 'باستا'], 'slug' => 'pasta', 'category' => 'cuisine'],

            // Dietary (20)
            ['name' => ['en' => 'Halal', 'ar' => 'حلال'], 'slug' => 'halal', 'category' => 'dietary'],
            ['name' => ['en' => 'Vegan', 'ar' => 'نباتي صرف'], 'slug' => 'vegan', 'category' => 'dietary'],
            ['name' => ['en' => 'Vegetarian', 'ar' => 'نباتي'], 'slug' => 'vegetarian', 'category' => 'dietary'],
            ['name' => ['en' => 'Gluten-Free', 'ar' => 'خالٍ من الغلوتين'], 'slug' => 'gluten-free', 'category' => 'dietary'],
            ['name' => ['en' => 'Kosher', 'ar' => 'كوشير'], 'slug' => 'kosher', 'category' => 'dietary'],
            ['name' => ['en' => 'Dairy-Free', 'ar' => 'خالٍ من الألبان'], 'slug' => 'dairy-free', 'category' => 'dietary'],
            ['name' => ['en' => 'Nut-Free', 'ar' => 'خالٍ من المكسرات'], 'slug' => 'nut-free', 'category' => 'dietary'],
            ['name' => ['en' => 'Organic', 'ar' => 'عضوي'], 'slug' => 'organic', 'category' => 'dietary'],
            ['name' => ['en' => 'Low-Carb', 'ar' => 'قليل الكربوهيدرات'], 'slug' => 'low-carb', 'category' => 'dietary'],
            ['name' => ['en' => 'Keto', 'ar' => 'كيتو'], 'slug' => 'keto', 'category' => 'dietary'],
            ['name' => ['en' => 'Paleo', 'ar' => 'باليو'], 'slug' => 'paleo', 'category' => 'dietary'],
            ['name' => ['en' => 'Plant-Based', 'ar' => 'نباتي المصدر'], 'slug' => 'plant-based', 'category' => 'dietary'],
            ['name' => ['en' => 'Sugar-Free', 'ar' => 'خالٍ من السكر'], 'slug' => 'sugar-free', 'category' => 'dietary'],
            ['name' => ['en' => 'Pescatarian', 'ar' => 'نباتي وسمكي'], 'slug' => 'pescatarian', 'category' => 'dietary'],
            ['name' => ['en' => 'Egg-Free', 'ar' => 'خالٍ من البيض'], 'slug' => 'egg-free', 'category' => 'dietary'],
            ['name' => ['en' => 'Soy-Free', 'ar' => 'خالٍ من الصويا'], 'slug' => 'soy-free', 'category' => 'dietary'],
            ['name' => ['en' => 'Lactose-Free', 'ar' => 'خالٍ من اللاكتوز'], 'slug' => 'lactose-free', 'category' => 'dietary'],
            ['name' => ['en' => 'Raw', 'ar' => 'نيء'], 'slug' => 'raw', 'category' => 'dietary'],
            ['name' => ['en' => 'High-Protein', 'ar' => 'عالي البروتين'], 'slug' => 'high-protein', 'category' => 'dietary'],
            ['name' => ['en' => 'Whole30', 'ar' => 'هول30'], 'slug' => 'whole30', 'category' => 'dietary'],

            // Vibe (20)
            ['name' => ['en' => 'Fine Dining', 'ar' => 'راقٍ'], 'slug' => 'fine-dining', 'category' => 'vibe'],
            ['name' => ['en' => 'Casual', 'ar' => 'كاجوال'], 'slug' => 'casual', 'category' => 'vibe'],
            ['name' => ['en' => 'Street Food', 'ar' => 'طعام الشارع'], 'slug' => 'street-food', 'category' => 'vibe'],
            ['name' => ['en' => 'Family', 'ar' => 'عائلي'], 'slug' => 'family', 'category' => 'vibe'],
            ['name' => ['en' => 'Takeaway', 'ar' => 'سفري'], 'slug' => 'takeaway', 'category' => 'vibe'],
            ['name' => ['en' => 'Brunch', 'ar' => 'فطور متأخر'], 'slug' => 'brunch', 'category' => 'vibe'],
            ['name' => ['en' => 'Romantic', 'ar' => 'رومانسي'], 'slug' => 'romantic', 'category' => 'vibe'],
            ['name' => ['en' => 'Rooftop', 'ar' => 'تراس علوي'], 'slug' => 'rooftop', 'category' => 'vibe'],
            ['name' => ['en' => 'Beachside', 'ar' => 'على الشاطئ'], 'slug' => 'beachside', 'category' => 'vibe'],
            ['name' => ['en' => 'Sports Bar', 'ar' => 'مقهى رياضي'], 'slug' => 'sports-bar', 'category' => 'vibe'],
            ['name' => ['en' => 'Late Night', 'ar' => 'سهرات ليلية'], 'slug' => 'late-night', 'category' => 'vibe'],
            ['name' => ['en' => 'Fast Service', 'ar' => 'خدمة سريعة'], 'slug' => 'fast-service', 'category' => 'vibe'],
            ['name' => ['en' => 'Cozy', 'ar' => 'مريح'], 'slug' => 'cozy', 'category' => 'vibe'],
            ['name' => ['en' => 'Trendy', 'ar' => 'عصري'], 'slug' => 'trendy', 'category' => 'vibe'],
            ['name' => ['en' => 'Artisan', 'ar' => 'حرفي'], 'slug' => 'artisan', 'category' => 'vibe'],
            ['name' => ['en' => 'Lounge', 'ar' => 'لاونج'], 'slug' => 'lounge', 'category' => 'vibe'],
            ['name' => ['en' => 'Bistro', 'ar' => 'بيسترو'], 'slug' => 'bistro', 'category' => 'vibe'],
            ['name' => ['en' => 'Buffet', 'ar' => 'بوفيه'], 'slug' => 'buffet', 'category' => 'vibe'],
            ['name' => ['en' => 'Delivery Only', 'ar' => 'توصيل فقط'], 'slug' => 'delivery-only', 'category' => 'vibe'],
            ['name' => ['en' => 'Pop-Up', 'ar' => 'مؤقت'], 'slug' => 'pop-up', 'category' => 'vibe'],

            // Style (20)
            ['name' => ['en' => 'Dark', 'ar' => 'داكن'], 'slug' => 'dark', 'category' => 'style'],
            ['name' => ['en' => 'Light', 'ar' => 'فاتح'], 'slug' => 'light', 'category' => 'style'],
            ['name' => ['en' => 'Minimal', 'ar' => 'بسيط'], 'slug' => 'minimal', 'category' => 'style'],
            ['name' => ['en' => 'Modern', 'ar' => 'حديث'], 'slug' => 'modern', 'category' => 'style'],
            ['name' => ['en' => 'Bold', 'ar' => 'جريء'], 'slug' => 'bold', 'category' => 'style'],
            ['name' => ['en' => 'Elegant', 'ar' => 'أنيق'], 'slug' => 'elegant', 'category' => 'style'],
            ['name' => ['en' => 'Classic', 'ar' => 'كلاسيكي'], 'slug' => 'classic', 'category' => 'style'],
            ['name' => ['en' => 'Rustic', 'ar' => 'ريفي'], 'slug' => 'rustic', 'category' => 'style'],
            ['name' => ['en' => 'Colorful', 'ar' => 'ملوّن'], 'slug' => 'colorful', 'category' => 'style'],
            ['name' => ['en' => 'Playful', 'ar' => 'مرِح'], 'slug' => 'playful', 'category' => 'style'],
            ['name' => ['en' => 'Vintage', 'ar' => 'عتيق'], 'slug' => 'vintage', 'category' => 'style'],
            ['name' => ['en' => 'Industrial', 'ar' => 'صناعي'], 'slug' => 'industrial', 'category' => 'style'],
            ['name' => ['en' => 'Traditional', 'ar' => 'تقليدي'], 'slug' => 'traditional', 'category' => 'style'],
            ['name' => ['en' => 'Contemporary', 'ar' => 'معاصر'], 'slug' => 'contemporary', 'category' => 'style'],
            ['name' => ['en' => 'Luxurious', 'ar' => 'فخم'], 'slug' => 'luxurious', 'category' => 'style'],
            ['name' => ['en' => 'Warm', 'ar' => 'دافئ'], 'slug' => 'warm', 'category' => 'style'],
            ['name' => ['en' => 'Vibrant', 'ar' => 'نابض بالحياة'], 'slug' => 'vibrant', 'category' => 'style'],
            ['name' => ['en' => 'Chic', 'ar' => 'شيك'], 'slug' => 'chic', 'category' => 'style'],
            ['name' => ['en' => 'Retro', 'ar' => 'ريترو'], 'slug' => 'retro', 'category' => 'style'],
            ['name' => ['en' => 'Artistic', 'ar' => 'فني'], 'slug' => 'artistic', 'category' => 'style'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(['slug' => $tag['slug']], $tag);
        }
    }
}
