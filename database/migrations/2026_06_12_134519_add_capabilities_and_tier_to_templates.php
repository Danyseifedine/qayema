<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collapse the 20 has_* flag columns + direction columns into a single
     * capabilities JSON, convert the fields schema into a precomputed
     * default_settings map, and add tier/sort_order. All existing templates
     * become tier=free so no live menu goes offline; the admin flips chosen
     * templates to paid (and adds template_prices) when monetization starts.
     *
     * @var string[]
     */
    private array $flags = [
        'has_logo', 'has_cover_image', 'has_description', 'has_phone',
        'has_address', 'has_map', 'has_schedule', 'has_social_links',
        'has_dish_images', 'has_dish_ingredients', 'has_dish_prices', 'has_dish_tags',
        'has_category_images', 'has_category_description',
        'has_search', 'has_search_title', 'has_order_page_title',
        'has_final_price_show', 'has_share_button', 'has_qr_code',
    ];

    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('tier', 8)->default('free')->after('slug');
            $table->json('capabilities')->nullable()->after('tier');
            $table->json('default_settings')->nullable()->after('capabilities');
            $table->integer('sort_order')->default(0)->after('is_active');
        });

        DB::table('templates')->orderBy('id')->chunkById(100, function ($templates) {
            foreach ($templates as $template) {
                $capabilities = [];

                foreach ($this->flags as $flag) {
                    $capabilities[substr($flag, 4)] = (bool) $template->{$flag};
                }

                $capabilities['direction'] = $template->default_direction ?: 'rtl';
                $capabilities['direction_switchable'] = (bool) $template->allows_direction_change;

                $fields = json_decode($template->fields ?? '[]', true) ?: [];
                $defaults = collect($fields)
                    ->filter(fn ($field) => isset($field['key']))
                    ->mapWithKeys(fn ($field) => [$field['key'] => $field['default'] ?? null])
                    ->all();

                DB::table('templates')->where('id', $template->id)->update([
                    'capabilities' => json_encode($capabilities),
                    'default_settings' => $defaults === [] ? null : json_encode($defaults),
                ]);
            }
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn([...$this->flags, 'default_direction', 'allows_direction_change', 'fields']);
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->json('fields')->nullable();

            foreach ($this->flags as $flag) {
                $table->boolean($flag)->default(true);
            }

            $table->string('default_direction', 3)->default('ltr');
            $table->boolean('allows_direction_change')->default(true);
        });

        DB::table('templates')->orderBy('id')->chunkById(100, function ($templates) {
            foreach ($templates as $template) {
                $capabilities = json_decode($template->capabilities ?? '{}', true) ?: [];
                $update = [];

                foreach ($this->flags as $flag) {
                    $update[$flag] = (bool) ($capabilities[substr($flag, 4)] ?? true);
                }

                $update['default_direction'] = $capabilities['direction'] ?? 'ltr';
                $update['allows_direction_change'] = (bool) ($capabilities['direction_switchable'] ?? true);

                DB::table('templates')->where('id', $template->id)->update($update);
            }
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['tier', 'capabilities', 'default_settings', 'sort_order']);
        });
    }
};
