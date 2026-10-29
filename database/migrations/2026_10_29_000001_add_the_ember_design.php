<?php

use App\Models\Template;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ember, the dark and warm premium design (resources/views/menu/templates/
 * ember.blade.php, public/css/menu-ember.css): its row, written whole so a
 * database that already has one (made by make:menu-template) ends up the
 * same as a new one. Only packages with premium_designs may use it.
 */
return new class extends Migration
{
    /**
     * What the owner may change on the Appearance page: the gold, the dark
     * page, the text on it, and the name beside the logo.
     *
     * @var array<int, array<string, mixed>>
     */
    private const SCHEMA = [
        ['key' => 'primary_color', 'type' => 'color', 'default' => '#E4CA55',
            'label' => ['en' => 'Gold', 'ar' => 'الذهبي'], 'contrast_with' => 'background_color'],
        ['key' => 'background_color', 'type' => 'color', 'default' => '#0F0F0F',
            'label' => ['en' => 'Background', 'ar' => 'الخلفية']],
        ['key' => 'text_color', 'type' => 'color', 'default' => '#F3EBDD',
            'label' => ['en' => 'Text', 'ar' => 'النص'], 'contrast_with' => 'background_color'],
        ['key' => 'show_name', 'type' => 'boolean', 'default' => true,
            'label' => ['en' => 'Name beside the logo', 'ar' => 'الاسم بجانب الشعار']],
    ];

    public function up(): void
    {
        $row = [
            'name' => json_encode(['en' => 'Ember', 'ar' => 'جمر'], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([
                'en' => 'Dark and warm, with gold lines, serif headings and your cover photo behind the name.',
                'ar' => 'تصميم داكن ودافئ بخطوط ذهبية وعناوين أنيقة، وصورة الغلاف خلف اسم مطعمك.',
            ], JSON_UNESCAPED_UNICODE),
            'settings_schema' => json_encode(self::SCHEMA, JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'is_premium' => true,
            'restaurant_id' => null,
            'sort_order' => 2,
            'updated_at' => now(),
        ];

        if (DB::table('templates')->where('slug', 'ember')->exists()) {
            DB::table('templates')->where('slug', 'ember')->update($row);
        } else {
            DB::table('templates')->insert([...$row, 'slug' => 'ember', 'created_at' => now()]);
        }
    }

    /**
     * The menus drawn in Ember go back to Classic rather than to no design
     * at all (which would take them offline); the colours saved for Ember go
     * with it, and the model's delete takes its picture with it.
     */
    public function down(): void
    {
        $ember = DB::table('templates')->where('slug', 'ember')->value('id');

        if ($ember === null) {
            return;
        }

        $classic = DB::table('templates')->where('slug', 'classic')->value('id');
        DB::table('restaurants')->where('template_id', $ember)->update(['template_id' => $classic]);

        foreach (DB::table('restaurants')->whereNotNull('template_settings')->get(['id', 'template_settings']) as $restaurant) {
            $saved = json_decode((string) $restaurant->template_settings, true);

            if (is_array($saved) && array_key_exists((string) $ember, $saved)) {
                unset($saved[(string) $ember]);
                DB::table('restaurants')->where('id', $restaurant->id)->update(['template_settings' => $saved === [] ? null : json_encode($saved)]);
            }
        }

        Template::query()->whereKey($ember)->first()?->delete();
    }
};
