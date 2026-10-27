<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * What a restaurant pays a month, in its package's currency, and whether
 * it pays through Whish Money. No price (null or left out) means its
 * package's price.
 */
class UpdateBillingRequest extends FormRequest
{
    /** The highest price a month, in cents (100,000.00). */
    public const MAX_PRICE_CENTS = 10_000_000;

    public function authorize(): bool
    {
        // The route already requires an admin.
        return true;
    }

    /**
     * The price rule, shared with a package change that sets one.
     *
     * @return array<int, string>
     */
    public static function priceRules(): array
    {
        return ['nullable', 'integer', 'min:0', 'max:'.self::MAX_PRICE_CENTS];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'price_cents' => self::priceRules(),
            'pays_with_whish' => ['required', 'boolean'],
        ];
    }
}
