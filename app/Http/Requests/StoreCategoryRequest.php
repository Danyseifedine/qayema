<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\CategoryRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    use CategoryRules;

    /**
     * Authorization is enforced by the policy in the controller (it needs the
     * restaurant context), so the request itself only validates shape.
     */
    public function authorize(): bool
    {
        return true;
    }
}
