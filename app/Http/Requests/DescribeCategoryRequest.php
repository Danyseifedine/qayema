<?php

namespace App\Http\Requests;

use App\Services\Menu\MenuLanguages;
use App\Support\MenuTextLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DescribeCategoryRequest extends FormRequest
{
    /** An owner with a restaurant. */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * The category's name, the owner's description when there is one to
     * improve, and the menu language to write in. The limits are the
     * category's own (StoreCategoryRequest).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.MenuTextLimits::CATEGORY_NAME],
            'text' => ['nullable', 'string', 'max:'.MenuTextLimits::CATEGORY_DESCRIPTION],
            'language' => ['required', 'string', Rule::in(MenuLanguages::forOwner($this->user()))],
        ];
    }
}
