<?php

namespace App\Http\Requests;

use App\Services\Menu\MenuLanguages;
use App\Support\MenuTextLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DescribeRestaurantRequest extends FormRequest
{
    /** An owner with a restaurant. */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * The owner's description when there is one to improve, and the menu
     * language to write in; the name and the menu are read from the
     * restaurant. The limit is the description's own (UpdateRestaurantRequest).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'text' => ['nullable', 'string', 'max:'.MenuTextLimits::RESTAURANT_DESCRIPTION],
            'language' => ['required', 'string', Rule::in(MenuLanguages::forOwner($this->user()))],
        ];
    }
}
