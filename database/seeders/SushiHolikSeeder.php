<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\MenuSetting;
use App\Models\MenuSocialLink;
use App\Models\User;
use Illuminate\Database\Seeder;

class SushiHolikSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@sushiholik.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Create Menu Owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@sushiholik.com'],
            [
                'name' => 'Sushi Holik Owner',
                'password' => bcrypt('password'),
                'role' => 'menu_owner',
                'restaurant_name' => 'Sushi Holik',
                'phone' => '+1-555-0123',
                'address' => '123 Sushi Street, Tokyo District, Food City',
            ]
        );

        // Create Menu
        $menu = Menu::firstOrCreate(
            ['slug' => 'sushi-holik'],
            [
                'user_id' => $owner->id,
                'name' => 'Sushi Holik',
                'description' => 'Authentic Japanese sushi restaurant offering the finest selection of fresh sushi, sashimi, and traditional Japanese cuisine.',
                'menu_style' => 'restaurant',
                'is_active' => true,
                'dish_limit' => 50,
            ]
        );

        // Create Categories
        $appetizers = Category::firstOrCreate(
            ['menu_id' => $menu->id, 'name' => 'Appetizers'],
            [
                'description' => 'Start your meal with our delicious appetizers',
                'display_order' => 1,
            ]
        );

        $rolls = Category::firstOrCreate(
            ['menu_id' => $menu->id, 'name' => 'Sushi Rolls'],
            [
                'description' => 'Our signature sushi rolls',
                'display_order' => 2,
            ]
        );

        $nigiri = Category::firstOrCreate(
            ['menu_id' => $menu->id, 'name' => 'Nigiri'],
            [
                'description' => 'Traditional hand-pressed sushi',
                'display_order' => 3,
            ]
        );

        $sashimi = Category::firstOrCreate(
            ['menu_id' => $menu->id, 'name' => 'Sashimi'],
            [
                'description' => 'Fresh sliced fish, served without rice',
                'display_order' => 4,
            ]
        );

        $desserts = Category::firstOrCreate(
            ['menu_id' => $menu->id, 'name' => 'Desserts'],
            [
                'description' => 'Sweet endings to your meal',
                'display_order' => 5,
            ]
        );

        $drinks = Category::firstOrCreate(
            ['menu_id' => $menu->id, 'name' => 'Beverages'],
            [
                'description' => 'Refreshing drinks and beverages',
                'display_order' => 6,
            ]
        );

        // Create Appetizers
        $appetizerDishes = [
            [
                'name' => 'Edamame',
                'description' => 'Steamed soybeans with sea salt',
                'price' => 6.99,
                'display_order' => 1,
                'is_available' => true,
            ],
            [
                'name' => 'Miso Soup',
                'description' => 'Traditional Japanese soup with tofu and seaweed',
                'price' => 4.99,
                'display_order' => 2,
                'is_available' => true,
            ],
            [
                'name' => 'Gyoza',
                'description' => 'Pan-fried Japanese dumplings with pork and vegetables',
                'price' => 8.99,
                'display_order' => 3,
                'is_available' => true,
            ],
            [
                'name' => 'Agedashi Tofu',
                'description' => 'Deep-fried tofu in a savory dashi broth',
                'price' => 7.99,
                'display_order' => 4,
                'is_available' => true,
            ],
        ];

        foreach ($appetizerDishes as $dish) {
            Dish::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'category_id' => $appetizers->id,
                    'name' => $dish['name'],
                ],
                array_merge($dish, [
                    'ingredients' => 'Fresh ingredients, traditional preparation',
                    'allergens' => json_encode(['Soy', 'Gluten']),
                    'prep_time' => 10,
                ])
            );
        }

        // Create Sushi Rolls
        $rollDishes = [
            [
                'name' => 'California Roll',
                'description' => 'Crab, avocado, and cucumber',
                'price' => 9.99,
                'display_order' => 1,
                'is_available' => true,
            ],
            [
                'name' => 'Spicy Tuna Roll',
                'description' => 'Fresh tuna with spicy mayo',
                'price' => 11.99,
                'display_order' => 2,
                'is_available' => true,
            ],
            [
                'name' => 'Dragon Roll',
                'description' => 'Eel, cucumber, avocado, and eel sauce',
                'price' => 14.99,
                'display_order' => 3,
                'is_available' => true,
            ],
            [
                'name' => 'Rainbow Roll',
                'description' => 'California roll topped with assorted fish',
                'price' => 15.99,
                'display_order' => 4,
                'is_available' => true,
            ],
            [
                'name' => 'Volcano Roll',
                'description' => 'Spicy crab and avocado, baked with special sauce',
                'price' => 16.99,
                'display_order' => 5,
                'is_available' => true,
            ],
        ];

        foreach ($rollDishes as $dish) {
            Dish::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'category_id' => $rolls->id,
                    'name' => $dish['name'],
                ],
                array_merge($dish, [
                    'ingredients' => 'Premium sushi rice, fresh fish, vegetables',
                    'allergens' => json_encode(['Fish', 'Soy']),
                    'prep_time' => 15,
                ])
            );
        }

        // Create Nigiri
        $nigiriDishes = [
            [
                'name' => 'Salmon Nigiri',
                'description' => 'Fresh Atlantic salmon',
                'price' => 5.99,
                'display_order' => 1,
                'is_available' => true,
            ],
            [
                'name' => 'Tuna Nigiri',
                'description' => 'Premium bluefin tuna',
                'price' => 6.99,
                'display_order' => 2,
                'is_available' => true,
            ],
            [
                'name' => 'Yellowtail Nigiri',
                'description' => 'Fresh yellowtail hamachi',
                'price' => 6.99,
                'display_order' => 3,
                'is_available' => true,
            ],
            [
                'name' => 'Eel Nigiri',
                'description' => 'Grilled eel with eel sauce',
                'price' => 7.99,
                'display_order' => 4,
                'is_available' => true,
            ],
            [
                'name' => 'Shrimp Nigiri',
                'description' => 'Cooked shrimp',
                'price' => 5.99,
                'display_order' => 5,
                'is_available' => true,
            ],
        ];

        foreach ($nigiriDishes as $dish) {
            Dish::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'category_id' => $nigiri->id,
                    'name' => $dish['name'],
                ],
                array_merge($dish, [
                    'ingredients' => 'Sushi rice, fresh fish, wasabi',
                    'allergens' => json_encode(['Fish', 'Shellfish']),
                    'prep_time' => 5,
                ])
            );
        }

        // Create Sashimi
        $sashimiDishes = [
            [
                'name' => 'Salmon Sashimi (5 pcs)',
                'description' => 'Fresh Atlantic salmon slices',
                'price' => 12.99,
                'display_order' => 1,
                'is_available' => true,
            ],
            [
                'name' => 'Tuna Sashimi (5 pcs)',
                'description' => 'Premium bluefin tuna slices',
                'price' => 14.99,
                'display_order' => 2,
                'is_available' => true,
            ],
            [
                'name' => 'Sashimi Platter',
                'description' => 'Assorted fresh fish (15 pieces)',
                'price' => 29.99,
                'display_order' => 3,
                'is_available' => true,
            ],
        ];

        foreach ($sashimiDishes as $dish) {
            Dish::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'category_id' => $sashimi->id,
                    'name' => $dish['name'],
                ],
                array_merge($dish, [
                    'ingredients' => 'Fresh fish, wasabi, pickled ginger',
                    'allergens' => json_encode(['Fish']),
                    'prep_time' => 8,
                ])
            );
        }

        // Create Desserts
        $dessertDishes = [
            [
                'name' => 'Mochi Ice Cream',
                'description' => 'Green tea, vanilla, or strawberry',
                'price' => 6.99,
                'display_order' => 1,
                'is_available' => true,
            ],
            [
                'name' => 'Tempura Ice Cream',
                'description' => 'Vanilla ice cream wrapped in tempura batter',
                'price' => 7.99,
                'display_order' => 2,
                'is_available' => true,
            ],
            [
                'name' => 'Green Tea Cheesecake',
                'description' => 'Creamy matcha cheesecake',
                'price' => 8.99,
                'display_order' => 3,
                'is_available' => true,
            ],
        ];

        foreach ($dessertDishes as $dish) {
            Dish::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'category_id' => $desserts->id,
                    'name' => $dish['name'],
                ],
                array_merge($dish, [
                    'ingredients' => 'Premium ingredients, traditional recipes',
                    'allergens' => json_encode(['Dairy', 'Gluten', 'Eggs']),
                    'prep_time' => 5,
                ])
            );
        }

        // Create Beverages
        $drinkDishes = [
            [
                'name' => 'Green Tea',
                'description' => 'Hot or iced Japanese green tea',
                'price' => 3.99,
                'display_order' => 1,
                'is_available' => true,
            ],
            [
                'name' => 'Sake (Hot)',
                'description' => 'Traditional Japanese rice wine',
                'price' => 8.99,
                'display_order' => 2,
                'is_available' => true,
            ],
            [
                'name' => 'Japanese Beer',
                'description' => 'Asahi, Kirin, or Sapporo',
                'price' => 5.99,
                'display_order' => 3,
                'is_available' => true,
            ],
            [
                'name' => 'Soft Drinks',
                'description' => 'Coke, Sprite, or Japanese sodas',
                'price' => 2.99,
                'display_order' => 4,
                'is_available' => true,
            ],
        ];

        foreach ($drinkDishes as $dish) {
            Dish::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'category_id' => $drinks->id,
                    'name' => $dish['name'],
                ],
                array_merge($dish, [
                    'ingredients' => 'Premium beverages',
                    'allergens' => json_encode([]),
                    'prep_time' => 2,
                ])
            );
        }

        // Create Menu Settings
        MenuSetting::firstOrCreate(
            ['menu_id' => $menu->id],
            [
                'theme_color' => '#dc2626',
                'currency' => 'USD',
                'language' => 'en',
            ]
        );

        // Create Social Links
        $socialLinks = [
            [
                'platform' => 'facebook',
                'url' => 'https://facebook.com/sushiholik',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'platform' => 'instagram',
                'url' => 'https://instagram.com/sushiholik',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'platform' => 'twitter',
                'url' => 'https://twitter.com/sushiholik',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'platform' => 'website',
                'url' => 'https://sushiholik.com',
                'display_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($socialLinks as $link) {
            MenuSocialLink::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'platform' => $link['platform'],
                ],
                $link
            );
        }

        $this->command->info('✅ Sushi Holik menu created successfully!');
        $this->command->info('📧 Admin Login: admin@sushiholik.com / password');
        $this->command->info('📧 Owner Login: owner@sushiholik.com / password');
        $this->command->info('🍣 Menu: Sushi Holik with ' . Dish::where('menu_id', $menu->id)->count() . ' dishes');
    }
}
