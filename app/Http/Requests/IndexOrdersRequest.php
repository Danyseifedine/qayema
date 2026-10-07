<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            // `table`: orders to a table (the Table orders page); `away`:
            // the rest (the Orders page). Left out, both.
            'kind' => ['nullable', Rule::in(['away', 'table'])],
        ];
    }
}
