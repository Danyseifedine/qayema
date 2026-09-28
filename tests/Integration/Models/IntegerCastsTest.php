<?php

namespace Tests\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Some MySQL drivers hand every column back as a string, so production read
 * `"template_id": "1"` where tests read `1`: the dashboard rejected the
 * session, and `$user->id === $restaurant->user_id` refused owners their own
 * orders. Every integer column is cast, so the driver never decides the type.
 */
class IntegerCastsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_integer_column_of_every_model_is_cast(): void
    {
        $uncast = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            if (! is_subclass_of($class, Model::class)) {
                continue;
            }

            $model = new $class;
            $casts = $model->getCasts();

            foreach (Schema::getColumns($model->getTable()) as $column) {
                if (str_contains(strtolower($column['type_name']), 'int') && ! isset($casts[$column['name']])) {
                    $uncast[] = $class.'::'.$column['name'];
                }
            }
        }

        $this->assertSame([], $uncast, 'Cast these in the model\'s casts(): '.implode(', ', $uncast));
    }
}
