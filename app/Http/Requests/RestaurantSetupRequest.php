<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantSetupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $step = $this->route('step') ?? $this->input('step', 1);

        if ($step == 1) {
            return [
                'restaurant_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
            ];
        }

        if ($step == 2) {
            return [
                'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
                'cover_image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            ];
        }

        return [];
    }
}
