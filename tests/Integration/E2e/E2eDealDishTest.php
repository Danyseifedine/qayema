<?php

namespace Tests\Integration\E2e;

use App\Models\Dish;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\E2e\E2eController;
use Tests\E2e\E2eServiceProvider;
use Tests\TestCase;

/**
 * The e2e scenario's offers with a deal dish: made in the named category,
 * the offer's one dish, and its id handed back with the offer.
 */
class E2eDealDishTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_scenario_offer_brings_its_deal_dish(): void
    {
        // The store the scenario counts AI uses in, which the e2e server adds.
        config(['cache.stores.'.E2eServiceProvider::WRITING_HELP_CACHE => ['driver' => 'array']]);

        $response = app(E2eController::class)->scenario(Request::create('/__e2e/scenario', 'POST', [
            'package' => 'premium',
            'template' => null,
            'categories' => [['name' => ['en' => 'Pizza'], 'dishes' => [['name' => ['en' => 'Margherita']]]]],
            'offers' => [
                ['title' => ['en' => 'Family meal'], 'placements' => ['banner'], 'action' => 'dishes', 'deal' => ['name' => ['en' => 'Family box'], 'price' => 25, 'category' => 'Pizza', 'ingredients' => ['en' => '2 pizzas, drinks']]],
                ['title' => ['en' => 'Plain'], 'placements' => ['banner']],
            ],
        ]));

        $offers = $response->getData(true)['offers'];
        $deal = Dish::query()->findOrFail($offers[0]['deal_id']);
        $offer = Offer::query()->findOrFail($offers[0]['id']);

        $this->assertSame('Family box', $deal->getTranslation('name', 'en'));
        $this->assertSame('25.00', (string) $deal->price);
        $this->assertSame($offer->id, $deal->offer_id);
        $this->assertSame($response->getData(true)['categories'][0]['id'], $deal->category_id);
        $this->assertSame([$deal->id], $offer->dishes()->pluck('dishes.id')->all());
        $this->assertNull($offers[1]['deal_id']);
    }
}
