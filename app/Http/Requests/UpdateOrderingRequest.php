<?php

namespace App\Http\Requests;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderingRequest extends FormRequest
{
    /**
     * An owner with a restaurant; ordering in the menu also needs the
     * package to include it.
     */
    public function authorize(): bool
    {
        $restaurant = $this->user()?->restaurant;

        if ($restaurant === null) {
            return false;
        }

        return $this->input('mode') !== OrderChannel::Menu->value
            || $restaurant->entitlements()->can(Feature::MenuOrdering);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(OrderChannel::class)],
            // At least one kind of order, or a guest could not order at all.
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['string', 'distinct', Rule::in(Fulfilment::away())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'types.required' => __('Keep delivery or pickup on.'),
            'types.min' => __('Keep delivery or pickup on.'),
        ];
    }
}
