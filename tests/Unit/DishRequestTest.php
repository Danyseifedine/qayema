<?php

namespace Tests\Unit;

use App\Http\Requests\DishRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DishRequestTest extends TestCase
{
    public function test_validation_accepts_multiple_image_uploads(): void
    {
        $rules = (new DishRequest)->rules();
        $data = [
            'name' => 'Test Dish',
            'display_order' => 0,
            'is_available' => true,
            'images' => [
                UploadedFile::fake()->image('a.jpg', 100, 100),
                UploadedFile::fake()->image('b.jpg', 100, 100),
            ],
        ];
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes(), (string) $validator->errors());
    }
}
