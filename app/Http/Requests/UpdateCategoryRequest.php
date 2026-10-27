<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\CategoryRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    use CategoryRules;

    /**
     * Authorization is enforced by the policy in the controller against the
     * resolved category, so the request itself only validates shape.
     */
    public function authorize(): bool
    {
        return true;
    }
}
