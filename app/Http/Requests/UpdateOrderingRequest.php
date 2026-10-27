<?php

namespace App\Http\Requests;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderingRequest extends FormRequest
{
    /** An owner with a restaurant. */
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
            // A way the package has (Restaurant::orderWays()).
            'mode' => ['required', Rule::enum(OrderChannel::class), function (string $attribute, mixed $value, Closure $fail): void {
                if (! in_array(OrderChannel::tryFrom((string) $value), $this->user()->restaurant->orderWays(), true)) {
                    $fail($value === OrderChannel::Menu->value
                        ? __('Orders in the menu are not on your package.')
                        : __('Orders on WhatsApp are not on your package.'));
                }
            }],
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
