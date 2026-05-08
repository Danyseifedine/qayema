<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. templates ─────────────────────────────────────────────────
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── 2. restaurants ────────────────────────────────────────────────
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('country_code', 5)->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('dish_limit')->default(40);
            $table->integer('category_limit')->default(10);
            $table->integer('social_link_limit')->default(4);
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->json('template_settings')->nullable();
            $table->timestamps();
        });

        // ── 3. Migrate menus → restaurants ───────────────────────────────
        DB::table('menus')->get()->each(function ($menu) {
            $user = DB::table('users')->where('id', $menu->user_id)->first();

            DB::table('restaurants')->insert([
                'user_id' => $menu->user_id,
                'name' => ($user && ! empty($user->restaurant_name)) ? $user->restaurant_name : $menu->name,
                'description' => $menu->description,
                'slug' => $menu->slug,
                'phone' => $user->phone ?? null,
                'is_active' => $menu->is_active,
                'dish_limit' => $menu->dish_limit,
                'category_limit' => $menu->category_limit,
                'social_link_limit' => $menu->social_link_limit,
                'created_at' => $menu->created_at,
                'updated_at' => $menu->updated_at,
            ]);
        });

        // ── 4. Re-point logo + cover_image media from User → Restaurant ──
        DB::table('media')
            ->where('model_type', 'App\\Models\\User')
            ->whereIn('collection_name', ['logo', 'cover_image'])
            ->get()
            ->each(function ($row) {
                $restaurant = DB::table('restaurants')->where('user_id', $row->model_id)->first();

                if ($restaurant) {
                    DB::table('media')->where('id', $row->id)->update([
                        'model_type' => 'App\\Models\\Restaurant',
                        'model_id' => $restaurant->id,
                    ]);
                }
            });

        // ── 5. restaurant_social_links ────────────────────────────────────
        Schema::create('restaurant_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('url');
            $table->timestamps();
        });

        DB::table('menu_social_links')->get()->each(function ($row) {
            $menu = DB::table('menus')->where('id', $row->menu_id)->first();
            $restaurant = $menu ? DB::table('restaurants')->where('user_id', $menu->user_id)->first() : null;

            if ($restaurant) {
                DB::table('restaurant_social_links')->insert([
                    'restaurant_id' => $restaurant->id,
                    'platform' => $row->platform,
                    'url' => $row->url,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });

        // ── 6. restaurant_statistics ──────────────────────────────────────
        Schema::create('restaurant_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->index();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamp('viewed_at');
            $table->integer('time_spent')->nullable();
            $table->integer('page_views')->default(1);
            $table->timestamps();

            $table->index(['restaurant_id', 'viewed_at']);
            $table->index(['session_id', 'restaurant_id']);
        });

        DB::table('menu_statistics')->get()->each(function ($row) {
            $menu = DB::table('menus')->where('id', $row->menu_id)->first();
            $restaurant = $menu ? DB::table('restaurants')->where('user_id', $menu->user_id)->first() : null;

            if ($restaurant) {
                DB::table('restaurant_statistics')->insert([
                    'restaurant_id' => $restaurant->id,
                    'session_id' => $row->session_id,
                    'device_type' => $row->device_type,
                    'browser' => $row->browser,
                    'os' => $row->os,
                    'viewed_at' => $row->viewed_at,
                    'time_spent' => $row->time_spent,
                    'page_views' => $row->page_views,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });

        // ── 7. categories: add restaurant_id, backfill, drop menu_id ─────
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
        });

        DB::table('categories')->get()->each(function ($row) {
            $menu = DB::table('menus')->where('id', $row->menu_id)->first();
            $restaurant = $menu ? DB::table('restaurants')->where('user_id', $menu->user_id)->first() : null;

            if ($restaurant) {
                DB::table('categories')->where('id', $row->id)->update(['restaurant_id' => $restaurant->id]);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
            $table->dropColumn('menu_id');
        });

        DB::statement('ALTER TABLE categories MODIFY restaurant_id BIGINT UNSIGNED NOT NULL');

        // ── 8. dishes: add restaurant_id, backfill, drop menu_id ─────────
        Schema::table('dishes', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id')->nullable()->after('id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
        });

        DB::table('dishes')->get()->each(function ($row) {
            $menu = DB::table('menus')->where('id', $row->menu_id)->first();
            $restaurant = $menu ? DB::table('restaurants')->where('user_id', $menu->user_id)->first() : null;

            if ($restaurant) {
                DB::table('dishes')->where('id', $row->id)->update(['restaurant_id' => $restaurant->id]);
            }
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
            $table->dropColumn('menu_id');
        });

        DB::statement('ALTER TABLE dishes MODIFY restaurant_id BIGINT UNSIGNED NOT NULL');

        // ── 9. Drop legacy tables ─────────────────────────────────────────
        Schema::dropIfExists('menu_social_links');
        Schema::dropIfExists('menu_statistics');
        Schema::dropIfExists('menu_settings');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('menus');

        // ── 10. Drop restaurant columns from users ────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['restaurant_name', 'phone', 'address']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('restaurant_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('dish_limit')->default(40);
            $table->integer('category_limit')->default(10);
            $table->integer('social_link_limit')->default(4);
            $table->timestamps();
        });

        Schema::create('menu_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('setting_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['menu_id', 'setting_id']);
        });

        Schema::create('menu_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('menu_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->index();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamp('viewed_at');
            $table->integer('time_spent')->nullable();
            $table->integer('page_views')->default(1);
            $table->timestamps();
        });

        // Restore media back to User
        DB::table('media')
            ->where('model_type', 'App\\Models\\Restaurant')
            ->whereIn('collection_name', ['logo', 'cover_image'])
            ->get()
            ->each(function ($row) {
                $restaurant = DB::table('restaurants')->where('id', $row->model_id)->first();

                if ($restaurant) {
                    DB::table('media')->where('id', $row->id)->update([
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $restaurant->user_id,
                    ]);
                }
            });

        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_id')->nullable()->after('id');
            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn('restaurant_id');
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_id')->nullable()->after('id');
            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn('restaurant_id');
        });

        Schema::dropIfExists('restaurant_social_links');
        Schema::dropIfExists('restaurant_statistics');
        Schema::dropIfExists('restaurants');
        Schema::dropIfExists('templates');
    }
};
