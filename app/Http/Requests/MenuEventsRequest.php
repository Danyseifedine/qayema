<?php

namespace App\Http\Requests;

use App\Enums\MenuEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuEventsRequest extends FormRequest
{
    /** The public menu is open to guests; abuse is handled by the limiter. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape only. Whether a dish or category belongs to this restaurant is
     * settled in MenuEventRecorder, which drops what does not.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:25'],
            'events.*.type' => ['required', 'string', Rule::enum(MenuEventType::class)],
            'events.*.dish_id' => ['nullable', 'integer', 'min:1'],
            'events.*.category_id' => ['nullable', 'integer', 'min:1'],
            'events.*.value' => ['nullable', 'string', 'max:64'],
        ];
    }
}
