<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id ?? 0;

        return [
            'name' => ['required', 'array'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.en' => ['nullable', 'string', 'max:2000'],
            'ingredients.ar' => ['nullable', 'string', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            'is_available' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $name = (array) $this->input('name', []);

            if (blank($name['en'] ?? null) && blank($name['ar'] ?? null)) {
                $validator->errors()->add('name', __('A dish name is required in at least one language.'));
            }
        });
    }
}
