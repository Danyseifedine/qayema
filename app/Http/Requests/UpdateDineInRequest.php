<?php

namespace App\Http\Requests;

use App\Enums\OrderChannel;
use App\Services\Orders\WhatsAppLink;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDineInRequest extends FormRequest
{
    /** An owner with a restaurant. */
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * How orders at the table come in: placed in the menu (the Table orders
     * page) or on WhatsApp, which needs a number to send to.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(OrderChannel::class), function (string $attribute, mixed $value, Closure $fail): void {
                if ($value === OrderChannel::WhatsApp->value && WhatsAppLink::internationalNumber($this->user()->restaurant) === null) {
                    $fail(__('Add your WhatsApp number on the Restaurant page first.'));
                }
            }],
        ];
    }
}
