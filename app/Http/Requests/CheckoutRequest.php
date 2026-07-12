<?php

namespace App\Http\Requests;

use App\Services\Global\FeatureCatalog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * Restricted to the authenticated owner's own restaurant in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sellableIds = array_keys(app(FeatureCatalog::class)->all());

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string', Rule::in($sellableIds)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.id.in' => __('That add-on is not available for purchase.'),
        ];
    }

    /**
     * Reject a cart whose translated unit quantity would exceed the Paddle
     * price's maximum, so we fail fast with a 422 instead of a Paddle 400.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $catalog = app(FeatureCatalog::class);

            foreach ((array) $this->input('items', []) as $index => $line) {
                $entry = $catalog->find($line['id'] ?? '');

                if ($entry === null || ! isset($entry['max'])) {
                    continue;
                }

                if ((int) ($line['quantity'] ?? 0) * $entry['step'] > $entry['max']) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        __('That quantity exceeds the maximum available for this add-on.'),
                    );
                }
            }
        });
    }
}
