<?php

namespace Tests\Feature\Health;

use App\Models\Restaurant;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * AppServiceProvider::keepModelsStrict(): an N+1 or a column read that was
 * not selected throws here, so it is fixed before it ships; production only
 * logs a lazy load, once a request per relation.
 */
class StrictModelsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function tearDown(): void
    {
        Model::handleLazyLoadingViolationUsing(null);
        Model::preventSilentlyDiscardingAttributes();
        Model::preventAccessingMissingAttributes();

        parent::tearDown();
    }

    public function test_a_relation_read_one_row_at_a_time_throws(): void
    {
        $this->owner();
        $this->owner();

        $this->expectException(LazyLoadingViolationException::class);
        Restaurant::query()->get()->each(fn (Restaurant $restaurant) => $restaurant->package);
    }

    public function test_a_column_that_was_not_selected_throws(): void
    {
        $this->owner();

        $this->expectException(MissingAttributeException::class);
        Restaurant::query()->first(['id'])->package_ends_at;
    }

    public function test_production_logs_a_lazy_load_once_and_carries_on(): void
    {
        $this->app['env'] = 'production';
        $this->app->getProvider(AppServiceProvider::class)->boot();
        Log::spy();
        $this->owner();
        $this->owner();

        $packages = Restaurant::query()->get()->map(fn (Restaurant $restaurant) => $restaurant->package?->id);

        $this->assertCount(2, $packages);
        Log::shouldHaveReceived('warning')
            ->with('Lazy loading: '.Restaurant::class.'::package read one row at a time; eager load it.')
            ->once();
    }
}
