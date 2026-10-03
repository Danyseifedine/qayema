<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /** Ownership of the order is checked in the controller. */
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
            'status' => ['required', Rule::enum(OrderStatus::class)],
            // How many times the guest had changed it when the owner's
            // screen showed it: taking it on means taking on that version.
            'guest_updates' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
